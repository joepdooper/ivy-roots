<?php

namespace Ivy;

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    protected function beforeAction(): void
    {
        if ($this->request->isMethod('POST')) {
            $this->post();
        }
        if ($this->request->isMethod('GET')) {
            $this->get();
        }
    }

    public function post(): void
    {
        // This method can be overridden in child controllers
    }

    public function get(): void
    {
        // This method can be overridden in child controllers
    }

    protected function requireLogin(): void
    {
        if (!User::isLoggedIn()) {
            Message::add('You must be logged in.', _BASE_PATH . 'login');
        }
    }

    protected function requirePost(): void
    {
        if (!$this->request->isMethod('POST')) {
            Message::add('Invalid request method.', _BASE_PATH);
        }
    }

    protected function requireGet(): void
    {
        if (!$this->request->isMethod('GET')) {
            Message::add('Invalid request method.', _BASE_PATH);
        }
    }
}