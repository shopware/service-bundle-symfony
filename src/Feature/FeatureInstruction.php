<?php

namespace Shopware\ServiceBundle\Feature;

readonly class FeatureInstruction
{
    /**
     * @param array<Condition> $conditions
     */
    public function __construct(
        public string $name,
        public FeatureInstructionType $type,
        public string $minimumShopwareVersion,
        public ?Feature $feature = null,
        public array $conditions = []
    ) {}

    public static function removal(string $name, string $minimumShopwareVersion): self
    {
        return new self($name, FeatureInstructionType::REMOVE, $minimumShopwareVersion);
    }

    public function match(ShopOperation $shop): bool
    {
        if ($shop->toVersion === null) {
            return false;
        }
        
        if ($shop->fromVersion === null) {
            //it's a new shop so we always match
            return true;
        }

        if ($shop->fromVersion && version_compare($shop->fromVersion, $this->minimumShopwareVersion, '>')) {
            return false;
        }

        if (version_compare($shop->toVersion, $this->minimumShopwareVersion, '<')) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (!$condition->match($shop)) {
                return false;
            }
        }

        return true;
    }
}