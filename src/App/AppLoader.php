<?php

namespace Shopware\ServiceBundle\App;

use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AppLoader
{
    public function __construct(
        private readonly string $appDirectory,
        private readonly AppHasher $appHasher,
        private readonly CacheInterface $cache,
    ) {}

    /**
     * @return array<App>
     */
    public function load(): array
    {
        $appData = $this->cache->get('app-list', function (ItemInterface $item) {
            $finder = new Finder();
            $finder->files()->in($this->appDirectory)->directories()->depth('== 0');

            $apps = [];

            foreach ($finder as $folder) {
                $path = $folder->getRealPath();
                $apps[] = [
                    'location' => $path,
                    'name' => $this->getAppName($path . '/manifest.xml'),
                    'hash' => $this->appHasher->hash($path),
                ];
            }

            usort($apps, static fn($a, $b) => $a['location'] <=> $b['location']);

            return $apps;
        });

        return array_map(
            static fn($app) => new App(
                $app['location'],
                $app['name'],
                basename($app['location']),
                $app['hash'],
            ),
            $appData,
        );
    }

    private function getAppName(string $path): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('Could not find manifest.xml');
        }

        $xml = simplexml_load_string((string) file_get_contents($path));

        if ($xml === false) {
            throw new \RuntimeException('Could not parse manifest.xml');
        }

        return (string) $xml->meta->name;
    }
}
