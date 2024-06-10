<?php

namespace ByJG\Util;

use ByJG\Util\Exception\XmlUtilException;

class File
{
    protected string $filename;

    public function __construct(string $filename, bool $allowNotFound = false)
    {
        if (!file_exists($filename) && !$allowNotFound) {
            throw new XmlUtilException('File not found');
        }
        $this->filename = $filename;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getContents(): string
    {
        if (!file_exists($this->filename)) {
            throw new XmlUtilException('File not found');
        }
        return file_get_contents($this->filename);
    }

    public function save(string $contents): void
    {
        file_put_contents($this->filename, $contents);
    }

}