<?php

namespace Ivy;

use Exception;
use GUMP;
use HTMLPurifier;
use HTMLPurifier_Config;

class Request
{
    protected array $data;
    protected array $files;
    protected mixed $method;
    protected array $htmlAllowedElements = [
        'br',
        'ul',
        'ol',
        'li',
        'b',
        'i'
    ];
    protected GUMP $validator;
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $json = json_decode(file_get_contents('php://input'), true);
        $this->data = array_merge($_REQUEST, $json ?? []);
        $this->files = $_FILES;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->validator = new GUMP();
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.AllowedElements', $this->htmlAllowedElements);
        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(mixed $input): mixed {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }

        if (is_string($input)) {
            return $this->purifier->purify($input);
        }

        return $input;
    }

    /**
     * Get a specific input item from the request.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->data[$key] ?? $default;
        return $this->sanitize($value);
    }

    /**
     * Get a specific file from the request.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function file(string $key, mixed $default = null): mixed
    {
        return $this->files[$key] ?? $default;
    }

    /**
     * Get all files from the request.
     *
     * @return array
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Get all the input data from the request.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->sanitize(array_merge($this->data, $this->files));
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Check if the request method matches the given method.
     *
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    /**
     * Validate a specific input item from the request.
     *
     * @param string $key
     * @param array $rules
     * @return bool
     * @throws Exception
     */
    public function validateInput(string $key, array $rules): bool
    {
        $data = [$key => $this->input($key)];
        return $this->validator->validate($data, $rules);
    }

    /**
     * Validate all input data from the request.
     *
     * @param array $rules
     * @return bool|array
     * @throws Exception
     */
    public function validate(array $input, array $rules): bool|array
    {
        return $this->validator->validate($input, $rules);
    }

    /**
     * Get validation errors.
     *
     * @return array
     * @throws Exception
     */
    public function errors(): array
    {
        return $this->validator->get_errors_array();
    }

    public static function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function requireCsrf(): void {
        if ($this->method() === 'POST') {
            $token = $this->input('csrf_token');
            if (!$this->validateCsrfToken($token)) {
                throw new \Exception('Invalid CSRF token');
            }
        }
    }
}
