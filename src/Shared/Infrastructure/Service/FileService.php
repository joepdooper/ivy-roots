<?php

namespace Ivy\Shared\Infrastructure\Service;

use Ivy\Shared\Base\File;
use Ivy\Shared\Core\Path;
use Ivy\Shared\Domain\Exception\FileException;
use Ivy\Shared\Domain\ValueObject\ImageFile;

class FileService
{
    /** @var File[] */
    protected array $files = [];

    /**
     * @param File|File[] ...$files
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
}
