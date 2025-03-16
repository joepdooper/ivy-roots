<?php

namespace Ivy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
    }

    protected function beforeAction(): void
    {
        $method = $this->request->getMethod();

        switch ($method) {
            case 'POST':
                $this->post();
                break;
            case 'PATCH':
                $this->patch();
                break;
            case 'GET':
                $this->get();
                break;
        }
    }

    public function post(): void
    {
        $this->requirePost();
    }

    public function patch(): void
    {
        $this->requirePatch();
    }

    public function get(): void
    {
        $this->requireGet();
    }

    protected function requirePost(): void
    {
        if (!$this->request->isMethod('POST')) {
            Message::add('Invalid request method.', Path::get('BASE_PATH'));
        }
        $this->requireCsrf();
    }

    protected function requirePatch(): void
    {
        if (!$this->request->isMethod('PATCH')) {
            Message::add('Invalid request method.', Path::get('BASE_PATH'));
        }
        $this->requireCsrf();
    }

    protected function requireGet(): void
    {
        if (!$this->request->isMethod('GET')) {
            Message::add('Invalid request method.', Path::get('BASE_PATH'));
        }
    }

    protected function requireLogin(): void
    {
        if (!User::getAuth()->isLoggedIn()) {
            Message::add('You must be logged in.', Path::get('BASE_PATH') . 'login');
        }
    }

    protected function requireAdmin(): void
    {
        if (!User::canEditAsAdmin()) {
            Message::add('You must have an admin role.', Path::get('BASE_PATH') . 'login');
        }
    }

    protected function requireSuperAdmin(): void
    {
        if (!User::canEditAsSuperAdmin()) {
            Message::add('You must have an super admin role.', Path::get('BASE_PATH') . 'login');
        }
    }

    protected function requireCsrf(): void
    {
        if (!isset($_SESSION['csrf_token']) ||
            !hash_equals(
                $_SESSION['csrf_token'],
                $this->request->get('csrf_token', '')
            )) {
            Message::add('Invalid security token.', Path::get('BASE_PATH'));
        }
    }

    protected function validate(array $rules): array
    {
        return $this->request->validate($rules);
    }

    protected function json(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    protected function wantsJson(): bool
    {
        return $this->request->headers->get('Accept') === 'application/json';
    }

    protected function handleUploadedFile(string $field): ?string
    {
        $file = $this->request->files->get($field);
        if ($file) {
            return $file->store('uploads');
        }
        return null;
    }
}