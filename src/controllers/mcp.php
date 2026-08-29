<?php

/**
 * DataForce MCP server — Model Context Protocol over Streamable HTTP.
 *
 * URL (via admin .htaccess rewrite):  POST /admin/mcp.php
 * Auth:                               Authorization: Bearer <api_token>
 *
 * Implements the stateless subset of the MCP spec (rev 2025-06-18):
 *   initialize, notifications/initialized, ping, tools/list, tools/call.
 * No SSE stream (GET → 405), no sessions (no Mcp-Session-Id) — every POST is
 * self-contained, which fits shared-hosting PHP. Compliant clients (Claude
 * Code, Codex, mcp-remote, n8n) handle a stateless server fine.
 *
 * Tools are generated from the same model registry as api.php:
 *   <resource>_list / _get / _create / _update / _delete   (CRUD)
 *   <resource>_schema                                       (introspection)
 *   <resource>_<action>                                     (apiAction_* methods)
 *
 * Client config example (.mcp.json):
 *   { "mcpServers": { "dietolog": {
 *       "type": "http", "url": "https://example.com/admin/mcp.php",
 *       "headers": { "Authorization": "Bearer <token>" } } } }
 */

require_once __DIR__ . '/../inc/Api.php';

define('DF_MCP_PROTOCOL_LATEST', '2025-06-18');

function dfMcpHttp($status, $body = null)
{
	http_response_code($status);
	if ($body !== null) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	exit;
}

function dfMcpResult($id, $result)
{
	dfMcpHttp(200, ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
}

function dfMcpError($id, $code, $message)
{
	dfMcpHttp(200, ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]]);
}

/** Wrap a PHP value as a tools/call result (text + structuredContent). */
function dfMcpToolResult($id, $value, $isError = false)
{
	$structured = is_array($value) ? $value : ['value' => $value];
	// structuredContent must be a JSON object
	if (array_values($structured) === $structured) {
		$structured = ['items' => $structured];
	}
	dfMcpResult($id, [
		'content' => [[
			'type' => 'text',
			'text' => json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
		]],
		'structuredContent' => $structured,
		'isError' => (bool)$isError,
	]);
}

// --------------------------------------------------------------------------

/** @var PDO $pdo        from inc/Connect.php (router already included it) */
/** @var array $dfConfig host config */
$__cfg = isset($dfConfig) && is_array($dfConfig) ? $dfConfig : (isset($GLOBALS['dfConfig']) ? $GLOBALS['dfConfig'] : []);
$api = new DataForceApi($pdo, $__cfg);

$__method = strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
if ($__method !== 'POST') {
	// No server-initiated SSE stream in this implementation
	header('Allow: POST');
	dfMcpHttp(405, ['error' => 'Method not allowed. MCP messages go via POST.']);
}

try {
	$api->authenticate();
} catch (DataForceApiError $e) {
	dfMcpHttp($e->httpStatus, ['error' => $e->getMessage()]);
}

$raw = file_get_contents('php://input');
$msg = json_decode($raw, true);
if (!is_array($msg)) {
	dfMcpError(null, -32700, 'Parse error: request body is not valid JSON');
}
if (isset($msg[0])) {
	// JSON-RPC batching was removed in MCP rev 2025-06-18
	dfMcpError(null, -32600, 'Batch requests are not supported');
}

$rpcMethod = isset($msg['method']) ? (string)$msg['method'] : '';
$rpcId     = array_key_exists('id', $msg) ? $msg['id'] : null;
$rpcParams = (isset($msg['params']) && is_array($msg['params'])) ? $msg['params'] : [];
$isNotification = !array_key_exists('id', $msg);

// Notifications (incl. notifications/initialized, notifications/cancelled):
// accept and acknowledge with 202, no body.
if ($isNotification) {
	dfMcpHttp(202);
}

