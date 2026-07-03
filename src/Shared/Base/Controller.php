<?php

namespace Ivy\Shared\Base;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Ivy\Shared\Core\Path;
use Ivy\Shared\Infrastructure\Http\Redirector;
use Ivy\Shared\Infrastructure\Manager\SessionManager;
use Ivy\Shared\Presentation\Validation\ValidationResult;
use Ivy\User\Application\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

abstract class Controller
{
    protected FlashBagInterface $flashBag;
    protected AuthService $authService;
    protected Request $request;
    protected Redirector $redirect;

    /**
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        $this->request = Container::getInstance()->make(Request::class);
        $this->flashBag = SessionManager::getFlashBag();
        $this->authService = new AuthService();
        $this->redirect = new Redirector($this->request);
    }

    protected function redirect(string $url = '', int $statusCode = 302): void
    {
        new RedirectResponse(Path::get('BASE_PATH').$url, $statusCode)->send();
        exit;
    }

    /**
     * @param array<int, mixed> $data
     */
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

        if (!$referer) {
            return null;
        }

        $parts = parse_url($referer);

        $path = $parts['path'] ?? null;

        if (!$path || !str_starts_with($path, $basePath)) {
            return null;
        }

        $relativePath = ltrim(substr($path, strlen($basePath)), '/');

        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $relativePath . $query;
    }

    protected function redirectToFormWithErrors(ValidationResult $result): void
    {
        $this->flashBag->set('errors', $result->errors);
        $this->flashBag->set('old', $result->old);
        $this->redirect($this->getRefererPath() ?? $this->request->getPathInfo());
    }
}
