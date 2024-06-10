<?php

namespace ByJG\Util;

use ByJG\Util\Exception\XmlUtilException;

class File
{
    protected string $filename;

    public function __construct(string $filename)
    {
        if (!file_exists($filename)) {
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
        return file_get_contents($this->filename);
    }

    public function save(string $contents): void
    {
        file_put_contents($this->filename, $contents);
    }

}