<?php
namespace Ivy\Routing;

use Ivy\Manager\RouterManager;

class Route
{
    protected string $method;
    protected string $pattern;

    protected function __construct(string $method, string $pattern)
    {
        $this->method = $method;
        $this->pattern = $pattern;
    }

    public static function get(string $pattern, string|callable $handler): self
    {
        return self::addRoute('GET', $pattern, $handler);
    }

    public static function post(string $pattern, string|callable $handler): self
    {
        return self::addRoute('POST', $pattern, $handler);
    }

    protected static function addRoute(string $method, string $pattern, string|callable $handler): self
    {
        RouterManager::register($method, $pattern, $handler);

        return new self($method, $pattern);
    }

    public function before(string|callable $handler): self
    {
        RouterManager::before($this->method, $this->pattern, $handler);
        return $this;
    }

    public static function mount(string $basePath, callable $callback): void
    {
        RouterManager::mount($basePath, $callback);
    }
}