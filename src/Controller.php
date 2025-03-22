<?php

namespace Ivy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

abstract class Controller
{
    protected Request $request;
    protected FlashBag $flashBag;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
        $this->flashBag = App::session()->getFlashBag();
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

    protected function requirePost()
    {
        if (!$this->request->isMethod('POST')) {
            $this->flashBag->add('error', 'Invalid request method.');
            $this->redirect();
        }
        $this->requireCsrf();
    }

    protected function requirePatch()
    {
        if (!$this->request->isMethod('PATCH')) {
            $this->flashBag->add('error', 'Invalid request method.');
            $this->redirect();
        }
        $this->requireCsrf();
    }

    protected function requireGet(): void
    {
        if (!$this->request->isMethod('GET')) {
            $this->flashBag->add('error', 'Invalid request method.');
            $this->redirect();
        }
    }

    protected function requireLogin(): void
    {
        if (!User::getAuth()->isLoggedIn()) {
            $this->flashBag->add('error', 'You must be logged in.');
            $this->redirect('login');
        }
    }

    protected function requireAdmin(): void
    {
        if (!User::canEditAsAdmin()) {
            $this->flashBag->add('error', 'You must have an admin role.');
            $this->redirect('login');
        }
    }

    protected function requireSuperAdmin(): void
    {
        if (!User::canEditAsSuperAdmin()) {
            $this->flashBag->add('error', 'You must have an super admin role.');
            $this->redirect('login');
        }
    }

    protected function requireCsrf(): void
    {
        $csrfToken = App::session()->get('csrf_token');

        if (!$csrfToken || !hash_equals($csrfToken, $this->request->get('csrf_token', ''))) {
            $this->flashBag->add('error', 'Invalid security token.');
            $this->redirect();
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

    protected function redirect(string $url = '', int $statusCode = 302): void
    {
        (new RedirectResponse(Path::get('BASE_PATH') . $url, $statusCode))->send();
    }

    public function authorize(string $ability, $model)
    {
        $policyClass = $model . 'Policy';
        if (!class_exists($policyClass)) {
            $policyClass = class_basename($model) . 'Policy';
        }

        if (!class_exists($policyClass)) {
            throw new Exception("Policy [$policyClass] not found.");
        }

        if (!method_exists($policyClass, $ability)) {
            throw new Exception("Method [$ability] does not exist in [$policyClass].");
        }

        if (!call_user_func([$policyClass, $ability], $model)) {
            throw new Exception("Unauthorized action.");
        }

        return true;
    }
}