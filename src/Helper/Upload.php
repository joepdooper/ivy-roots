<?php

namespace Ivy\Helper;

use finfo;
use Intervention\Image\ImageManagerStatic as Image;

class Upload
{
    public function __construct(
        protected string $directory,
        protected array  $allowed = [],       // e.g. ['image/jpeg','image/png','audio/mpeg']
        protected ?int   $resizeImageToWidth = null, // Only for images
    ) {}

    /**
     * @return string File name on success, or null on failure
     */
    public function handle(array $file): ?string
    {
        // Check for php upload errors
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        // Ensure upload folder exists
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true)) {
            throw new \RuntimeException('Cannot create upload directory');
        }

        // Detect actual MIME
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($file['tmp_name']);
        $allowed = $this->allowed;

        // Validate allowed MIME
        if ($allowed && !in_array($mime, $allowed, true)) {
            return null;
        }

        // Generate a safe random file name (keeps extension if possible)
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: $this->guessExtension($mime);
        $filename  = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');

        $target = rtrim($this->directory, '/') . '/' . $filename;

        // If it's an image and we want resizing
        if ($this->resizeImageToWidth && str_starts_with($mime, 'image/')) {
            $image = Image::make($file['tmp_name']);
            $image->resize($this->resizeImageToWidth, null, fn ($constraint) => $constraint->aspectRatio());
            $image->save($target);
        } else {
            // Otherwise move safely
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                return null;
            }
        }

        return $filename;
    }

    public function delete(string $file): void
    {
        $path = rtrim($this->directory, '/') . '/' . $file;
        if (is_file($path)) {
            unlink($path);
        }
    }

    protected function guessExtension(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'audio/mpeg' => 'mp3',
            'audio/wav'  => 'wav',
            default      => null,
        };
    }
}
