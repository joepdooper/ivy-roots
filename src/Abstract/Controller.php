<?php

namespace Ivy\Abstract;

use Curl\Curl;
use Ivy\Core\Path;
use Ivy\Manager\SessionManager;
use Ivy\Middleware\CsrfVerifier;
use Ivy\Middleware\MiddlewarePipeline;
use Ivy\Middleware\RequestNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

abstract class Controller
{
    protected Request $request;

    protected FlashBagInterface $flashBag;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
        $this->flashBag = SessionManager::getFlashBag();
        $this->runMiddlewares();
    }

    protected function runMiddlewares(): void
    {
        $pipeline = new MiddlewarePipeline;
        $pipeline->add(new RequestNormalizer);
        $pipeline->add(new CsrfVerifier);
        $pipeline->handle($this->request, fn (Request $req) => null);
    }

    protected function redirect(string $url = '', int $statusCode = 302): void
    {
        (new RedirectResponse(Path::get('BASE_PATH').$url, $statusCode))->send();
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

        if (! $path || ! str_starts_with($path, $basePath)) {
            return null;
        }

        return ltrim(substr($path, strlen($basePath)), '/');
    }
}
