<?php

namespace Shopware\ServiceBundle\Snippet;

use Symfony\Component\Finder\Finder;

class SnippetLoader
{
    public function __construct(
        private readonly string $snippetBasePath
    ){
    }

    public function loadSnippets(): array
    {
        $finder = new Finder();

        $files = $finder
            ->files()
            ->in($this->snippetBasePath)
            ->name('/[a-z]{2}-[A-Z]{2,3}\.json/')
            ->getIterator();

        $snippets = [];

        foreach ($files as $index => $file) {
            $locale = $file->getBasename('.json');

            $structured = json_decode(
                file_get_contents($file->getRealPath()),
                true,
                flags: JSON_THROW_ON_ERROR
            );

            $snippets[$locale] = $this->flatten($structured);
        }

        return $snippets;
    }

    /**
     * @return array<string, string>
     */
    private function flatten(array $structured, string $baseName = ''): array
    {
        $flatValues = [];

        foreach ($structured as $key => $value) {
            $currentKey = $baseName === '' ? $key : $baseName. '.' . $key;

            if (is_array($value)) {
                $flatValues = array_merge(
                    $flatValues,
                    $this->flatten($value, $currentKey)
                );
            } else {
                $flatValues[$currentKey] = $value;
            }
        }

        return $flatValues;
    }
}