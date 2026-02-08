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

function tokenizePath(string $path): array
{
    preg_match_all(
        "/([A-Za-z0-9_-]+|\['[^']+'\]|\[\d+\]|\[x\])/",
        $path,
        $matches
    );

    return $matches[0];
}

function setPathValue(array &$root, array $tokens, $value): void
{
    $token = array_shift($tokens);

    // Leaf
    if ($token === null) {
        return;
    }

    // [x] fan-out
    if ($token === '[x]') {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $i => $v) {
            $idxToken = "[$i]";
            setPathValue($root, array_merge([$idxToken], $tokens), $v);
        }
        return;
    }

    // [0], [1], etc
    if (preg_match('/^\[(\d+)\]$/', $token, $m)) {
        $idx = (int) $m[1];
        if (! isset($root[$idx])) {
            $root[$idx] = [];
        }

        if (empty($tokens)) {
            $root[$idx] = $value;
            return;
        }

        setPathValue($root[$idx], $tokens, $value);
        return;
    }

    // ['Literal Key']
    if (preg_match("/^\['(.+)'\]$/", $token, $m)) {
        $key = $m[1];

        if (empty($tokens)) {
            $root[$key] = $value;
            return;
        }

        if (! isset($root[$key]) || ! is_array($root[$key])) {
            $root[$key] = [];
        }

        setPathValue($root[$key], $tokens, $value);
        return;
    }

    // Normal key
    $key = $token;

    if (empty($tokens)) {
        $root[$key] = $value;
        return;
    }

    if (! isset($root[$key]) || ! is_array($root[$key])) {
        $root[$key] = [];
    }

    setPathValue($root[$key], $tokens, $value);
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

        // Strip leading "fields."
        $path = preg_replace('/^fields\./', '', $field['target_path']);

        $tokens = tokenizePath($path);

        /*
         * WRITE RULES:
         * - [x] fans out arrays
         * - [0] assigns single index
         * - Scalars never receive arrays
         * - No hardcoding of Airtable semantics
         */

        setPathValue($fields, $tokens, $value);
    }

    return $fields;
}
