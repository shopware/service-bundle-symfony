<?php

namespace Shopware\ServiceBundle\Manifest;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

class ManifestSelector
{
    public function __construct(private string $manifestDirectory)
    {
    }

    public function choose(string $shopwareVersion): Manifest
    {
        $finder = new Finder();
        $finder->files()->in($this->manifestDirectory)->name('*.xml');

        $files = [];

        foreach ($finder as $file) {
            preg_match('/manifest-(\d.\d.\d.\d).xml/', $file, $matches);
            $files[$matches[1]] = $file->getRealPath();
        }

        ksort($files);

        $selectedVersion = null;
        foreach (array_reverse($files) as $version => $path) {
            if (version_compare($version, $shopwareVersion, '<=')) {
                $selectedVersion = new Manifest($version, $path);
                break;
            }
        }
        
        if (!$selectedVersion instanceof Manifest) {
            throw NoSupportedManifestException::fromShopwareVersion($shopwareVersion);
        }

        return $selectedVersion;
    }
}