<?php

namespace Ivy\Shared\Infrastructure\Service;

use Symfony\Component\Process\Process;

readonly class ComposerService
{
    /**
     * Examples:
     *
     * ['composer']
     * ['/usr/local/bin/composer']
     * ['php', '/var/www/composer.phar']
     */
    public function __construct(
        private array   $binary = ['composer'],
        private ?string $workingDirectory = null,
    ) {}

    public function require(string $package): void
    {
        $this->run(['require', $package]);
    }

    public function remove(string $package): void
    {
        $this->run(['remove', $package]);
    }

    public function update(?string $package = null): void
    {
        $command = ['update'];

        if ($package !== null) {
            $command[] = $package;
        }

        $this->run($command);
    }

    public function install(): void
    {
        $this->run(['install']);
    }

    public function dumpAutoload(): void
    {
        $this->run(['dump-autoload']);
    }

    /**
     * Execute any Composer command.
     *
     * Example:
     * $composer->run(['show']);
     * $composer->run(['show', 'joepdooper/ivy-roots']);
     */
    public function run(array $arguments): void
    {
        $process = new Process(
            [...$this->binary, ...$arguments],
            $this->workingDirectory,
        );

        $process->setTimeout(null);
        $process->mustRun();
    }
}