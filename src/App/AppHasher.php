<?php

namespace Shopware\ServiceBundle\App;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AppHasher
{
    public function hash(string $folder): string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $data = [];
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $data[] = hash_file('sha256', $file->getPathname());
            $data[] = $file->getFilename();
        }

        return hash('sha256', implode('', $data));
    }
}
