<?php

namespace ByJG\XmlUtil;

use ByJG\XmlUtil\Exception\FileException;

class File
{
    protected string $filename;

    /**
     * @param string $filename
     * @param bool $allowNotFound
     * @throws FileException
     */
    public function __construct(string $filename, bool $allowNotFound = false)
    {
        if (!file_exists($filename) && !$allowNotFound) {
            throw new FileException('File not found');
        }
        $this->filename = $filename;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     * @throws FileException
     */
    public function getContents(): string
    {
        if (!file_exists($this->filename)) {
            throw new FileException('File not found');
        }
        return file_get_contents($this->filename);
    }

    public function save(string $contents): void
    {
        file_put_contents($this->filename, $contents);
    }

}