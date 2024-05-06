<?php declare(strict_types=1);

namespace Shopware\ServiceBundle\Manifest;

class Manifest
{
    public readonly string $hash;
    public function __construct(public string $version, public string $content)
    {
        $this->hash = hash('sha256', $this->content);
    }
}