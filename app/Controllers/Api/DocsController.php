<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

class DocsController extends BaseApiController
{
    /**
     * GET /api/v1/openapi.json
     */
    public function openapi(): ResponseInterface
    {
        $path = ROOTPATH . 'docs/openapi.yaml';

        if (! is_file($path)) {
            return $this->errorResponse('OpenAPI specification not found', self::HTTP_NOT_FOUND);
        }

        $yaml = file_get_contents($path);
        if ($yaml === false) {
            return $this->errorResponse('Unable to read OpenAPI specification', self::HTTP_INTERNAL_ERROR);
        }

        if (function_exists('yaml_parse')) {
            $spec = yaml_parse($yaml);
            if ($spec === false) {
                return $this->errorResponse('Invalid OpenAPI YAML', self::HTTP_INTERNAL_ERROR);
            }

            return $this->response
                ->setStatusCode(self::HTTP_OK)
                ->setJSON($spec);
        }

        return $this->response
            ->setStatusCode(self::HTTP_OK)
            ->setHeader('Content-Type', 'application/yaml')
            ->setBody($yaml);
    }

    /**
     * GET /api/v1/docs
     * Render Swagger UI for browsing the API.
     */
    public function ui(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(self::HTTP_OK)
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody(view('api/docs'));
    }
}
