<?php

function readConfig($url)
{
    $configFile = __DIR__ . '/config.json';

    if (! file_exists($configFile)) {
        return null;
    }

    $configs = json_decode(file_get_contents($configFile), true);
    $host    = parse_url($url, PHP_URL_HOST);

    return isset($configs[$host]) ? $configs[$host] : null;
}

function buildFilePath($method, $url)
{
    $parts = parse_url($url);

    if (! in_array($parts['scheme'], ['http', 'https'])) {
        throw new Exception('URL must be HTTP or HTTPS');
    }

    $host = $parts['host'];
    if (isset($parts['port'])) {
        $host .= '-' . $parts['port'];
    }

    $host = preg_replace('/[^\w\.]/', '-', $host);
    $dir  = __DIR__ . '/cache/' . $host . '/' . $method;

    if (! file_exists($dir)) {
        mkdir($dir, 0700, true);
    }

    if ($method === 'POST') {
        $url .= file_get_contents('php://input');
    }

    return $dir . '/' . hash('sha256', $url);
}

/**
 * -------------------------------------------------
 * Path Resolver (structural only)
 * -------------------------------------------------
 */

function resolvePath($data, $path)
{
    if ($path === null || $path === '') {
        return null;
    }

    // Split on dots, but keep bracket tokens
    preg_match_all("/([A-Za-z0-9_-]+|\['[^']+'\]|\[x\])/", $path, $matches);

    $tokens = $matches[0];

    return walkTokens($data, $tokens);
}

function walkTokens($current, $tokens)
{
    if ($current === null) {
        return null;
    }

    if (empty($tokens)) {
        return $current;
    }

    $token = array_shift($tokens);

    // Array mapper
    if ($token === '[x]') {
        if (! is_array($current)) {
            return [];
        }

        $out = [];
        foreach ($current as $item) {
            $val = walkTokens($item, $tokens);
            if ($val !== null) {
                $out[] = $val;
            }

        }
        return $out;
    }

    // Literal key ['Some Key']
    if (preg_match("/^\['(.+)'\]$/", $token, $m)) {
        return walkTokens($current[$m[1]] ?? null, $tokens);
    }

    // Normal object key
    return walkTokens($current[$token] ?? null, $tokens);
}

/**
 * -------------------------------------------------
 * Structural Normalization
 * -------------------------------------------------
 */

function normalizeStructure($rawData, $entityMap, $system)
{
    $norm = [];

    foreach ($entityMap['fields'] as $field) {
        $pathKey = $system === 'source' ? 'source_path' : 'target_path';
        if (! isset($field[$pathKey])) {
            continue;
        }

        $value = resolvePath($rawData, $field[$pathKey]);

        // Enforce empty array for [x] paths
        if (str_contains($field[$pathKey], '[x]') && $value === null) {
            $value = [];
        }

        $norm[$field['norm_name']] = $value;
    }

    return $norm;
}

/**
 * -------------------------------------------------
 * Target Payload Builder (norm → Airtable fields)
 * -------------------------------------------------
 */
function buildTargetPayloadFromNorm(array $normData, array $entityMap): array
{
    $fields = [];

    foreach ($entityMap['fields'] as $field) {
        if (! isset($field['norm_name'], $field['target_path'])) {
            continue;
        }

        $normName = $field['norm_name'];

        if (! array_key_exists($normName, $normData)) {
            continue;
        }

        $value = $normData[$normName];

        if ($value === null) {
            continue;
        }

        // Extract Airtable field name from target_path
        // Supports: fields.Name  OR  fields['Some Name']
        if (preg_match("/^fields\.([A-Za-z0-9_]+)$/", $field['target_path'], $m)) {
            $airtableField = $m[1];
        } elseif (preg_match("/^fields\['(.+)'\]$/", $field['target_path'], $m)) {
            $airtableField = $m[1];
        } else {
            // unsupported write path – skip silently
            continue;
        }

        $fields[$airtableField] = $value;
    }

    return $fields;
}
