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
        $method = $this->request->method();

        switch ($method) {
            case 'POST':
                $this->post();
                break;
            case 'PATCH':
                $this->patch();
                break;
            case 'GET':
                $this->get();
                break;
        }
    }

    protected function requireCsrf(): void
    {
        try {
            $this->request->requireCsrf();
        } catch (\Exception $e) {
            Message::add('Invalid security token.', Path::get('BASE_PATH'));
        }
    }

    public function post(): void
    {
        $this->requirePost();
        // This method can be overridden in child controllers
    }

    public function patch(): void
    {
        $this->requirePatch();
        // This method can be overridden in child controllers
    }

    public function get(): void
    {
        $this->requireGet();
        // This method can be overridden in child controllers
    }

    protected function requirePost(): void
    {
        if (!$this->request->isMethod('POST')) {
            Message::add('Invalid request method.', Path::get('BASE_PATH'));
        }
        $this->requireCsrf();
    }

    protected function requirePatch(): void
    {
        if (!$this->request->isMethod('PATCH')) {
            Message::add('Invalid request method.', Path::get('BASE_PATH'));
        }
        $this->requireCsrf();
    }

    protected function requireGet(): void
    {
        if (!$this->request->isMethod('GET')) {
            Message::add('Invalid request method.', Path::get('BASE_PATH'));
        }
    }

    protected function requireLogin(): void
    {
        if (!User::getAuth()->isLoggedIn()) {
            Message::add('You must be logged in.', Path::get('BASE_PATH') . 'login');
        }
    }
}