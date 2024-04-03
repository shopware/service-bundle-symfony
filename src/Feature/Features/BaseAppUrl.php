<?php

namespace Shopware\ServiceBundle\Feature\Features;

use Shopware\ServiceBundle\Feature\Feature;
use Shopware\ServiceBundle\Feature\FeatureInstructionType;

class BaseAppUrl implements Feature
{
    public const INSTALL_REQUIRED_FIELDS = [
        'url',
    ];

    public function __construct(public ?string $url = null)
    {
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['url'] ?? null,
        );
    }

    public function validate(FeatureInstructionType $type): bool
    {
        if ($type === FeatureInstructionType::INSTALL) {
            foreach (self::INSTALL_REQUIRED_FIELDS as $field) {
                if ($this->$field === null) {
                    return false;
                }
            }
        }

        return true;
    }


    public function getConfig(): array
    {
        return array_filter([
            'url' => $this->url,
        ]);
    }
}