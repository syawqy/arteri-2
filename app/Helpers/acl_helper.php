<?php

if (! function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return session('tipe') === 'admin';
    }
}

if (! function_exists('hasModuleAccess')) {
    function hasModuleAccess(string $module): bool
    {
        if (isAdmin()) {
            return true;
        }

        $aksesModul = session('akses_modul');

        if (! is_array($aksesModul)) {
            return false;
        }

        return ($aksesModul[$module] ?? 'off') === 'on';
    }
}

if (! function_exists('hasClassificationAccess')) {
    function hasClassificationAccess(string $kode): bool
    {
        if (isAdmin()) {
            return true;
        }

        $aksesKlas = session('akses_klas');

        // Empty means access to all
        if (empty($aksesKlas)) {
            return true;
        }

        // Check if the kode starts with any of the comma-separated prefixes
        $prefixes = explode(',', $aksesKlas);
        foreach ($prefixes as $prefix) {
            $prefix = trim($prefix);
            if ($prefix !== '' && str_starts_with(strtolower($kode), strtolower($prefix))) {
                return true;
            }
        }

        return false;
    }
}
