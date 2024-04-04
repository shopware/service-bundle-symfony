<?php

namespace Shopware\ServiceBundle\Manifest;

class Manifest
{
    public function __construct(public string $version, public string $filePath)
    {
    }

    public function getContent(): string
    {
        return file_get_contents($this->filePath);
    }

    public function hash(): string
    {
        return hash_file('sha256', $this->filePath);
    }
}