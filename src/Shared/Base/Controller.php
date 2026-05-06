<?php

namespace Ivy\Shared\Base;

use Illuminate\Container\Container;
use Ivy\Shared\Core\Path;
use Ivy\Infrastructure\Manager\SessionManager;
use Ivy\Application\Service\AuthApplicationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

abstract class Controller
{
    protected FlashBagInterface $flashBag;
    protected AuthApplicationService $authService;
    protected Request $request;

    public function __construct()
    {
        $this->request = Container::getInstance()->make(Request::class);
        $this->flashBag = SessionManager::getFlashBag();
        $this->authService = new AuthApplicationService();
    }

    protected function redirect(string $url = '', int $statusCode = 302): void
    {
        new RedirectResponse(Path::get('BASE_PATH').$url, $statusCode)->send();
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

    protected function redirectToFormWithErrors($result) {
        $this->flashBag->set('errors', $result->errors);
        $this->flashBag->set('old', $result->old);
        $this->redirect($this->getRefererPath());
    }
}
