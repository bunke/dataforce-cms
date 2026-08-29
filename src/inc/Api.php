<?php

/**
 * DataForce Machine API — shared core for the JSON API (controllers/api.php)
 * and the MCP endpoint (controllers/mcp.php).
 *
 * Design:
 *   - Opt-in per model:  `public $API = 1;`  (full CRUD)  or  `public $API = 'ro';`
 *     (read-only) on any admin_* class. Models without the flag are invisible.
 *   - Everything is introspected from the model's Field[] definitions — no
 *     separate schema to maintain. Field types map to JSON Schema types.
 *   - All SQL goes through PDO prepared statements. The legacy
 *     insertQuery()/updateQuery() string-concatenation paths are NOT used.
 *   - Domain actions: a model may define `apiAction_<name>(array $params)`
 *     methods; they are exposed as POST actions / MCP tools. Optional
 *     `public $API_ACTIONS = ['<name>' => ['description'=>..., 'input'=>[...],
 *     'required'=>[...]]]` supplies docs + JSON Schema for the action.
 *
 * Auth: Bearer token from $dfConfig['api_token'] (see config.sample.php).
 * A token shorter than 16 chars is treated as "not configured" (endpoint off).
 */

if (!defined('DATAFORCE_API_VERSION')) {
	define('DATAFORCE_API_VERSION', '1.0.0');
}

class DataForceApiError extends Exception
{
	public $httpStatus;

	public function __construct($message, $httpStatus = 400)
	{
		parent::__construct($message);
		$this->httpStatus = $httpStatus;
	}
}

class DataForceApi
{
	/** @var PDO */
	private $pdo;
	/** @var array */
	private $config;
	/** @var array|null cache of resource => model instance */
	private $registry = null;

	const MIN_TOKEN_LEN = 16;
	const MAX_LIMIT = 500;
	const DEFAULT_LIMIT = 50;

	// Field types that are not real DB columns (section dividers / computed)
	private static $virtualTypes = [8]; // C_HR
	// Field types rendered by custom code — real columns, but API read-only
	private static $readOnlyTypes = [5, 14]; // C_SPEC, custom subquery

	public function __construct(PDO $pdo, array $config)
	{
		$this->pdo = $pdo;
		$this->config = $config;
	}

	// ------------------------------------------------------------------ auth

	public function tokenConfigured()
	{
		$t = isset($this->config['api_token']) ? (string)$this->config['api_token'] : '';
		return strlen($t) >= self::MIN_TOKEN_LEN;
	}

	/**
	 * Validate `Authorization: Bearer <token>`. Throws DataForceApiError
	 * (503 when no token configured, 401 on mismatch).
	 */
	public function authenticate()
	{
		if (!$this->tokenConfigured()) {
			throw new DataForceApiError(
				'API disabled: api_token is not configured (need >= ' . self::MIN_TOKEN_LEN . ' chars)', 503
			);
		}

		$header = '';
		foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
			if (!empty($_SERVER[$key])) {
				$header = $_SERVER[$key];
				break;
			}
		}
		if ($header === '' && function_exists('apache_request_headers')) {
			$hs = apache_request_headers();
			foreach ($hs as $k => $v) {
				if (strtolower($k) === 'authorization') {
					$header = $v;
					break;
				}
			}
		}

