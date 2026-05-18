<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\SirkulasiModel;
use App\Models\ArsipModel;

/**
 * REST API Controller for Sirkulasi (Circulation) operations.
 */
class SirkulasiController extends BaseApiController
{
    private SirkulasiModel $sirkulasiModel;
    private ArsipModel $arsipModel;

    public function __construct()
    {
        $this->sirkulasiModel = new SirkulasiModel();
        $this->arsipModel     = new ArsipModel();
    }

    /**
     * GET /api/sirkulasi
     * List all circulation records with cursor-based pagination.
     *
     * Query params:
     *   - cursor: int (optional)
     *   - limit: int (optional, default 20, max 100)
     *   - keywords: string (optional)
     */
    public function index(): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        $cursor   = $this->request->getGet('cursor') !== null
            ? (int) $this->request->getGet('cursor')
            : null;
        $limit    = min((int) ($this->request->getGet('limit') ?? 20), 100);
        $keywords = $this->request->getGet('katakunci') ?? '';

        $result = $this->sirkulasiModel->searchWithCursor(
            $cursor,
            $keywords,
            $limit
        );

        return $this->paginatedResponse(
            $result['records'],
            $result['next_cursor'],
            $result['has_more'],
            'Circulation records retrieved successfully'
        );
    }

    /**
     * GET /api/sirkulasi/{id}
     * Get a single circulation record by ID.
     */
    public function show(int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        $record = $this->sirkulasiModel->find($id);

        if ($record === null) {
            return $this->errorResponse(
                'Circulation record not found',
                self::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse($record, 'Circulation record retrieved successfully');
    }

    /**
     * POST /api/sirkulasi
     * Create a new circulation record (borrow).
     */
    public function create(): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        $data = $this->request->getJSON(true);

        if (empty($data)) {
            return $this->errorResponse(
                'Request body is required',
                self::HTTP_BAD_REQUEST
            );
        }

        // Validate required fields
        $required = ['noarsip', 'username_peminjam', 'tgl_haruskembali'];
        $missing  = [];

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

        // Verify archive exists
        $arsip = $this->arsipModel->where('noarsip', $data['noarsip'])->first();
        if ($arsip === null) {
            return $this->errorResponse(
                'Archive with this noarsip not found',
                self::HTTP_NOT_FOUND
            );
        }

        // Set transaction timestamp
        $data['tgl_pinjam']     = date('Y-m-d H:i:s');
        $data['tgl_transaksi']  = date('Y-m-d H:i:s');

        try {
            $id = $this->sirkulasiModel->insert($data);

            if ($id === false) {
                return $this->errorResponse(
                    'Failed to create circulation record',
                    self::HTTP_INTERNAL_ERROR,
                    $this->sirkulasiModel->errors()
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Circulation record created successfully',
                self::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create circulation record: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * POST /api/sirkulasi/{id}/return
     * Mark a circulation record as returned.
     */
    public function return(int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        $record = $this->sirkulasiModel->find($id);

        if ($record === null) {
            return $this->errorResponse(
                'Circulation record not found',
                self::HTTP_NOT_FOUND
            );
        }

        // Already returned
        if (! empty($record['tgl_pengembalian'])) {
            return $this->errorResponse(
                'Archive already returned',
                self::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $result = $this->sirkulasiModel->returnArchive($id);

            if ($result === false) {
                return $this->errorResponse(
                    'Failed to return archive',
                    self::HTTP_INTERNAL_ERROR,
                    $this->sirkulasiModel->errors()
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Archive returned successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to return archive: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * DELETE /api/sirkulasi/{id}
     * Delete a circulation record.
     */
    public function delete(int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        $record = $this->sirkulasiModel->find($id);

        if ($record === null) {
            return $this->errorResponse(
                'Circulation record not found',
                self::HTTP_NOT_FOUND
            );
        }

        try {
            $deleted = $this->sirkulasiModel->delete($id);

            if ($deleted === false) {
                return $this->errorResponse(
                    'Failed to delete circulation record',
                    self::HTTP_INTERNAL_ERROR
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Circulation record deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete circulation record: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }
}