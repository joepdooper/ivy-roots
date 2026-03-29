<?php

namespace Ivy\Service;

use Ivy\Config\Environment;
use Ivy\Core\Path;

class AssetPublisher
{
    public function publish(string $type, string $name): void
    {
        $basePath = $this->resolveBasePath($type, $name);
        $source = $basePath . 'dist' . DIRECTORY_SEPARATOR;

        if (!is_dir($source)) {
            return;
        }

        if ($type === 'templates') {
            $target = Path::get('PUBLIC_PATH');

            $this->removeDirectory($target . 'css');
            $this->removeDirectory($target . 'js');

            if (is_dir($source . 'css')) {
                $this->copyDirectory(
                    $source . 'css' . DIRECTORY_SEPARATOR,
                    $target . 'css' . DIRECTORY_SEPARATOR
                );
            }

            if (is_dir($source . 'js')) {
                $this->copyDirectory(
                    $source . 'js' . DIRECTORY_SEPARATOR,
                    $target . 'js' . DIRECTORY_SEPARATOR
                );
            }

            if (Environment::isProd()) {
                $this->setReadOnly($target . 'css');
                $this->setReadOnly($target . 'js');
            }

            return;
        }
    }

    protected function publishFiles(string $source, string $targetBase, string $type): void
    {
        if (is_dir($targetBase)) {
            $this->removeDirectory($targetBase);
        }

        @mkdir($targetBase, 0755, true);

        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $ext = pathinfo($item, PATHINFO_EXTENSION);
            if ($ext !== $type) continue;

            copy($source . $item, $targetBase . $item);
        }

        if (Environment::isProd()) {
            $this->setReadOnly($targetBase);
        }
    }

    protected function resolveBasePath(string $type, string $name): string
    {
        if (!in_array($type, ['templates', 'plugins'])) {
            throw new \InvalidArgumentException("Invalid type: $type");
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid name: $name");
        }

        return $type === 'templates'
            ? Path::get('TEMPLATES_PATH') . $name . DIRECTORY_SEPARATOR
            : Path::get('PLUGINS_PATH') . $name . DIRECTORY_SEPARATOR;
    }

    protected function copyDirectory(string $source, string $target): void
    {
        $parent = dirname($target);

        if (!is_dir($parent)) {
            throw new \RuntimeException("Parent directory does not exist: $parent");
        }

        if (!is_writable($parent)) {
            chmod($parent, 0755);
        }

        if (!is_dir($target) && !mkdir($target, 0755, true)) {
            throw new \RuntimeException("Failed to create directory: $target");
        }

        $items = scandir($source);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $src = rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;
            $dst = rtrim($target, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

            if (is_dir($src)) {
                $this->copyDirectory($src . DIRECTORY_SEPARATOR, $dst . DIRECTORY_SEPARATOR);
            } else {
                if (!copy($src, $dst)) {
                    throw new \RuntimeException("Failed to copy file: $src → $dst");
                }
            }
        }
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            chmod($item->getRealPath(), $item->isDir() ? 0755 : 0644);
        }

        chmod($dir, 0755);

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($dir);
    }

    protected function setReadOnly(string $target): void
    {
        if (!is_dir($target)) return;

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($target, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                chmod($item->getRealPath(), 0555);
            } else {
                chmod($item->getRealPath(), 0444);
            }
        }
    }
}