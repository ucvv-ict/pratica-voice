<?php

namespace App\Support;

class AppVersion
{
    public static function version(): string
    {
        $tag = trim((string) shell_exec('git describe --tags --abbrev=0 2>/dev/null'));
        $hash = trim((string) shell_exec('git rev-parse --short HEAD'));

        if (!empty($tag)) {
            return self::normalizeTag($tag);
        }

        return 'dev (' . $hash . ')';
    }

    private static function normalizeTag(string $tag): string
    {
        return str_starts_with($tag, 'v') ? $tag : 'v' . $tag;
    }

    public static function commit(): string
    {
        return trim((string) shell_exec('git rev-parse --short HEAD'));
    }
}
