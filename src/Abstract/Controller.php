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

        $this->requirements();
    }

    protected function requirePost()
    {
        if (!$this->request->isMethod('POST')) {
            $this->flashBag->add('error', 'Invalid request method.');
            $this->redirect();
            exit;
        }
        $this->requireCsrf();
    }

    protected function requirePatch()
    {
        if (!$this->request->isMethod('PATCH')) {
            $this->flashBag->add('error', 'Invalid request method.');
            $this->redirect();
            exit;
        }
        $this->requireCsrf();
    }

    protected function requireGet(): void
    {
        if (!$this->request->isMethod('GET')) {
            $this->flashBag->add('error', 'Invalid request method.');
            $this->redirect();
            exit;
        }
    }

    protected function requirements(): void
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

    protected function requireCsrf(): void
    {
        $csrfToken = SessionManager::get('csrf_token');

        if (!$csrfToken || !hash_equals($csrfToken, $this->request->get('csrf_token', ''))) {
            $this->flashBag->add('error', 'Invalid security token.');
            $this->redirect();
            exit;
        }
    }

    protected function redirect(string $url = '', int $statusCode = 302): void
    {
        (new RedirectResponse(Path::get('BASE_PATH') . $url, $statusCode))->send();
    }

    protected function getRefererPath(): ?string
    {
        $referer = $this->request->headers->get('referer');
        $basePath = $this->request->getBasePath();
        $path = parse_url($referer, PHP_URL_PATH);

        if (!$path || !$basePath || !str_starts_with($path, $basePath)) {
            return null;
        }

        return ltrim(substr($path, strlen($basePath)), '/');
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
}