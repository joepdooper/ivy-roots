<?php

namespace Ivy;

use Verot\Upload\Upload;

class File
{
    protected string $directory;
    protected string $name;
    protected ?string $file_name;
    protected string $format;
    protected array $allowed;
    protected ?string $image_convert = null;
    protected ?int $width = null;

    public function upload($file): string
    {
        $handle = new Upload($file);
        $handle->allowed = $this->allowed;
        if ($handle->file_is_image) {
            !$this->image_convert ?: $handle->image_convert = $this->image_convert;
            if ($this->width) {
                $handle->image_resize = true;
                $handle->image_x = $this->width;
                $handle->image_ratio_y = true;
            }
        }
        $handle->file_new_name_body = $this->name;
        $handle->process($this->directory);
        if ($handle->processed) {
            $this->file_name = $handle->file_dst_name;
            // $handle->clean();
        } else {
            error_log('error : ' . $handle->error);
        }
        $this->image_convert = null;
        return $this->file_name;
    }

    public function delete($file): void
    {
        unlink($this->directory . $file);
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     */
    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->file_name;
    }

    /**
     * @param string $file_name
     */
    public function setFileName(string $file_name): void
    {
        $this->file_name = $file_name;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * @return array
     */
    public function getAllowed(): array
    {
        return $this->allowed;
    }

    /**
     * @param array $allowed
     */
    public function setAllowed(array $allowed): void
    {
        $this->allowed = $allowed;
    }

    /**
     * @return string|null
     */
    public function getImageConvert(): ?string
    {
        return $this->image_convert;
    }

    /**
     * @param string|null $image_convert
     */
    public function setImageConvert(string $image_convert): void
    {
        $this->image_convert = $image_convert;
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @param int|null $width
     */
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

}
