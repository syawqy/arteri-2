<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ArsipModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * REST API Controller for Arsip (Archive) operations.
 */
class ArsipController extends BaseApiController
{
    private ArsipModel $arsipModel;

    public function __construct()
    {
        $this->arsipModel = new ArsipModel();
    }

    /**
     * GET /api/arsip
     * List all archives with cursor-based pagination.
     *
     * Query params:
     *   - cursor: int (optional) - Last seen ID for pagination
     *   - limit: int (optional, default 20, max 100)
     *   - keywords: string (optional) - Search term
     *   - noarsip, tanggal, uraian, ket, kode, retensi,
     *     penc, peng, lok, med, nobox: string (optional) - Filters
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

        $filters = [
            'noarsip'  => $this->request->getGet('noarsip') ?? '',
            'tanggal'  => $this->request->getGet('tanggal') ?? '',
            'uraian'   => $this->request->getGet('uraian') ?? '',
            'ket'      => $this->request->getGet('ket') ?? '',
            'kode'     => $this->request->getGet('kode') ?? '',
            'retensi'  => $this->request->getGet('retensi') ?? '',
            'penc'     => $this->request->getGet('penc') ?? '',
            'peng'     => $this->request->getGet('peng') ?? '',
            'lok'      => $this->request->getGet('lok') ?? '',
            'med'      => $this->request->getGet('med') ?? '',
            'nobox'    => $this->request->getGet('nobox') ?? '',
        ];

        $result = $this->arsipModel->searchWithCursor(
            $cursor,
            $keywords,
            $filters,
            $limit
        );

        return $this->paginatedResponse(
            $result['records'],
            $result['next_cursor'],
            $result['has_more'],
            'Archives retrieved successfully'
        );
    }

    /**
     * GET /api/arsip/{id}
     * Get a single archive by ID.
     */
    public function show(int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        $arsip = $this->arsipModel->getDetail($id);

        if ($arsip === null) {
            return $this->errorResponse(
                'Archive not found',
                self::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse($arsip, 'Archive retrieved successfully');
    }

    /**
     * POST /api/arsip
     * Create a new archive record.
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
        $required = ['noarsip', 'tanggal', 'uraian', 'kode'];
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

        // Set default values
        $data['username'] = session('username') ?? 'api';

        try {
            $id = $this->arsipModel->insert($data);

            if ($id === false) {
                return $this->errorResponse(
                    'Failed to create archive',
                    self::HTTP_INTERNAL_ERROR,
                    $this->arsipModel->errors()
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Archive created successfully',
                self::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create archive: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * PUT /api/arsip/{id}
     * Update an existing archive record.
     */
    public function update(int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        $arsip = $this->arsipModel->find($id);

        if ($arsip === null) {
            return $this->errorResponse(
                'Archive not found',
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

        // Remove fields that should not be updated via API
        unset($data['id'], $data['tgl_input']);

        try {
            $updated = $this->arsipModel->update($id, $data);

            if ($updated === false) {
                return $this->errorResponse(
                    'Failed to update archive',
                    self::HTTP_INTERNAL_ERROR,
                    $this->arsipModel->errors()
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Archive updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update archive: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * DELETE /api/arsip/{id}
     * Delete an archive record.
     */
    public function delete(int $id): ResponseInterface
    {
        if ($error = $this->validateApiKey()) {
            return $error;
        }

        $arsip = $this->arsipModel->find($id);

        if ($arsip === null) {
            return $this->errorResponse(
                'Archive not found',
                self::HTTP_NOT_FOUND
            );
        }

        try {
            $deleted = $this->arsipModel->delete($id);

            if ($deleted === false) {
                return $this->errorResponse(
                    'Failed to delete archive',
                    self::HTTP_INTERNAL_ERROR
                );
            }

            return $this->successResponse(
                ['id' => $id],
                'Archive deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete archive: ' . $e->getMessage(),
                self::HTTP_INTERNAL_ERROR
            );
        }
    }
}