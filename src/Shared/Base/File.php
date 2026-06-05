<?php

namespace Ivy\Shared\Base;

use Ivy\Shared\Core\Path;
use Ivy\Shared\Domain\Exception\FileException;
use Random\RandomException;
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
        if ($uploadedFile) {
            $this->uploadFile = $uploadedFile;
            $this->extension = strtolower($uploadedFile->getClientOriginalExtension());
            $this->mimeType = $uploadedFile->getMimeType();
        }
    }

    public function getUploadFile(): UploadedFile|null
    {
        return $this->uploadFile;
    }

    public function getExtension(): string|null
    {
        return $this->extension;
    }

    public function getMimeType(): string|null
    {
        return $this->mimeType;
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    public function setUploadPath(string $uploadPath): static
    {
        $this->uploadPath = $uploadPath;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @throws RandomException
     */
    public function generateFileName(): string
    {
        $this->fileName = bin2hex(random_bytes(16)).'.'.$this->extension;

        return $this->fileName;
    }

    /**
     * @throws FileException
     */
    public function validate(): static
    {
        if (! $this->uploadFile?->isValid()) {
            throw new FileException('Upload failed with error: '.$this->uploadFile?->getError());
        }

        if (! $this->isMimeAllowed()) {
            throw new FileException("File type not allowed: $this->mimeType");
        }

        if (! in_array($this->extension, $this->getAllowedExtensions(), true)) {
            throw new FileException("File extension not allowed: .$this->extension");
        }

        return $this;
    }

    protected function isMimeAllowed(): bool
    {
        return array_any($this->getAllowedMimeTypes(), fn($allowed) => $allowed === $this->mimeType || (str_ends_with($allowed, '/*') && str_starts_with($this->mimeType, substr($allowed, 0, strpos($allowed, '/')) . '/')));
    }

    public function remove(?string $fileName = null): void
    {
        if ($fileName) {
            unlink(Path::get('MEDIA_PATH').trim($this->getUploadPath(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$fileName);
        }
    }

    abstract public function getAllowedMimeTypes(): array;

    abstract public function getAllowedExtensions(): array;
}
