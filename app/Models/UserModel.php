<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'master_user';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $allowedFields    = [
        'username',
        'password',
        'tipe',
        'akses_klas',
        'akses_modul',
        'deleted_at',
    ];
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    /**
     * Attempt to authenticate a user.
     *
     * @param string $username
     * @param string $password
     * @return array|null User record on success, null on failure.
     */
    public function attemptLogin(string $username, string $password): ?array
    {
        $user = $this->where('username', $username)->first();

        if ($user === null) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        return $user;
    }
}
