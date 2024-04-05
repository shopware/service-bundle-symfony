<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\Feature\Features;

use Shopware\ServiceBundle\Feature\Feature;
use Shopware\ServiceBundle\Feature\FeatureInstructionType;

class Module implements Feature
{
    public const INSTALL_REQUIRED_FIELDS = [
        'name',
        'parent',
    ];

    /**
     * @param array<string, string>|null $label
     */
    public function __construct(
        public string $name,
        public string $parent,
        public ?string $source = null,
        public ?int $position = 0,
        public ?array $label = []
    )
    {
    }

    /**
     * @param array{name?: string, parent?: string, source?: string, position?: int, label?: array<string, string>} $array
     */
    public static function fromArray(array $array): self
    {
        $label = $array['label'] ?? [];
        $transformedLabel = [];

        foreach ($label as $key => $value) {
            $transformedLabel[(string) str_replace('_', '-', $key)] = $value;
        }

        return new self(
            (string) $array['name'],
            (string) $array['parent'],
            $array['source'] ?? null,
            isset($array['position']) ? $array['position'] : 0,
            $transformedLabel
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

    /**
     * @return array{name: string, parent: string, source?: string, position?: int}
     */
    public function getConfig(): array
    {
        return array_filter([
            'name' => $this->name,
            'parent' => $this->parent,
            'source' => $this->source,
            'position' => $this->position ?? 0,
            'label' => $this->label ?? []
        ]);
    }
}