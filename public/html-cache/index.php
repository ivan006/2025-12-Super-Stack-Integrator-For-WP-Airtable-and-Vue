<?php
// Base directory = site root (one level above /html-cache)
$base = dirname(__DIR__);

/**
 * Recursively remove a directory
 */
function rrmdir($dir)
{
    if (!is_dir($dir))
        return;

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..')
            continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

/**
 * HANDLE ACTIONS
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $slug = trim($_POST['slug'] ?? '', '/');

    /**
     * BACKUP ROOT INDEX
     */
    if ($action === 'backup') {
        $file = $base . '/index.html';
        $backup = $base . '/index.backup.html';

        if (!file_exists($file)) {
            echo "ℹ️ No /index.html to backup.";
        } elseif (copy($file, $backup)) {
            echo "✅ Backup created at /index.backup.html";
        } else {
            echo "❌ Failed to create backup.";
        }
        exit;
    }

    /**
     * DELETE PAGE
     */
    if ($action === 'delete') {
        if ($slug === '') {
            // HOMEPAGE
            $file = $base . '/index.html';
            $backup = $base . '/index.backup.html';

            if (file_exists($backup)) {
                copy($backup, $file);
                echo "🔁 Restored /index.html from backup.";
            } elseif (file_exists($file)) {
                echo "ℹ️ /index.html exists but no backup available.";
            } else {
                echo "ℹ️ No /index.html found.";
            }
        } else {
            // SLUG PAGE
            $folder = $base . '/' . $slug;
            if (is_dir($folder)) {
                rrmdir($folder);
                echo "🗑️ Deleted /$slug folder.";
            } else {
                echo "ℹ️ No /$slug folder found.";
            }
        }
        exit;
    }

    /**
     * SAVE HTML
     */
    if ($action === 'save') {
        $html = base64_decode($_POST['html'] ?? '');

        if (!$html) {
            http_response_code(400);
            echo "❌ Missing HTML content.";
            exit;
        }

        $folder = $slug === '' ? $base : $base . '/' . $slug;
        $file = $folder . '/index.html';

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        // Remove JSON-LD
        $html = preg_replace(
            '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is',
            '',
            $html
        );

        // Remove canonical links
        $html = preg_replace(
            '/<link[^>]+rel=["\']canonical["\'][^>]*>/i',
            '',
            $html
        );

        // Remove meta SEO / social tags
        $html = preg_replace(
            '/<meta[^>]+name=["\'](description|twitter:[^"\']+)["\'][^>]*>/i',
            '',
            $html
        );
        $html = preg_replace(
            '/<meta[^>]+property=["\']og:[^"\']+["\'][^>]*>/i',
            '',
            $html
        );

        // Remove Google Tag Manager
        $html = preg_replace(
            '/<script[^>]+src=["\']https:\/\/www\.googletagmanager\.com\/gtm\.js[^"\']*["\'][^>]*>\s*<\/script>/i',
            '',
            $html
        );

        file_put_contents($file, $html);

        echo "✅ Saved to " . ($slug === '' ? '/index.html' : "/$slug/index.html");
        exit;
    }

    http_response_code(400);
    echo "❌ Unknown action.";
    exit;
}
