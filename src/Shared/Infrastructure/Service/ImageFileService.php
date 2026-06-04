<?php

namespace Ivy\Shared\Infrastructure\Service;

use Ivy\Shared\Core\Path;
use Ivy\Shared\Domain\ValueObject\ImageFile;
use Random\RandomException;
use RuntimeException;

class ImageFileService extends FileService
{
    /** @var ImageFile[] */
    protected array $files = [];

    /**
     * @throws RandomException
     */
    public function upload(): void
    {
        foreach ($this->files as $file) {
            $file->validate();

            if (! $file->getFileName()) {
                $file->generateFileName();
            }

            $tmpPath = $file->getUploadFile()->getPathname();

            $imageInfo = getimagesize($tmpPath);

            if ($imageInfo === false) {
                throw new FileException('Failed to read image information');
            }

            [$origWidth, $origHeight, $type] = $imageInfo;

            $src = match ($type) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($tmpPath),
                IMAGETYPE_PNG  => imagecreatefrompng($tmpPath),
                IMAGETYPE_GIF  => imagecreatefromgif($tmpPath),
                IMAGETYPE_WEBP => imagecreatefromwebp($tmpPath),
                default => throw new FileException('Unsupported image type'),
            };

            if (! $src) {
                throw new FileException('Failed to create image resource');
            }

            $targetDir = rtrim(Path::get('MEDIA_PATH'), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . trim($file->getUploadPath(), DIRECTORY_SEPARATOR);

            if (! is_writable($targetDir)) {
                throw new FileException('Upload directory is not writable: ' . $targetDir);
            }

            $fileName = $file->getFileName();

            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

            $webpPath = $targetDir
                . DIRECTORY_SEPARATOR
                . pathinfo($fileName, PATHINFO_FILENAME)
                . '.webp';

            $maxWidth = (int) $file->getImageWidth();

            $writeOriginal = function ($resource) use ($type, $targetPath) {
                return match ($type) {
                    IMAGETYPE_JPEG => imagejpeg($resource, $targetPath, 90),
                    IMAGETYPE_PNG  => imagepng($resource, $targetPath),
                    IMAGETYPE_GIF  => imagegif($resource, $targetPath),
                    IMAGETYPE_WEBP => imagewebp($resource, $targetPath, 80),
                    default => false,
                };
            };

            if ($maxWidth <= 0) {
                copy($tmpPath, $targetPath);

                if (! imagewebp($src, $webpPath, 80)) {
                    throw new FileException('Failed to write webp: ' . $webpPath);
                }

                return;
            }

            if ($origWidth <= $maxWidth) {
                copy($tmpPath, $targetPath);

                if (! imagewebp($src, $webpPath, 80)) {
                    throw new FileException('Failed to write webp: ' . $webpPath);
                }

                return;
            }

            $ratio = $origWidth / $origHeight;
            $newWidth = $maxWidth;
            $newHeight = (int) round($newWidth / $ratio);

            $dst = imagecreatetruecolor($newWidth, $newHeight);

            if (! $dst) {
                throw new FileException('Failed to create destination image');
            }

            if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);

                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefill($dst, 0, 0, $transparent);
            }

            imagecopyresampled(
                $dst,
                $src,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $origWidth,
                $origHeight
            );

            if (! $writeOriginal($dst)) {
                throw new FileException('Failed to write original image: ' . $targetPath);
            }

            if (! imagewebp($dst, $webpPath, 80)) {
                throw new FileException('Failed to write webp: ' . $webpPath);
            }
        }
    }
}
