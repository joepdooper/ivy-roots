<?php
namespace Ivy\Trait;

use Ivy\Manager\SessionManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ivy\Core\Path;

trait ImageProcess
{
    /**
     * Resize an image to the given width/height.
     * Keeps aspect ratio unless both width and height are forced.
     *
     * @param string $source Path to source image
     * @param string $destination Path to save resized image
     * @param int|null $maxWidth Max width in px
     * @param int|null $maxHeight Max height in px
     * @return bool True on success, false on failure
     */
    protected function resizeImage(string $source, string $destination, ?int $maxWidth, ?int $maxHeight): bool
    {
        [$origWidth, $origHeight, $type] = getimagesize($source);

        if (!$origWidth || !$origHeight) {
            return false;
        }

        // Preserve aspect ratio
        $ratio = $origWidth / $origHeight;

        if ($maxWidth && $maxHeight) {
            $newWidth  = $maxWidth;
            $newHeight = $maxHeight;
        } elseif ($maxWidth) {
            $newWidth  = $maxWidth;
            $newHeight = (int) round($maxWidth / $ratio);
        } elseif ($maxHeight) {
            $newHeight = $maxHeight;
            $newWidth  = (int) round($maxHeight * $ratio);
        } else {
            return false; // nothing to resize
        }

        // Load source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($source);
                break;
            default:
                return false;
        }

        // Create destination canvas
        $dst = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG & GIF
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF], true)) {
            imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        // Resample
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Save image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($dst, $destination, 90);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($dst, $destination, 6);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($dst, $destination);
                break;
            default:
                $result = false;
        }

        imagedestroy($src);
        imagedestroy($dst);

        return $result;
    }
}

