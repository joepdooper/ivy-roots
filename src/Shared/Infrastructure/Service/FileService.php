<?php

namespace Ivy\Shared\Infrastructure\Service;

use Exception;
use Ivy\Shared\Base\File;
use Ivy\Shared\Core\Path;
use Ivy\Shared\Domain\Exception\FileException;

class FileService
{
    /** @var File[] */
    protected array $files = [];

    /**
     * @param  File|File[]  ...$files
     * @return $this
     */
    public function add(File|array ...$files): static
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    $this->assertFile($f);
                    $this->files[] = $f;
                }
            } else {
                $this->assertFile($file);
                $this->files[] = $file;
            }
        }

        return $this;
    }

    private function assertFile(mixed $file): void
    {
        if (! $file instanceof File) {
            throw new FileException('All elements must be instances of '.File::class);
        }
    }

    public function upload(): void
    {
        foreach ($this->files as $file) {
            $file->validate();

            $destination = Path::get('MEDIA_PATH').DIRECTORY_SEPARATOR.trim($file->getUploadPath(), DIRECTORY_SEPARATOR);

            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->getUploadFile()?->move($destination, $file->getFileName());
        }
    }

    /**
     * Check whether a file exists inside a directory.
     * @throws Exception
     */
    public static function exists(string $path, string $basePath): bool
    {
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $fullPath = $basePath.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);

        if (! is_file($fullPath)) {
            return false;
        }

        $realPath = realpath($fullPath);

        if ($realPath === false || ! str_starts_with(
                $realPath,
                $basePath.DIRECTORY_SEPARATOR
            )) {
            throw new Exception('Invalid file path: '.$path);
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public static function getRealPath(string $path): ?string
    {
        $file = new \Symfony\Component\HttpFoundation\File\File($path);
        $file = $file->getRealPath();

        if ($file === false || ! str_starts_with($file, Path::get('PLUGINS_PATH'))) {
            throw new Exception('Invalid file path: '.$path);
        }

        return $file;
    }

    public static function getRelativePath(string $path, string $basePath): ?string
    {
        return str_replace($basePath, '', $path);
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws Exception
     */
    public static function parseJson(string $path): ?array
    {
        $file = self::getRealPath(Path::get('PLUGINS_PATH').trim($path));

        if (! $file) {
            throw new Exception('No JSON file found: '.$path);
        }

        $content = file_get_contents($file);

        if (! $content) {
            throw new Exception('JSON file could not be read: '.$path);
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (! $decoded) {
            throw new Exception('Invalid JSON: '.$path);
        }

        return $decoded;
    }
}
