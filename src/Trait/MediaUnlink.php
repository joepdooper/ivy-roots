<?php
namespace Ivy\Trait;

use Ivy\Manager\SessionManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ivy\Core\Path;

trait MediaUnlink
{
    public function unlink(string $fileName): string
    {
        if($fileName){
            unlink(Path::get('MEDIA_PATH') . trim($this->uploadMediaPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName);
        }
    }

}

