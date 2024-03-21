<?php

namespace Shopware\ServiceBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Shopware\AppBundle\Entity\AbstractShop;

#[Entity]
class Shop extends AbstractShop
{
    #[Column(type: 'string')]
    public string $shopVersion;
}
