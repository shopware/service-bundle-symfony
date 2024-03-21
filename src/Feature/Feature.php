<?php

namespace Shopware\ServiceBundle\Feature;

interface Feature
{
    public function getConfig(): array;

    public function validate(FeatureInstructionType $type): bool;
}