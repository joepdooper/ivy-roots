<?php

namespace Ivy\Middleware;

use Ivy\Core\Path;
use Ivy\Manager\SessionManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

                (new RedirectResponse($request->headers->get('referer')))->send();
                exit;
            }
        }
    }
}
