<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\App;

class AppSelector
{
    public function __construct(
        private readonly AppLoader $appLoader,
    ) {}

    public function select(string $shopwareVersion): App
    {
        $apps = $this->appLoader->load();

        $selectedVersion = null;

        foreach (array_reverse($apps) as $app) {
            if (version_compare($app->version, $shopwareVersion, '<=')) {
                $selectedVersion = $app;
                break;
            }
        }

        if (!$selectedVersion instanceof App) {
            throw NoSupportedAppException::fromShopwareVersion($shopwareVersion);
        }

        return $selectedVersion;
    }

    public function specific(string $appVersion): App
    {
        foreach ($this->appLoader->load() as $app) {
            if ($app->version === $appVersion) {
                return $app;
            }
        }

        throw NoSupportedAppException::fromAppVersion($appVersion);
    }
}
