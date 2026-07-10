<?php

namespace Ivy\Shared\Presentation\Middleware;

use Ivy\Shared\Infrastructure\Manager\SessionManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CsrfVerifier implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        if (in_array($request->getMethod(), ['POST', 'PATCH'])) {

            $csrfToken = SessionManager::get('csrf_token');
            $submitted = $request->request->get('csrf_token', '');

            if (! is_string($csrfToken)) {
                $csrfToken = '';
            }
            if (! is_string($submitted)) {
                $submitted = '';
            }

            if (! $csrfToken || ! hash_equals($csrfToken, $submitted)) {

                SessionManager::getFlashBag()->add(
                    'error',
                    'No valid security token.'
                );

                $referer = $request->headers->get('referer');
                $url = is_string($referer) && $referer !== '' ? $referer : '/';

                new RedirectResponse($url)->send();
                exit;
            }
        }
    }
}
