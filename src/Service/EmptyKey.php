<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\Service;

use Lcobucci\JWT\Signer\Key;

final class EmptyKey implements Key
{
    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public function contents(): string
    {
        return 'test';
    }

    public function passphrase(): string
    {
        return '';
    }
}
