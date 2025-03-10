<?php

namespace Ivy;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Message
{
    private static ?Session $session = null;

    private static function getSession(): Session
    {
        if (self::$session === null) {
            self::$session = new Session();

            // Check if session is already started
            if (session_status() !== PHP_SESSION_ACTIVE) {
                self::$session->start();
            }
        }
        return self::$session;
    }

    public static function add(string $message, ?string $redirect = null, string $key = 'success'): void
    {
        $session = self::getSession();
        $session->getFlashBag()->add($key, $message);

        if ($redirect) {
            $response = new RedirectResponse($redirect);
            $response->send();
            exit;
        }
    }

    public static function render(string $template): void
    {
        $session = self::getSession();
        $messages = $session->getFlashBag()->all();

        if (!empty($messages)) {
            foreach ($messages as $key => $texts) {
                foreach ($texts as $text) {
                    Template::render($template, ['message' => (object) ['id' => $key, 'text' => $text]]);
                }
            }
        }
    }
}
