<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\Feature\Features;

use Shopware\ServiceBundle\Feature\Feature;
use Shopware\ServiceBundle\Feature\FeatureInstructionType;

class MainModule implements Feature
{
    public const INSTALL_REQUIRED_FIELDS = [
        'source',
    ];

    public function __construct(public string $source)
    {
    }

    /**
     * @param array{source?: string} $array
     */
    public static function fromArray(array $array): self
    {
        return new self((string) $array['source']);
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

    /**
     * @return array{source: string}
     */
    public function getConfig(): array
    {
        return array_filter([
            'source' => $this->source,
        ]);
    }
}