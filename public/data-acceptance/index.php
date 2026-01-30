<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

require_once __DIR__ . '/../data-cache/CurlClient.php';
require_once __DIR__ . '/../data-cache/helpers.php';



header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

/**
 * -------------------------------------------------
 * Load configs
 * -------------------------------------------------
 */
$configPath = __DIR__ . '/config.json';
$envPath = __DIR__ . '/env.json';

if (!file_exists($configPath)) {
  http_response_code(500);
  echo json_encode(['error' => 'Missing config.json']);
  exit;
}

if (!file_exists($envPath)) {
  http_response_code(500);
  echo json_encode(['error' => 'Missing env.json']);
  exit;
}

$config = json_decode(file_get_contents($configPath), true);
$env = json_decode(file_get_contents($envPath), true);

/**
 * -------------------------------------------------
 * Routing
 * -------------------------------------------------
 */
$endpoint = $_GET['endpoint'] ?? null;

/**
 * -------------------------------------------------
 * CONFIGS FETCH
 * -------------------------------------------------
 */
if ($endpoint === 'configs-fetch') {
  echo json_encode([
    'entities' => $env['entities'] ?? []
  ], JSON_PRETTY_PRINT);
  exit;
}

/**
 * -------------------------------------------------
 * SOURCE FETCH (WordPress)
 * -------------------------------------------------
 */ elseif ($endpoint === 'source-fetch') {

  $entity = $_GET['entity'] ?? null;
  $id = $_GET['id'] ?? null;

  if (!$entity || !$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing entity or id']);
    exit;
  }

  // Validate source entity
  $allowed = false;
  foreach ($env['entities'] as $e) {
    if (($e['source_entity_name'] ?? null) === $entity) {
      $allowed = true;
      break;
    }
  }

  if (!$allowed) {
    http_response_code(404);
    echo json_encode(['error' => 'Source entity not allowed']);
    exit;
  }

  $baseUrl = rtrim($env['source']['base_url'], '/');
  $url = $baseUrl . '/' . rawurlencode($entity) . '/' . rawurlencode($id);

  $client = new CurlClient(false);
  $bodyStream = fopen('php://temp', 'w+');

  $info = $client->get($url, [], $bodyStream);

  if (!$info || ($info['http_code'] ?? 500) >= 400) {
    http_response_code(502);
    echo json_encode([
      'error' => 'Failed to fetch source record',
      'http_code' => $info['http_code'] ?? null
    ], JSON_PRETTY_PRINT);
    exit;
  }

  rewind($bodyStream);
  $data = json_decode(stream_get_contents($bodyStream), true);
  fclose($bodyStream);

  // Find entity mapping
  $entityMap = null;
  foreach ($env['entities'] as $e) {
    if (($e['source_entity_name'] ?? null) === $entity) {
      $entityMap = $e;
      break;
    }
  }

  $normData = $entityMap
    ? normalizeStructure($data, $entityMap, 'source')
    : [];

  echo json_encode([
    'system' => 'source',
    'entity' => $entity,
    'id' => $id,
    'norm_data' => $normData,
    'raw_data' => $data
  ], JSON_PRETTY_PRINT);
  exit;

}

/**
 * -------------------------------------------------
 * TARGET FETCH (Airtable)
 * -------------------------------------------------
 */ elseif ($endpoint === 'target-fetch') {

  $entity = $_GET['entity'] ?? null;
  $id = $_GET['id'] ?? null;

  if (!$entity || !$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing entity or id']);
    exit;
  }

  // Validate target entity via env.json
  $entityMap = null;
  foreach ($env['entities'] as $e) {
    if (($e['target_entity_name'] ?? null) === $entity) {
      $entityMap = $e;
      break;
    }
  }

  if (!$entityMap) {
    http_response_code(404);
    echo json_encode(['error' => 'Entity not allowed']);
    exit;
  }

  // Build Airtable URL
  $baseUrl = rtrim($env['target']['base_url'], '/');
  $baseId = $env['target']['base_id'];
  $table = $entityMap['target_entity_name'];

  $url =
    $baseUrl . '/' .
    rawurlencode($baseId) . '/' .
    rawurlencode($table) . '/' .
    rawurlencode($id);

  // Inject auth headers (host-based)
  $host = parse_url($url, PHP_URL_HOST);
  if (!isset($config[$host]['headers'])) {
    http_response_code(500);
    echo json_encode(['error' => 'No auth config for host']);
    exit;
  }

  $headers = [];
  foreach ($config[$host]['headers'] as $k => $v) {
    $headers[] = $k . ': ' . $v;
  }

  // Fetch
  $client = new CurlClient(false);
  $bodyStream = fopen('php://temp', 'w+');

  $info = $client->get($url, $headers, $bodyStream);

  rewind($bodyStream);
  $raw = stream_get_contents($bodyStream);
  fclose($bodyStream);

  if (!$info || ($info['http_code'] ?? 500) >= 400) {
    http_response_code(502);
    echo json_encode([
      'error' => 'Failed to fetch target record',
      'http_code' => $info['http_code'] ?? null,
      'attempted_url' => $url
    ], JSON_PRETTY_PRINT);
    exit;
  }

  $data = json_decode($raw, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
      'error' => 'JSON decode failed',
      'json_error' => json_last_error_msg(),
      'raw_body' => $raw
    ], JSON_PRETTY_PRINT);
    exit;
  }

  $normData = normalizeStructure($data, $entityMap, 'target');

  echo json_encode([
    'system' => 'target',
    'entity' => $entity,
    'id' => $id,
    'norm_data' => $normData,
    'raw_data' => $data
  ], JSON_PRETTY_PRINT);
  exit;

}


/**
 * -------------------------------------------------
 * FALLBACK
 * -------------------------------------------------
 */ else {
  http_response_code(404);
  echo json_encode(['error' => 'Unknown endpoint']);
  exit;
}
