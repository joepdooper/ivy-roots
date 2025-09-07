<?php

namespace Ivy\Service;

use Items\Collection\Image\ImageSize;
use \Ivy\Abstract\File;
use Ivy\Core\Path;
use Ivy\Model\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileService
{
    /** @var File[] */
    protected array $files = [];

    /**
     * Add one or more files.
     *
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
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('All elements must be instances of ' . File::class);
        }
    }

    public function upload(): void
    {
        if(!User::getAuth()->isLoggedIn()){
            throw new \RuntimeException('You must be logged in to upload files.');
        }

        foreach ($this->files as $file){
            $file->validate();

            $destination = Path::get('MEDIA_PATH') . DIRECTORY_SEPARATOR . trim($file->getUploadPath(), DIRECTORY_SEPARATOR);

            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->getUploadFile()->move($destination, $file->getFileName());
        }
    }
}
