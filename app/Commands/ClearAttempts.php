<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ClearAttempts extends BaseCommand
{
    protected $group       = 'Test';
    protected $name        = 'test:clear-attempts';
    protected $description = 'Truncate login_attempts table for e2e testing';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $db->query('TRUNCATE TABLE login_attempts');
        CLI::write('Cleared login_attempts', 'green');
    }
}
