<?php

namespace App\Validation;

use CodeIgniter\Database\BaseConnection;

class CustomRules
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function valid_fk(string $value, string $params, array $data, ?string &$error = null): bool
    {
        if ($value === '' || $value === null) {
            return true;
        }

        $parts = explode(',', $params);
        $table = trim($parts[0]);
        $field = trim($parts[1] ?? 'id');

        $exists = $this->db->table($table)->where($field, $value)->countAllResults() > 0;

        if (! $exists) {
            return false;
        }
        return true;
    }

    public function valid_date_range(string $value, string $params, array $data, ?string &$error = null): bool
    {
        $parts = explode(',', $params);
        $startField = trim($parts[0]);
        $endField   = trim($parts[1] ?? $startField);

        $startDate = $data[$startField] ?? null;
        $endDate   = $data[$endField] ?? null;

        if ($startDate === null || $endDate === null || $startDate === '' || $endDate === '') {
            return true;
        }

        if ($endDate < $startDate) {
            return false;
        }
        return true;
    }

    public function valid_password_strength(string $value, ?string &$error = null): bool
    {
        if (mb_strlen($value) < 8) {
            return false;
        }

        if (! preg_match('/[a-zA-Z]/', $value)) {
            return false;
        }

        if (! preg_match('/[0-9]/', $value)) {
            return false;
        }

        return true;
    }
}
