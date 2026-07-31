<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Services\AccessGuard;
use App\Models\{ArsipModel, MasterKodeModel, MasterLokasiModel, MasterMediaModel};
use PhpMcp\Server\Attributes\{McpResource, McpResourceTemplate};

class ArsipResource
{
    private AccessGuard $accessGuard;

    public function __construct()
    {
        $this->accessGuard = new AccessGuard();
    }

    /**
     * Get a specific archive record.
     */
    #[McpResourceTemplate(
        uriTemplate: 'arteri://arsip/{arsipId}',
        mimeType: 'application/json'
    )]
    public function getArsip(string $arsipId): array
    {
        $arsipModel = new ArsipModel();
        $arsip = $arsipModel->getDetail((int) $arsipId);
        if (!$arsip) return ['error' => 'Arsip not found'];
        if (!$this->accessGuard->canAccessArsip($arsip)) return ['error' => 'Access denied'];
        return $arsip;
    }

    /**
     * List all classifications.
     */
    #[McpResource(
        uri: 'arteri://master/klasifikasi',
        mimeType: 'application/json'
    )]
    public function getKlasifikasi(): array
    {
        return (new MasterKodeModel())->where('deleted_at', null)->findAll();
    }

    /**
     * List all locations.
     */
    #[McpResource(
        uri: 'arteri://master/lokasi',
        mimeType: 'application/json'
    )]
    public function getLokasi(): array
    {
        return (new MasterLokasiModel())->where('deleted_at', null)->findAll();
    }

    /**
     * List all media types.
     */
    #[McpResource(
        uri: 'arteri://master/media',
        mimeType: 'application/json'
    )]
    public function getMedia(): array
    {
        return (new MasterMediaModel())->where('deleted_at', null)->findAll();
    }
}
