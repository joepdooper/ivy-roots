<?php

namespace Ivy\Shared\Infrastructure\Http;

use Ivy\Shared\Core\Path;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class Redirector
{
    public function __construct(
        private Request           $request,
        private FlashBagInterface $flashBag
    )
    {
    }

    public function to(string $url = '', int $statusCode = 302): never
    {
        new RedirectResponse(Path::get('BASE_PATH') . $ur, $statusCode)->send();
        exit;
    }

    public function back(?string $fallback = null, int $statusCode = 302): never
    {
        $referer = $this->request->headers->get('referer');

        if (!$referer) {
            $this->to($fallback ?? '', $statusCode);
        }

        $parts = parse_url($referer);
        $basePath = $this->request->getBasePath();
        $path = $parts['path'] ?? null;

        if (!$path || !str_starts_with($path, $basePath)) {
            $this->to($fallback ?? '', $statusCode);
        }

        $relative = ltrim(substr($path, strlen($basePath)), '/');

        $this->to($relative, $statusCode);
    }
}
