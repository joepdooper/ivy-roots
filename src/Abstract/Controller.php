<?php

namespace Ivy\Abstract;

use Ivy\App;
use Ivy\Manager\SessionManager;
use Ivy\Model\User;
use Ivy\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

abstract class Controller
{
    protected Request $request;
    protected FlashBag $flashBag;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
        $this->flashBag = SessionManager::getFlashBag();
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
            $this->redirect('admin/login');
        }
    }

    protected function requireCsrf(): void
    {
        $csrfToken = SessionManager::get('csrf_token');

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

    protected function redirect(string $url = '', int $statusCode = 302): void
    {
        (new RedirectResponse(Path::get('BASE_PATH') . $url, $statusCode))->send();
    }

    protected function beforeAuthorize(): void
    {
        $method = $this->request->getMethod();

        switch ($method) {
            case 'POST':
                $this->requirePost();
                break;
            case 'PATCH':
                $this->requirePatch();
                break;
            case 'GET':
                $this->requireGet();
                break;
        }
    }

    public function authorize(string $ability, $model)
    {
        $this->beforeAuthorize();

        $modelClass = is_object($model) ? get_class($model) : $model;

        if (!class_exists($modelClass)) {
            error_log("Model {$modelClass} not found.");
        }

        $shortName = basename(str_replace('\\', '/', $modelClass));
        $policyClass = "Ivy\\Policy\\{$shortName}Policy";
        $alternativePolicyClass = "{$modelClass}Policy";

        $policyClass = class_exists($policyClass) ? $policyClass : $alternativePolicyClass;

        if (!class_exists($policyClass)) {
            error_log("Policy not found for {$shortName}");
        }

        if (!method_exists($policyClass, $ability)) {
            error_log("Method {$ability} does not exist in {$policyClass}.");
        }

        if (!$policyClass::$ability(new $modelClass)) {
            $this->redirect('admin/login');
        }

        return true;
    }
}