<?php declare(strict_types=1);

namespace Shopware\ServiceBundle\Manifest;

class Manifest
{
    public readonly string $hash;
    public function __construct(public string $version, public string $filePath)
    {
        $this->hash = hash_file('sha256', $this->filePath);
    }

    public function getContent(): string
    {
        return file_get_contents($this->filePath);
    }
}