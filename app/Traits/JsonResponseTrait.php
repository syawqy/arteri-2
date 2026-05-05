<?php

namespace App\Traits;

trait JsonResponseTrait
{
    protected function jsonSuccess(string $message, ?array $data = null): \CodeIgniter\HTTP\ResponseInterface
    {
        $response = ['status' => 'success', 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->response->setStatusCode(200)->setJSON($response);
    }

    protected function jsonError(string $message, array $errors = []): \CodeIgniter\HTTP\ResponseInterface
    {
        $response = ['status' => 'error', 'message' => $message];
        if (! empty($errors)) {
            $response['errors'] = $errors;
        }
        return $this->response->setStatusCode(422)->setJSON($response);
    }

    protected function jsonValidationErrors(): \CodeIgniter\HTTP\ResponseInterface
    {
        $errors = [];
        if (property_exists($this, 'validator')) {
            $errors = $this->validator->getErrors();
        }
        return $this->jsonError('Validasi gagal. Periksa kembali input Anda.', $errors);
    }
}
