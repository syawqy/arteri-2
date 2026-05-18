<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\MasterKodeModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;

/**
 * REST API Controller for Master Data operations.
 */
class MasterDataController extends BaseApiController
{
    private array $models = [];

    public function __construct()
    {
        $this->models = [
            'kode'    => new MasterKodeModel(),
            'pencipta'=> new MasterPenciptaModel(),
            'pengolah'=> new MasterPengolahModel(),
            'lokasi'  => new MasterLokasiModel(),
            'media'   => new MasterMediaModel(),
        ];
    }

    /**
     * GET /api/master/{type}
     * Get all records for a master data type.
     *
     * Types: kode, pencipta, pengolah, lokasi, media
     */
    public function index(string $type): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        if (! isset($this->models[$type])) {
            return $this->errorResponse(
                'Invalid master data type. Valid types: ' . implode(', ', array_keys($this->models)),
                self::HTTP_BAD_REQUEST
            );
        }

        $model = $this->models[$type];
        $orderField = match($type) {
            'kode'     => 'kode',
            'pencipta' => 'nama_pencipta',
            'pengolah' => 'nama_pengolah',
            'lokasi'   => 'nama_lokasi',
            'media'    => 'nama_media',
            default    => 'id',
        };

        $data = $model->orderBy($orderField, 'ASC')->findAll();

        return $this->successResponse($data, 'Master data retrieved successfully');
    }

    /**
     * GET /api/master/{type}/{id}
     * Get a single master data record.
     */
    public function show(string $type, int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        if (! isset($this->models[$type])) {
            return $this->errorResponse(
                'Invalid master data type',
                self::HTTP_BAD_REQUEST
            );
        }

        $record = $this->models[$type]->find($id);

        if ($record === null) {
            return $this->errorResponse(
                'Record not found',
                self::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse($record, 'Record retrieved successfully');
    }

    /**
     * POST /api/master/{type}
     * Create a new master data record.
     */
    public function create(string $type): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        if (! isset($this->models[$type])) {
            return $this->errorResponse(
                'Invalid master data type',
                self::HTTP_BAD_REQUEST
            );
        }

        $data = $this->request->getJSON(true);

        if (empty($data)) {
            return $this->errorResponse(
                'Request body is required',
                self::HTTP_BAD_REQUEST
            );
        }

        // Validate based on type
        $required = match($type) {
            'kode'     => ['kode', 'nama'],
            'pencipta'=> ['nama_pencipta'],
            'pengolah'=> ['nama_pengolah'],
            'lokasi'  => ['nama_lokasi'],
            'media'   => ['nama_media'],
            default   => [],
        };

        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            return $this->errorResponse(
                'Missing required fields: ' . implode(', ', $missing),
                self::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $id = $this->models[$type]->insert($data);

            if ($id === false) {
                return $this->errorResponse(
                    'Failed to create record',
                    self::HTTP_INTERNAL_ERROR,
                    $this->models[$type]->errors()
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Record created successfully',
                self::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create record: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * PUT /api/master/{type}/{id}
     * Update a master data record.
     */
    public function update(string $type, int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        if (! isset($this->models[$type])) {
            return $this->errorResponse(
                'Invalid master data type',
                self::HTTP_BAD_REQUEST
            );
        }

        $record = $this->models[$type]->find($id);

        if ($record === null) {
            return $this->errorResponse(
                'Record not found',
                self::HTTP_NOT_FOUND
            );
        }

        $data = $this->request->getJSON(true);

        if (empty($data)) {
            return $this->errorResponse(
                'Request body is required',
                self::HTTP_BAD_REQUEST
            );
        }

        // Remove id from update data
        unset($data['id']);

        try {
            $updated = $this->models[$type]->update($id, $data);

            if ($updated === false) {
                return $this->errorResponse(
                    'Failed to update record',
                    self::HTTP_INTERNAL_ERROR,
                    $this->models[$type]->errors()
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Record updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update record: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * DELETE /api/master/{type}/{id}
     * Delete a master data record.
     */
    public function delete(string $type, int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        if (! isset($this->models[$type])) {
            return $this->errorResponse(
                'Invalid master data type',
                self::HTTP_BAD_REQUEST
            );
        }

        $record = $this->models[$type]->find($id);

        if ($record === null) {
            return $this->errorResponse(
                'Record not found',
                self::HTTP_NOT_FOUND
            );
        }

        try {
            $deleted = $this->models[$type]->delete($id);

            if ($deleted === false) {
                return $this->errorResponse(
                    'Failed to delete record',
                    self::HTTP_INTERNAL_ERROR
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Record deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete record: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }
}