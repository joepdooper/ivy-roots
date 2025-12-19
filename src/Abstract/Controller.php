<?php

namespace Ivy\Abstract;

use Ivy\Manager\SessionManager;
use Ivy\Core\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Ivy\Middleware\RequestNormalizer;
use Ivy\Middleware\CsrfVerifier;
use Ivy\Middleware\MiddlewarePipeline;

abstract class Controller
{
    protected Request $request;
    protected FlashBag $flashBag;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
        $this->flashBag = SessionManager::getFlashBag();
        $this->runMiddlewares();
        $this->requirements();
    }

    protected function runMiddlewares(): void
    {
        $pipeline = new MiddlewarePipeline();
        $pipeline->add(new RequestNormalizer());
        $pipeline->add(new CsrfVerifier());
        $pipeline->handle($this->request, fn(Request $req) => null);
    }

    protected function requirePost(): void
    {
        if (!$this->request->isMethod('POST')) {
            $this->flashBag->add('error', 'Invalid request method.');
            $this->redirect();
            exit;
        }
    }

    protected function requirePatch(): void
    {
        if (!$this->request->isMethod('PATCH')) {
            $this->flashBag->add('error', 'Invalid request method.');
            $this->redirect();
            exit;
        }
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

    protected function redirect(string $url = '', int $statusCode = 302): void
    {
        (new RedirectResponse(Path::get('BASE_PATH') . $url, $statusCode))->send();
        exit;
    }

    protected function json(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    protected function wantsJson(): bool
    {
        return $this->request->headers->get('Accept') === 'application/json';
    }

    protected function getRefererPath(): ?string
    {
        $referer = $this->request->headers->get('referer');
        $basePath = $this->request->getBasePath();
        $path = parse_url($referer, PHP_URL_PATH);

        if (!$path || !str_starts_with($path, $basePath)) {
            return null;
        }

        return ltrim(substr($path, strlen($basePath)), '/');
    }
}