<?php

/**
 * DataForce JSON API — thin HTTP adapter over inc/Api.php.
 *
 * URL (via admin .htaccess rewrite):  /admin/api.php
 * Auth:                               Authorization: Bearer <api_token>
 *
 *   GET    api.php                                → discovery (resources + schemas)
 *   GET    api.php?resource=users                 → list (page, limit, sort, order,
 *                                                    plus any column=value filter)
 *   GET    api.php?resource=users&id=42           → single record
 *   GET    api.php?resource=users&action=schema   → field metadata
 *   POST   api.php?resource=users        {json}   → create
 *   PATCH  api.php?resource=users&id=42  {json}   → update (PUT accepted too)
 *   DELETE api.php?resource=users&id=42           → delete
 *   POST   api.php?resource=users&action=<name> {json} → domain action
 *
 * Envelope: {"ok":true,"data":...,"meta":{...}} | {"ok":false,"error":{...}}
 */

require_once __DIR__ . '/../inc/Api.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function dfApiRespond($status, array $body)
{
	http_response_code($status);
	echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

function dfApiOk($data, $meta = null)
{
	$body = ['ok' => true, 'data' => $data];
	if ($meta !== null) {
		$body['meta'] = $meta;
	}
	dfApiRespond(200, $body);
}

function dfApiFail($status, $message)
{
	dfApiRespond($status, ['ok' => false, 'error' => ['code' => $status, 'message' => $message]]);
}

/** @var PDO $pdo   from inc/Connect.php (router already included it) */
/** @var array $dfConfig  host config (vendor mode) or config.php globals */
$__cfg = isset($dfConfig) && is_array($dfConfig) ? $dfConfig : (isset($GLOBALS['dfConfig']) ? $GLOBALS['dfConfig'] : []);

try {
	$api = new DataForceApi($pdo, $__cfg);
	$api->authenticate();

	$method   = strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
	$resource = isset($_GET['resource']) ? (string)$_GET['resource'] : '';
	$id       = isset($_GET['id']) ? (int)$_GET['id'] : null;
	$action   = isset($_GET['action']) ? (string)$_GET['action'] : '';

	// JSON body for write methods
	$payload = [];
	if (in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
		$raw = file_get_contents('php://input');
		if ($raw !== '' && $raw !== false) {
			$payload = json_decode($raw, true);
			if (!is_array($payload)) {
				dfApiFail(400, 'Request body must be a JSON object');
			}
		}
	}

	// ---- discovery: GET api.php ------------------------------------------
	if ($resource === '') {
		if ($method !== 'GET') {
			dfApiFail(405, 'Use GET for discovery');
		}
		$list = [];
		foreach (array_keys($api->resources()) as $name) {
			$list[] = $api->describe($name);
		}
		dfApiOk([
			'name'      => 'DataForce CMS JSON API',
			'version'   => DATAFORCE_API_VERSION,
			'mcp'       => 'POST mcp.php (same Bearer token, MCP Streamable HTTP)',
			'resources' => $list,
		]);
	}

	// ---- schema ------------------------------------------------------------
	if ($action === 'schema') {
		dfApiOk($api->describe($resource));
	}

	// ---- domain actions ------------------------------------------------------
	if ($action !== '') {
		if ($method !== 'POST') {
			dfApiFail(405, 'Domain actions require POST');
		}
		dfApiOk($api->callAction($resource, $action, $payload));
	}

	// ---- CRUD ----------------------------------------------------------------
	switch ($method) {
		case 'GET':
			if ($id !== null) {
				dfApiOk($api->getRow($resource, $id));
			}
			// every non-reserved query param that names a column is a filter
			$reserved = ['resource', 'id', 'action', 'page', 'limit', 'sort', 'order', 'inc'];
			$filters = [];
			foreach ($_GET as $k => $v) {
				if (!in_array($k, $reserved, true) && is_scalar($v)) {
					$filters[$k] = $v;
				}
			}
			$res = $api->listRows(
				$resource,
				$filters,
				isset($_GET['page']) ? $_GET['page'] : 1,
				isset($_GET['limit']) ? $_GET['limit'] : DataForceApi::DEFAULT_LIMIT,
				isset($_GET['sort']) ? $_GET['sort'] : null,
				isset($_GET['order']) ? $_GET['order'] : 'desc'
			);
			dfApiOk($res['items'], $res['meta']);
			break;

		case 'POST':
			dfApiRespond(201, ['ok' => true, 'data' => $api->createRow($resource, $payload)]);
			break;

		case 'PATCH':
		case 'PUT':
			if ($id === null) {
				dfApiFail(400, 'id query parameter is required for update');
			}
			dfApiOk($api->updateRow($resource, $id, $payload));
			break;

		case 'DELETE':
			if ($id === null) {
				dfApiFail(400, 'id query parameter is required for delete');
			}
			dfApiOk($api->deleteRow($resource, $id));
			break;

		default:
			dfApiFail(405, "Method $method not supported");
	}
} catch (DataForceApiError $e) {
	dfApiFail($e->httpStatus, $e->getMessage());
} catch (PDOException $e) {
	error_log('DataForce API PDO error: ' . $e->getMessage());
	dfApiFail(500, 'Database error');
} catch (Exception $e) {
	error_log('DataForce API error: ' . $e->getMessage());
	dfApiFail(500, 'Internal error');
}
