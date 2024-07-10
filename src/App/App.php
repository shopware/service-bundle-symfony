<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\App;

readonly class App
{
    public string $revision;

    public function __construct(
        public string $location,
        public string $name,
        public string $version,
        public string $hash,
    ) {
        $this->revision = $this->version . '-' . $this->hash;
    }
}
