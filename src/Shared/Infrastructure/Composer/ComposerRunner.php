<?php

namespace Ivy\Shared\Infrastructure\Composer;

use Ivy\Shared\Core\Path;

final readonly class ComposerRunner
{
    public function require(string $package): void
    {
        $this->runComposer('require', $package);
    }

    public function update(string $package): void
    {
        $this->runComposer('update', $package);
    }

    public function remove(string $package): void
    {
        $this->runComposer('remove', $package);
    }

    private function runComposer(string $command, string $package): void
    {
        $projectPath = Path::get('PROJECT_PATH');
        $cache = $projectPath . 'cache';
        $log = $projectPath . 'logs/composer.log';

        $cmd = sprintf(
            'cd %s && HOME=%s COMPOSER_HOME=%s composer %s %s >> %s 2>&1 < /dev/null &',
            escapeshellarg($projectPath),
            escapeshellarg($cache),
            escapeshellarg($cache . '/composer'),
            $command,
            escapeshellarg($package),
            escapeshellarg($log),
        );

        exec($cmd);
    }
}