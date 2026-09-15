<?php
declare(strict_types=1);

/**
 * Reads secrets from real server environment variables first, then from a
 * DirectAdmin-friendly private file outside public_html.
 */
function configValue(string $key, string $default = ''): string
{
    $environmentValue = getenv($key);
    if ($environmentValue !== false && trim((string) $environmentValue) !== '') {
        return trim((string) $environmentValue);
    }

    static $privateConfig;
    if ($privateConfig === null) {
        $configPath = dirname(__DIR__, 2) . '/boka-config.php';
        $privateConfig = is_file($configPath) ? require $configPath : [];
        if (!is_array($privateConfig)) $privateConfig = [];
    }

    return isset($privateConfig[$key]) ? trim((string) $privateConfig[$key]) : $default;
}
