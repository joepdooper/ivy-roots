<?php

namespace Ivy\Infrastructure\Service;

use Ivy\Domain\Model\UserModel;
use Ivy\Shared\Base\File;
use Ivy\Shared\Core\Path;

class FileService
{
    /** @var File[] */
    protected array $files = [];

    /**
     * Add one or more files.
     *
     * @param File|array ...$files
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
            throw new \InvalidArgumentException('All elements must be instances of '.File::class);
        }
    }

    public function upload(): void
    {
        if (! UserModel::getAuth()->isLoggedIn()) {
            throw new \RuntimeException('You must be logged in to upload files.');
        }

        foreach ($this->files as $file) {
            $file->validate();

            $destination = Path::get('MEDIA_PATH').DIRECTORY_SEPARATOR.trim($file->getUploadPath(), DIRECTORY_SEPARATOR);

            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->getUploadFile()->move($destination, $file->getFileName());
        }
    }
}
