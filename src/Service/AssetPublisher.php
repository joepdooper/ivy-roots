<?php

namespace Ivy\Service;

use Ivy\Core\Path;
use Ivy\Model\Template;

class AssetPublisher
{
    public function publish(): void
    {
        $target = Path::get('PUBLIC_PATH');

        // fresh state
        $this->removeDirectory($target . 'css');
        $this->removeDirectory($target . 'js');

        $templates = Template::whereIn('type', ['base', 'sub'])
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

    protected function copy(string $source, string $target): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
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