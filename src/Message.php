<?php

namespace Ivy;

class Message
{
    protected static string $template;

    function __construct()
    {
        if (!isset($_SESSION["flash_messages"])) {
            $_SESSION["flash_messages"] = array();
        }
    }

    public static function template($template): Message
    {
        self::$template = $template;
        return new self;
    }

    public static function add($value, $redirect = null): Message
    {
        if (isset($_SESSION["flash_messages"]) && !in_array($value, $_SESSION["flash_messages"])) {
            $_SESSION["flash_messages"][] = $value;
        }
        if ($redirect) {
            if (headers_sent()) {
                print '<script> location.replace("' . $redirect . '"); </script>';
            } else {
                header('location:' . $redirect, true, 302);
                exit;
            }
        }
        return new self;
    }

    public static function render($template = null): void
    {
        if ($template) {
            self::$template = $template;
        }
        if (!empty($_SESSION["flash_messages"]) && !empty(self::$template)) {
            foreach ($_SESSION["flash_messages"] as $key => $value) {
                $message['id'] = $key;
                $messags['text'] = $value;
                Template::render(self::$template, ['message' => (object) $message]);
            }
        }
        self::remove();
    }

    private static function remove(): void
    {
        $_SESSION["flash_messages"] = array();
    }
}
