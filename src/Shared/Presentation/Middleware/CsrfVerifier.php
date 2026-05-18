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

            if (! $csrfToken || ! hash_equals($csrfToken, $submitted)) {

                SessionManager::getFlashBag()->add(
                    'error',
                    'No valid security token.'
                );

                new RedirectResponse($request->headers->get('referer'))->send();
                exit;
            }
        }
    }
}
