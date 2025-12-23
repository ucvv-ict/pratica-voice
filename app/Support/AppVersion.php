<?php

namespace App\Support;

class AppVersion
{
    public static function version(): string
    {
        $tag = trim((string) shell_exec('git describe --tags --abbrev=0 2>/dev/null'));
        $hash = trim((string) shell_exec('git rev-parse --short HEAD'));

        if (!empty($tag)) {
            return $tag;
        }

        return 'dev (' . $hash . ')';
    }
}
