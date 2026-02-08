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

function setPathValue(array &$root, string $path, $value): void
{
    // Strip leading "fields."
    $path = preg_replace('/^fields\./', '', $path);

    preg_match_all("/([A-Za-z0-9_ -]+|\['[^']+'\]|\[\d+\]|\[x\])/", $path, $m);
    $tokens = $m[0];

    $ref = &$root;

    while (count($tokens) > 1) {
        $token = array_shift($tokens);

        // ['Some Key']
        if (preg_match("/^\['(.+)'\]$/", $token, $mm)) {
            $key = $mm[1];
            if (! isset($ref[$key]) || ! is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
            continue;
        }

        // [0] or [x]
        if (preg_match("/^\[(\d+|x)\]$/", $token, $mm)) {
            if (! is_array($ref)) {
                $ref = [];
            }

            if ($mm[1] === 'x') {
                $ref[] = [];
                $ref   = &$ref[array_key_last($ref)];
            } else {
                $idx = (int) $mm[1];
                if (! isset($ref[$idx]) || ! is_array($ref[$idx])) {
                    $ref[$idx] = [];
                }
                $ref = &$ref[$idx];
            }
            continue;
        }

        // normal key
        if (! isset($ref[$token]) || ! is_array($ref[$token])) {
            $ref[$token] = [];
        }
        $ref = &$ref[$token];
    }

    // Final token → assign value
    $final = array_shift($tokens);

    if (preg_match("/^\['(.+)'\]$/", $final, $mm)) {
        $ref[$mm[1]] = $value;
    } elseif (preg_match("/^\[(\d+|x)\]$/", $final, $mm)) {
        if ($mm[1] === 'x') {
            $ref[] = $value;
        } else {
            $ref[(int) $mm[1]] = $value;
        }
    } else {
        $ref[$final] = $value;
    }
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

        setPathValue($fields, $field['target_path'], $value);
    }

    return $fields;
}