		if (!preg_match('/^Bearer\s+(\S+)$/i', trim($header), $m)) {
			throw new DataForceApiError('Missing Authorization: Bearer <token> header', 401);
		}
		if (!hash_equals((string)$this->config['api_token'], $m[1])) {
			throw new DataForceApiError('Invalid API token', 401);
		}
	}

	// -------------------------------------------------------------- registry

	/**
	 * All admin_* models that opted in via `$API`.
	 * @return array resource => ['model'=>obj, 'readOnly'=>bool]
	 */
	public function resources()
	{
		if ($this->registry !== null) {
			return $this->registry;
		}
		$this->registry = [];
		foreach (get_declared_classes() as $class) {
			if (strncmp($class, 'admin_', 6) !== 0) {
				continue;
			}
			$vars = get_class_vars($class);
			if (empty($vars['API'])) {
				continue;
			}
			$model = new $class();
			if (empty($model->fld) || !is_array($model->fld)) {
				continue;
			}
			$resource = substr($class, 6);
			$this->registry[$resource] = [
				'model'    => $model,
				'readOnly' => ($vars['API'] === 'ro'),
			];
		}
		ksort($this->registry);
		return $this->registry;
	}

	/** @return array ['model'=>obj,'readOnly'=>bool] */
	public function resource($name)
	{
		$all = $this->resources();
		if (!isset($all[$name])) {
			throw new DataForceApiError("Unknown resource '$name'", 404);
		}
		return $all[$name];
	}

	private function table($model)
	{
		if (!empty($model->TABLE)) {
			return $model->TABLE;
		}
		return str_replace('admin_', '', get_class($model));
	}

	// ---------------------------------------------------------- introspection

	/**
	 * Column metadata from the model's Field[] list.
	 * @return array name => ['type','jsonType','label','writable','fk'=>?table]
	 */
	public function fields($model)
	{
		$out = [
			'id' => ['type' => 0, 'jsonType' => 'integer', 'label' => 'ID', 'writable' => false, 'fk' => null],
		];
		$multiLang = !empty($model->MULTI_LANG);

		foreach ($model->fld as $f) {
			if (!isset($f->name) || $f->name === '' || $f->name === 'id') {
				continue;
			}
			if (in_array($f->type, self::$virtualTypes, true)) {
				continue;
			}
			// multi-lang columns live in <table>_info — out of scope for v1
			if ($multiLang && !empty($f->multi_lang)) {
				continue;
			}
			$writable = !in_array($f->type, self::$readOnlyTypes, true);
			$out[$f->name] = [
				'type'     => $f->type,
				'jsonType' => $this->jsonType($f->type),
				'label'    => isset($f->txt) ? $f->txt : $f->name,
				'writable' => $writable,
				'fk'       => (!empty($f->table_val) ? $f->table_val : null),
			];
		}
		return $out;
	}

	private function jsonType($cType)
	{
		switch ($cType) {
			case 0:  return 'number';   // C_FLOAT
			case 6:  return 'integer';  // C_CHECKBOX (0|1)
			case 9:  return 'integer';  // C_LIST (FK id)
			default: return 'string';
		}
	}

	/** Public self-description of one resource (for ?action=schema and MCP). */
	public function describe($resourceName)
	{
		$r = $this->resource($resourceName);
		$model = $r['model'];
		$fields = [];
		foreach ($this->fields($model) as $name => $meta) {
			$fields[] = [
				'name'     => $name,
				'type'     => $meta['jsonType'],
				'label'    => $meta['label'],
				'writable' => $meta['writable'] && !$r['readOnly'],
				'fk_table' => $meta['fk'],
			];
		}
		return [
			'resource'  => $resourceName,
			'table'     => $this->table($model),
			'label'     => isset($model->NAME) ? $model->NAME : $resourceName,
			'read_only' => $r['readOnly'],
			'fields'    => $fields,
			'actions'   => array_keys($this->actions($model)),
		];
	}

	/**
	 * Domain actions: apiAction_<name>() methods + $API_ACTIONS metadata.
	 * @return array name => ['description'=>string,'input'=>props,'required'=>[]]
	 */
	public function actions($model)
	{
		$meta = isset($model->API_ACTIONS) && is_array($model->API_ACTIONS) ? $model->API_ACTIONS : [];
		$out = [];
		foreach (get_class_methods($model) as $m) {
			if (strncmp($m, 'apiAction_', 10) !== 0) {
				continue;
			}
			$name = substr($m, 10);
			$info = isset($meta[$name]) ? $meta[$name] : [];
			$out[$name] = [
				'description' => isset($info['description']) ? $info['description'] : ('Domain action ' . $name),
				'input'       => isset($info['input']) ? $info['input'] : (object)[],
				'required'    => isset($info['required']) ? $info['required'] : [],
			];
		}
		return $out;
	}

	// -------------------------------------------------------------------- CRUD

	public function listRows($resourceName, array $filters = [], $page = 1, $limit = self::DEFAULT_LIMIT, $sort = null, $order = 'desc')
	{
		$r = $this->resource($resourceName);
		$model = $r['model'];
		$table = $this->table($model);
		$fields = $this->fields($model);

		$page  = max(1, (int)$page);
		$limit = min(self::MAX_LIMIT, max(1, (int)$limit));
		$order = (strtolower((string)$order) === 'asc') ? 'ASC' : 'DESC';
		if ($sort === null || !isset($fields[$sort])) {
			$sort = 'id';
		}

		$where = [];
		$params = [];
		foreach ($filters as $col => $val) {
			if (!isset($fields[$col])) {
				throw new DataForceApiError("Unknown filter column '$col'", 400);
			}
			$where[] = "`$col` = ?";
			$params[] = $val;
		}
		$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

		$stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `$table`" . $whereSql);
		$stmt->execute($params);
		$total = (int)$stmt->fetchColumn();

		$offset = ($page - 1) * $limit;
		$stmt = $this->pdo->prepare(
			"SELECT * FROM `$table`" . $whereSql . " ORDER BY `$sort` $order LIMIT $limit OFFSET $offset"
		);
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return [
			'items' => $rows,
			'meta'  => ['page' => $page, 'limit' => $limit, 'total' => $total, 'sort' => $sort, 'order' => strtolower($order)],
		];
	}

	public function getRow($resourceName, $id)
	{
		$table = $this->table($this->resource($resourceName)['model']);
		$stmt = $this->pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
		$stmt->execute([(int)$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($row === false) {
			throw new DataForceApiError("Record $id not found in '$resourceName'", 404);
		}
		return $row;
	}

	public function createRow($resourceName, array $data)
	{
		$r = $this->assertWritable($resourceName);
		$model = $r['model'];
		$table = $this->table($model);
		$fields = $this->fields($model);

		$cols = [];
		$vals = [];
		foreach ($data as $col => $val) {
			$this->assertWritableColumn($fields, $col);
			$cols[] = $col;
			$vals[] = $this->normalizeValue($fields[$col], $val);
		}
		if (!$cols) {
			throw new DataForceApiError('Empty payload — nothing to insert', 400);
		}

		// Auto-fill conventional columns the admin UI normally sets
		foreach (['creation_time', 'crtdate', 'created_at'] as $tsCol) {
			if (isset($fields[$tsCol]) && !in_array($tsCol, $cols, true)) {
				$cols[] = $tsCol;
				$vals[] = time();
			}
		}
		if (isset($fields['sort']) && !in_array('sort', $cols, true)) {
			$cols[] = 'sort';
			$vals[] = 0;
		}

		$colSql = '`' . implode('`,`', $cols) . '`';
		$phSql  = implode(',', array_fill(0, count($cols), '?'));
		$stmt = $this->pdo->prepare("INSERT INTO `$table` ($colSql) VALUES ($phSql)");
		$stmt->execute($vals);
		$id = (int)$this->pdo->lastInsertId();

		$row = $this->getRow($resourceName, $id);
		$this->callHook($model, 'afterAdd', [$row]);
		return $row;
	}

	public function updateRow($resourceName, $id, array $data)
	{
		$r = $this->assertWritable($resourceName);
		$model = $r['model'];
		$table = $this->table($model);
		$fields = $this->fields($model);

		$before = $this->getRow($resourceName, $id); // 404 if missing

		$sets = [];
		$vals = [];
		foreach ($data as $col => $val) {
			$this->assertWritableColumn($fields, $col);
			$sets[] = "`$col` = ?";
			$vals[] = $this->normalizeValue($fields[$col], $val);
		}
		if (!$sets) {
			throw new DataForceApiError('Empty payload — nothing to update', 400);
		}
		if (isset($fields['update_time']) && !isset($data['update_time'])) {
			$sets[] = '`update_time` = ?';
			$vals[] = time();
		}
		$vals[] = (int)$id;

		$stmt = $this->pdo->prepare("UPDATE `$table` SET " . implode(', ', $sets) . ' WHERE id = ?');
		$stmt->execute($vals);

		$row = $this->getRow($resourceName, $id);
		$this->callHook($model, 'afterEdit', [$row]);
		return $row;
	}

	public function deleteRow($resourceName, $id)
	{
		$r = $this->assertWritable($resourceName);
		$model = $r['model'];
		$table = $this->table($model);

		$row = $this->getRow($resourceName, $id); // 404 if missing
		if (!empty($row['no_del'])) {
			throw new DataForceApiError("Record $id is protected (no_del=1)", 409);
		}

		$this->callHook($model, 'beforeDelete', [$row]);
		$stmt = $this->pdo->prepare("DELETE FROM `$table` WHERE id = ?");
		$stmt->execute([(int)$id]);
		$this->callHook($model, 'onDelete', [(int)$id]);

		return ['deleted' => true, 'id' => (int)$id];
	}

	public function callAction($resourceName, $actionName, array $params)
	{
		$model = $this->resource($resourceName)['model'];
		$method = 'apiAction_' . $actionName;
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $actionName) || !method_exists($model, $method)) {
			throw new DataForceApiError("Unknown action '$actionName' on '$resourceName'", 404);
		}
		return $model->$method($params);
	}

	// ---------------------------------------------------------------- helpers

	private function assertWritable($resourceName)
	{
		$r = $this->resource($resourceName);
		if ($r['readOnly']) {
			throw new DataForceApiError("Resource '$resourceName' is read-only (\$API = 'ro')", 403);
		}
		return $r;
	}

	private function assertWritableColumn(array $fields, $col)
	{
		if (!isset($fields[$col])) {
			throw new DataForceApiError("Unknown column '$col'", 400);
		}
		if (!$fields[$col]['writable']) {
			throw new DataForceApiError("Column '$col' is not writable", 400);
		}
	}

	private function normalizeValue(array $fieldMeta, $val)
	{
		if ($val === null) {
			return null;
		}
		if (is_array($val) || is_object($val)) {
			throw new DataForceApiError('Scalar value expected for column', 400);
		}
		switch ($fieldMeta['jsonType']) {
			case 'integer': return (int)$val;
			case 'number':  return (float)$val;
			default:        return (string)$val;
		}
	}

	/** Legacy lifecycle hooks may echo HTML — swallow their output. */
	private function callHook($model, $method, array $args)
	{
		if (!method_exists($model, $method)) {
			return;
		}
		ob_start();
		try {
			call_user_func_array([$model, $method], $args);
		} catch (Exception $e) {
			error_log('DataForce API: hook ' . get_class($model) . "::$method failed: " . $e->getMessage());
		}
		ob_end_clean();
	}
}
