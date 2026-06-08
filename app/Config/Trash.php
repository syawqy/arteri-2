<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi fitur Sampah / Recovery (trash bin).
 */
class Trash extends BaseConfig
{
    /**
     * Lama masa pemulihan (hari) sebelum data di sampah dihapus permanen
     * oleh command `php spark trash:purge`. Dapat di-override via .env:
     * `trash.recoveryDays = 30`.
     */
    public int $recoveryDays = 30;
}
