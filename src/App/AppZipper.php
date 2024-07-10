<?php

namespace Shopware\ServiceBundle\App;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class AppZipper
{
    public function zip(App $app): string
    {
        $zip = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');

        if ($tempFile === false) {
            throw new \RuntimeException('Could not create temp file');
        }

        if ($zip->open($tempFile, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Could not create zip file');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($app->location, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($files as $file) {
            /** @var \SplFileObject $file */
            $filePath = $file->getRealPath();

            $zipPath = $app->name . '/' . substr($filePath, strlen($app->location) + 1);

            $zip->addFile($filePath, $zipPath);
        }

        $zip->close();

        $content =  (string) file_get_contents($tempFile);

        unlink($tempFile);

        return $content;
    }
}
