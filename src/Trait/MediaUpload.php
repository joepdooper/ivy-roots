<?php
namespace Ivy\Trait;

use Ivy\Manager\SessionManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ivy\Core\Path;

trait MediaUpload
{
    protected array $allowedMimeTypes;
    protected array $allowedExtensions;
    protected string $uploadMediaPath = '';
    protected ?string $uploadedFileName = null;

    protected function isAllowed(string $mime, $ext): bool
    {
        foreach ($this->allowedMimeTypes as $allowed) {
            if (($mime === $allowed) && in_array($ext, $this->allowedExtensions, true)) {
                return true;
            }
            if (str_ends_with($allowed, '/*')) {
                $type = substr($allowed, 0, strpos($allowed, '/'));
                if (str_starts_with($mime, $type . '/') && in_array($ext, $this->allowedExtensions, true)) {
                    return true;
                }
            }
        }

        if ($mime === 'application/octet-stream' && in_array($ext, $this->allowedExtensions, true)) {
            return true;
        }

        return false;
    }

    /**
     * Uploads a file to the media folder.
     *
     * @param UploadedFile  $file        The input field name
     * @return string|null          New filename or null if failed
     */
    protected function uploadMedia(UploadedFile $file): ?string
    {
        if ($file && $file->isValid()) {
            $mime = $file->getMimeType();
            $ext  = strtolower($file->getClientOriginalExtension());

            if (!$this->isAllowed($mime, $ext)){
                $this->flashBag->add('error', 'File is not allowed');
            }

            $newFilename = bin2hex(random_bytes(16));
            $file->move(Path::get('MEDIA_PATH') . trim($this->uploadMediaPath, DIRECTORY_SEPARATOR), $newFilename);

            return $newFilename;
        } else {
            $this->flashBag->add('error', 'File is invalid');
        }

        return null;
    }
}

