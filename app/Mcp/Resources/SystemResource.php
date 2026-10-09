<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Models\{ArsipModel, SirkulasiModel, UserModel};
use PhpMcp\Server\Attributes\McpResource;

class SystemResource
{
    /**
     * Get Arteri system information.
     */
    #[McpResource(
        uri: 'arteri://system/info',
        mimeType: 'application/json'
    )]
    public function getSystemInfo(): array
    {
        return [
            'name'        => 'Arteri 2',
            'version'     => '2.0.0',
            'description' => 'Sistem Pengelola Arsip Digital',
            'mcp_version' => '1.0.0',
            'capabilities' => [
                'agents' => [
                    'ingestion'      => 'Document ingestion, OCR, metadata extraction',
                    'classification' => 'Archive classification and retention scheduling',
                    'search'         => 'Natural language archive search',
                    'retention'      => 'Retention monitoring and disposition management',
                    'compliance'     => 'Audit trail, compliance checking, access monitoring',
                    'migration'      => 'Data migration from various sources',
                ],
            ],
            'modules' => ['arsip', 'sirkulasi', 'master', 'user', 'trash', 'report', 'audit', 'import'],
        ];
    }

    /**
     * Get system statistics.
     */
    #[McpResource(
        uri: 'arteri://system/stats',
        mimeType: 'application/json'
    )]
    public function getSystemStats(): array
    {
        return [
            'total_arsip'      => (new ArsipModel())->countAllResults(),
            'active_sirkulasi' => (new SirkulasiModel())->where('tgl_pengembalian', null)->countAllResults(),
            'total_users'      => (new UserModel())->where('deleted_at', null)->countAllResults(),
            'last_updated'     => date('Y-m-d H:i:s'),
        ];
    }
}
