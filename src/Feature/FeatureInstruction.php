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
    ) {}

    public static function removal(string $name, string $minimumShopwareVersion): self
    {
        return new self($name, FeatureInstructionType::REMOVE, $minimumShopwareVersion);
    }

    public function match(ShopOperation $shop): bool
    {
        //uninstall case
        if ($shop->toVersion === null) {
            return false;
        }

        //updating, but from a version where this feature is already installed
        //eg this feature is for 6.6. But the update is for 6.7 -> 6.8
        if ($shop->fromVersion && version_compare($shop->fromVersion, $this->minimumShopwareVersion, '>')) {
            return false;
        }

        //updating or installing, but to a version less than this requirement
        if (version_compare($shop->toVersion, $this->minimumShopwareVersion, '<')) {
            return false;
        }

        return true;
    }
}