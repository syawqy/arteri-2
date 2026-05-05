<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        \App\Validation\CustomRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    public array $login = [
        'username' => 'required',
        'password' => 'required',
    ];

    public array $arsipCreate = [
        'noarsip'      => 'required|max_length[100]|is_unique[data_arsip.noarsip]',
        'tanggal'      => 'required|valid_date[Y-m-d]',
        'pencipta'     => 'required|integer|valid_fk[master_pencipta,id]',
        'unitpengolah' => 'required|integer|valid_fk[master_pengolah,id]',
        'kode'         => 'required|integer|valid_fk[master_kode,id]',
        'uraian'       => 'required',
        'lokasi'       => 'required|integer|valid_fk[master_lokasi,id]',
        'media'        => 'required|integer|valid_fk[master_media,id]',
        'ket'          => 'required|in_list[asli,copy]',
        'jumlah'       => 'required|integer|greater_than[0]',
        'nobox'        => 'permit_empty|max_length[10]',
        'file'         => 'permit_empty|uploaded[file]|ext_in[file,pdf,doc,docx]|max_size[file,10240]',
    ];

    public array $arsipUpdate = [
        'noarsip'      => 'required|max_length[100]|is_unique[data_arsip.noarsip,id,{id}]',
        'tanggal'      => 'required|valid_date[Y-m-d]',
        'pencipta'     => 'required|integer|valid_fk[master_pencipta,id]',
        'unitpengolah' => 'required|integer|valid_fk[master_pengolah,id]',
        'kode'         => 'required|integer|valid_fk[master_kode,id]',
        'uraian'       => 'required',
        'lokasi'       => 'required|integer|valid_fk[master_lokasi,id]',
        'media'        => 'required|integer|valid_fk[master_media,id]',
        'ket'          => 'required|in_list[asli,copy]',
        'jumlah'       => 'required|integer|greater_than[0]',
        'nobox'        => 'permit_empty|max_length[10]',
        'file'         => 'permit_empty|uploaded[file]|ext_in[file,pdf,doc,docx]|max_size[file,10240]',
    ];

    public array $sirkulasiCreate = [
        'noarsip'           => 'required|max_length[255]',
        'username_peminjam' => 'required|max_length[255]',
        'keperluan'         => 'required',
        'tgl_pinjam'        => 'required|valid_date[Y-m-d]',
        'tgl_haruskembali'  => 'required|valid_date[Y-m-d]|valid_date_range[tgl_pinjam,tgl_haruskembali]',
    ];

    public array $userCreate = [
        'username'      => 'required|alpha_numeric|min_length[3]|is_unique[master_user.username]',
        'password'      => 'required|min_length[8]|valid_password_strength',
        'conf_password' => 'required|matches[password]',
        'tipe'          => 'required|in_list[admin,user]',
    ];

    public array $userUpdate = [
        'username'      => 'required|alpha_numeric|min_length[3]|is_unique[master_user.username,id,{id}]',
        'password'      => 'permit_empty|min_length[8]|valid_password_strength',
        'conf_password' => 'permit_empty|matches[password]',
        'tipe'          => 'required|in_list[admin,user]',
    ];

    public array $importFile = [
        'excel_file' => 'required|uploaded[excel_file]|ext_in[excel_file,xlsx,xls,csv]|max_size[excel_file,20480]',
    ];
}
