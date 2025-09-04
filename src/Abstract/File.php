<?php

namespace Ivy\Abstract;

use Items\Collection\Image\ImageSize;
use Ivy\Core\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class File
{
    protected ?UploadedFile $uploadFile;
    protected string $fileName;

    public function __construct(?UploadedFile $uploadedFile = null)
    {
        $this->uploadFile = $uploadedFile;
    }

    public function getUploadFile(): UploadedFile
    {
        return $this->uploadFile;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    abstract public function getUploadPath(): string|array;
    abstract public function getAllowedMimeTypes(): array;
    abstract public function getAllowedExtensions(): array;

    /**
     * @throws RuntimeException
     */
    public function process(): static
    {
        if (!$this->uploadFile->isValid()) {
            throw new RuntimeException("Upload failed with error: " . $this->uploadFile->getError());
        }

        $ext = strtolower($this->uploadFile->getClientOriginalExtension());
        $mime = $this->uploadFile->getMimeType();

        if (!$this->isMimeAllowed($mime)) {
            throw new RuntimeException("File type not allowed: $mime");
        }

        if (!in_array($ext, $this->getAllowedExtensions(), true)) {
            throw new RuntimeException("File extension not allowed: .$ext");
        }

        $this->fileName = $this->generateFileName($ext);

        $this->upload();

        return $this;
    }

    public function generateFileName(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    protected function isMimeAllowed(string $mime): bool
    {
        foreach ($this->getAllowedMimeTypes() as $allowed) {
            if ($allowed === $mime || (str_ends_with($allowed, '/*') && str_starts_with($mime, substr($allowed, 0, strpos($allowed, '/')) . '/'))) {
                return true;
            }
        }

        return false;
    }

    protected function upload(): void
    {
        $paths = $this->getUploadPath();
        if (!is_array($paths)) {
            $paths = [$paths];
        }

        foreach ($paths as $path){
            $destination = Path::get('MEDIA_PATH') . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);

            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $this->uploadFile->move($destination, $this->fileName);
        }
    }

    public function remove($file):void
    {
        if($file){
            unlink(Path::get('MEDIA_PATH') . $this->getUploadPath() . DIRECTORY_SEPARATOR . $file);
        }
    }
}