try {
	switch ($rpcMethod) {
		case 'initialize':
			$requested = isset($rpcParams['protocolVersion']) ? (string)$rpcParams['protocolVersion'] : '';
			$supported = ['2025-06-18', '2025-03-26', '2024-11-05'];
			$version = in_array($requested, $supported, true) ? $requested : DF_MCP_PROTOCOL_LATEST;
			dfMcpResult($rpcId, [
				'protocolVersion' => $version,
				'capabilities'    => ['tools' => ['listChanged' => false]],
				'serverInfo'      => [
					'name'    => 'dataforce-cms',
					'title'   => 'DataForce CMS (' . (isset($__cfg['project_name']) ? $__cfg['project_name'] : 'site') . ')',
					'version' => DATAFORCE_API_VERSION,
				],
				'instructions' => 'Table-driven CMS admin API. Tools follow <resource>_<verb> naming. '
					. 'Call <resource>_schema first to learn a resource\'s columns.',
			]);
			break;

		case 'ping':
			dfMcpResult($rpcId, (object)[]);
			break;

		case 'tools/list':
			dfMcpResult($rpcId, ['tools' => dfMcpBuildTools($api)]);
			break;

		case 'tools/call':
			$tool = isset($rpcParams['name']) ? (string)$rpcParams['name'] : '';
			$args = (isset($rpcParams['arguments']) && is_array($rpcParams['arguments'])) ? $rpcParams['arguments'] : [];
			dfMcpDispatchTool($api, $rpcId, $tool, $args);
			break;

		default:
			dfMcpError($rpcId, -32601, "Method not found: $rpcMethod");
	}
} catch (DataForceApiError $e) {
	// Tool-level (execution) errors go back as isError results per spec
	dfMcpToolResult($rpcId, ['error' => $e->getMessage()], true);
} catch (PDOException $e) {
	error_log('DataForce MCP PDO error: ' . $e->getMessage());
	dfMcpToolResult($rpcId, ['error' => 'Database error'], true);
} catch (Exception $e) {
	error_log('DataForce MCP error: ' . $e->getMessage());
	dfMcpError($rpcId, -32603, 'Internal error');
}

// --------------------------------------------------------------------------

/** Build the tools/list payload from the opted-in model registry. */
function dfMcpBuildTools(DataForceApi $api)
{
	$tools = [];
	foreach ($api->resources() as $resource => $entry) {
		$model = $entry['model'];
		$label = isset($model->NAME) ? $model->NAME : $resource;
		$fields = $api->fields($model);

		// column properties for filters / writes
		$filterProps = [];
		$writeProps = [];
		foreach ($fields as $name => $meta) {
			$prop = ['type' => $meta['jsonType'], 'description' => $meta['label']];
			$filterProps[$name] = $prop;
			if ($meta['writable'] && !$entry['readOnly']) {
				$writeProps[$name] = $prop;
			}
		}

		$tools[] = [
			'name'        => $resource . '_schema',
			'description' => "Describe the '$resource' resource ($label): columns, types, writability, domain actions.",
			'inputSchema' => ['type' => 'object', 'properties' => (object)[], 'additionalProperties' => false],
		];
		$tools[] = [
			'name'        => $resource . '_list',
			'description' => "List '$resource' records ($label) with optional equality filters, pagination and sorting.",
			'inputSchema' => [
				'type' => 'object',
				'properties' => array_merge($filterProps, [
					'page'  => ['type' => 'integer', 'description' => 'Page number, 1-based (default 1)'],
					'limit' => ['type' => 'integer', 'description' => 'Records per page (default 50, max 500)'],
					'sort'  => ['type' => 'string', 'description' => 'Column to sort by (default id)'],
					'order' => ['type' => 'string', 'enum' => ['asc', 'desc'], 'description' => 'Sort direction (default desc)'],
				]),
				'additionalProperties' => false,
			],
		];
		$tools[] = [
			'name'        => $resource . '_get',
			'description' => "Fetch one '$resource' record ($label) by id.",
			'inputSchema' => [
				'type' => 'object',
				'properties' => ['id' => ['type' => 'integer', 'description' => 'Record id']],
				'required' => ['id'],
				'additionalProperties' => false,
			],
		];

		if (!$entry['readOnly']) {
			$tools[] = [
				'name'        => $resource . '_create',
				'description' => "Create a '$resource' record ($label). Unknown columns are rejected.",
				'inputSchema' => ['type' => 'object', 'properties' => $writeProps, 'additionalProperties' => false],
			];
			$tools[] = [
				'name'        => $resource . '_update',
				'description' => "Update columns of an existing '$resource' record ($label) by id.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => array_merge(['id' => ['type' => 'integer', 'description' => 'Record id']], $writeProps),
					'required' => ['id'],
					'additionalProperties' => false,
				],
			];
			$tools[] = [
				'name'        => $resource . '_delete',
				'description' => "Delete a '$resource' record ($label) by id. Rows with no_del=1 are protected.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => ['id' => ['type' => 'integer', 'description' => 'Record id']],
					'required' => ['id'],
					'additionalProperties' => false,
				],
			];
		}

		foreach ($api->actions($model) as $actionName => $info) {
			$tools[] = [
				'name'        => $resource . '_' . $actionName,
				'description' => $info['description'],
				'inputSchema' => [
					'type' => 'object',
					'properties' => is_array($info['input']) && $info['input'] ? $info['input'] : (object)[],
					'required' => $info['required'],
					'additionalProperties' => false,
				],
			];
		}
	}
	return $tools;
}

