<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    use \App\Traits\AuditableTrait;
    use \App\Traits\JsonResponseTrait;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->helpers = ['form', 'url', 'acl'];

        parent::initController($request, $response, $logger);
    }

    protected function redirectWithErrors(array $errors): \CodeIgniter\HTTP\RedirectResponse
    {
        return redirect()->back()->withInput()->with('errors', $errors);
    }

    protected function formatValidationErrors(array $errors): array
    {
        return [
            'status' => 'error',
            'errors' => $errors,
            'message' => implode(' ', array_values($errors)),
        ];
    }
}
