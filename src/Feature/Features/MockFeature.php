<?php

namespace Shopware\ServiceBundle\Feature\Features;

use Shopware\ServiceBundle\Feature\Feature;
use Shopware\ServiceBundle\Feature\FeatureInstructionType;

class MockFeature implements Feature
{
    public function getConfig(): array
    {
        return [
            'name' => 'Mock Name',
        ];
    }

    public function validate(FeatureInstructionType $type): bool
    {
        return true;
    }
}