/** Route a tools/call to the Api core. */
function dfMcpDispatchTool(DataForceApi $api, $rpcId, $tool, array $args)
{
	if (!preg_match('/^([a-zA-Z0-9]+(?:_[a-zA-Z0-9]+)*)_(schema|list|get|create|update|delete)$/', $tool, $m)
		|| !dfMcpResourceExists($api, $m[1])
	) {
		// Not a CRUD verb — try <resource>_<domain action> (longest resource match)
		foreach (array_keys($api->resources()) as $resource) {
			$prefix = $resource . '_';
			if (strncmp($tool, $prefix, strlen($prefix)) === 0) {
				$action = substr($tool, strlen($prefix));
				dfMcpToolResult($rpcId, dfMcpWrap($api->callAction($resource, $action, $args)));
			}
		}
		dfMcpError($rpcId, -32602, "Unknown tool: $tool");
	}

	$resource = $m[1];
	$verb = $m[2];

	switch ($verb) {
		case 'schema':
			dfMcpToolResult($rpcId, $api->describe($resource));
			break;
		case 'list':
			$page  = isset($args['page']) ? $args['page'] : 1;
			$limit = isset($args['limit']) ? $args['limit'] : DataForceApi::DEFAULT_LIMIT;
			$sort  = isset($args['sort']) ? $args['sort'] : null;
			$order = isset($args['order']) ? $args['order'] : 'desc';
			unset($args['page'], $args['limit'], $args['sort'], $args['order']);
			dfMcpToolResult($rpcId, $api->listRows($resource, $args, $page, $limit, $sort, $order));
			break;
		case 'get':
			dfMcpToolResult($rpcId, $api->getRow($resource, dfMcpRequireId($args)));
			break;
		case 'create':
			dfMcpToolResult($rpcId, $api->createRow($resource, $args));
			break;
		case 'update':
			$id = dfMcpRequireId($args);
			unset($args['id']);
			dfMcpToolResult($rpcId, $api->updateRow($resource, $id, $args));
			break;
		case 'delete':
			dfMcpToolResult($rpcId, $api->deleteRow($resource, dfMcpRequireId($args)));
			break;
	}
}

function dfMcpResourceExists(DataForceApi $api, $name)
{
	$all = $api->resources();
	return isset($all[$name]);
}

function dfMcpRequireId(array $args)
{
	if (!isset($args['id']) || !is_numeric($args['id'])) {
		throw new DataForceApiError("Integer 'id' argument is required", 400);
	}
	return (int)$args['id'];
}

function dfMcpWrap($value)
{
	return is_array($value) ? $value : ['value' => $value];
}
