<?php

namespace Shopware\ServiceBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Shopware\AppBundle\Entity\AbstractShop;

#[Entity]
class Shop extends AbstractShop
{
    #[Column(type: 'string', nullable: true)]
    public ?string $shopVersion = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $selectedAppVersion = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $selectedAppHash = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $commercialLicenseKey = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $commercialLicenseHost = null;
}
