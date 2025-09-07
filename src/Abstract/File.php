<?php

namespace Ivy\Abstract;

use Items\Collection\Image\ImageSize;
use Ivy\Core\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class File
{
    protected ?UploadedFile $uploadFile;
    protected ?string $extension;
    protected ?string $mimeType;

    protected string $uploadPath;

    protected string $fileName;

    public function __construct(?UploadedFile $uploadedFile = null)
    {
        if($uploadedFile){
            $this->uploadFile = $uploadedFile;
            $this->extension = strtolower($uploadedFile->getClientOriginalExtension());
            $this->mimeType = $uploadedFile->getMimeType();
        }
    }

    public function getUploadFile(): UploadedFile
    {
        return $this->uploadFile;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    public function setUploadPath($uploadPath): static
    {
        $this->uploadPath = $uploadPath;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName($fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function generateFileName(): string
    {
        $this->fileName = bin2hex(random_bytes(16)) . '.' . $this->extension;

        return $this->fileName;
    }

    /**
     * @throws RuntimeException
     */
    public function validate(): static
    {
        if (!$this->uploadFile->isValid()) {
            throw new RuntimeException("Upload failed with error: " . $this->uploadFile->getError());
        }

        if (!$this->isMimeAllowed()) {
            throw new RuntimeException("File type not allowed: $mime");
        }

        if (!in_array($this->extension, $this->getAllowedExtensions(), true)) {
            throw new RuntimeException("File extension not allowed: .$ext");
        }

        return $this;
    }

    protected function isMimeAllowed(): bool
    {
        foreach ($this->getAllowedMimeTypes() as $allowed) {
            if ($allowed === $this->mimeType || (str_ends_with($allowed, '/*') && str_starts_with($this->mimeType, substr($allowed, 0, strpos($allowed, '/')) . '/'))) {
                return true;
            }
        }

        return false;
    }

    public function remove(?string $fileName = null): void
    {
        if($fileName){
            unlink(Path::get('MEDIA_PATH') . trim($this->getUploadPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName);
        }
    }

    abstract public function getAllowedMimeTypes(): array;
    abstract public function getAllowedExtensions(): array;
}
