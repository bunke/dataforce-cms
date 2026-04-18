<?php
/**
 * Tiny zero-dependency .env loader.
 *
 * Populates $_ENV, $_SERVER and putenv() for every KEY=VALUE line.
 * Existing env values are NEVER overwritten, so hosting-provided env wins
 * over the file (standard convention).
 */

namespace DataForce;

/**
 * @param string $path absolute path to the .env file
 * @return bool true if file was loaded, false if it didn't exist
 */
function loadEnv($path)
{
    if (!is_file($path) || !is_readable($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false)    continue;

        list($key, $value) = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) continue;

        // Strip matching surrounding quotes
        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0]; $last = $value[$len - 1];
            if (($first === '"' || $first === "'") && $first === $last) {
                $value = substr($value, 1, -1);
                if ($first === '"') {
                    $value = str_replace(['\\n', '\\r', '\\t', '\\"', '\\\\'],
                                         ["\n", "\r", "\t", '"', '\\'], $value);
                }
            }
        }

        // Don't overwrite real env (CI/hosting providers set these)
        if (getenv($key) !== false) continue;

        putenv("$key=$value");
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }

    return true;
}
