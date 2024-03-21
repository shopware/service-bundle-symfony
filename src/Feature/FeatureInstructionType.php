<?php

namespace Shopware\ServiceBundle\Feature;

enum FeatureInstructionType
{
    case INSTALL;
    case REMOVE;
    case UPDATE;
}