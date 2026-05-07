<?php

namespace Ivy\Application\Service;

use FilesystemIterator;
use Ivy\Shared\Core\Path;
use Ivy\Domain\Entity\TemplateEntity;

class AssetPublisherApplicationService
{
    public function publishTemplate(): void
    {
        $target = Path::get('PUBLIC_PATH');

        $this->removeDirectory($target . 'css');
        $this->removeDirectory($target . 'js');

        $templates = TemplateEntity::whereIn('type', ['base', 'sub'])
            ->orderByRaw("FIELD(type, 'base', 'sub')")
            ->get();

        foreach ($templates as $template) {
            $source = Path::get('TEMPLATES_PATH')
                . $template->value
                . DIRECTORY_SEPARATOR
                . 'dist'
                . DIRECTORY_SEPARATOR;

            if (is_dir($source)) {
                $this->copy($source, $target);
            }
        }
    }

    public function publishPlugin(string $plugin): void
    {
        $source = Path::get('PLUGINS_PATH')
            . $plugin
            . DIRECTORY_SEPARATOR
            . 'dist'
            . DIRECTORY_SEPARATOR;

        if (! is_dir($source)) {
            return;
        }

        $target = Path::get('PUBLIC_PATH')
            . 'plugins'
            . DIRECTORY_SEPARATOR
            . $plugin
            . DIRECTORY_SEPARATOR;

        $this->copy($source . 'css', $target . 'css');
        $this->copy($source . 'js',  $target . 'js');
    }

    protected function copy(string $source, string $target): void
    {
        if (! is_dir($source)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($source));
            $dst = $target . DIRECTORY_SEPARATOR . $relative;

            if ($item->isDir()) {
                if (! is_dir($dst)) {
                    mkdir($dst, 0755, true);
                }
                continue;
            }

            $dir = dirname($dst);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            copy($item->getPathname(), $dst);
        }
    }

    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
