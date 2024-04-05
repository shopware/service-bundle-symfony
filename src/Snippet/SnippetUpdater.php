<?php

namespace Shopware\ServiceBundle\Snippet;

use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Shop\ShopInterface;

class SnippetUpdater
{
    public function __construct(
        private readonly ClientFactory $clientFactory
    ){
    }

    public function updateSnippetsInShop(string $author, array $snippets, ShopInterface $shop): void
    {
        if (empty($snippets)) {
            return;
        }

        $client = $this->clientFactory->createClient($shop);

        $snippetSets = $this->getSnippetSets($client);

        $writeData = [];
        foreach ($snippetSets as $snippetSet) {
            $writeData = array_merge($writeData, $this->getEntityData(
                $client,
                $snippetSet,
                $snippets[$snippetSet['iso']] ?? $snippets[$snippetSet['en-GB']]
            ));
        }

        $client->sendRequest(new Request(
            'POST',
            '/api/_action/sync',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            [
                'update-snippets' => [
                    'entity' => 'snippet',
                    'action' => 'upsert',
                    'payload' => $writeData
                ]
            ]
        ));
    }

    private function getSnippetSets(ClientInterface $client): array
    {
        $response = $client->sendRequest(new Request(
            'GET',
            '/api/snippet-set',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ));

        if ($response->getStatusCode() !== 200) {
            // throw
        }

        $body = \json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return $body['data'] ?? [];
    }

    private function getEntityData(ClientInterface $client, mixed $snippetSet, array $snippets)
    {
        $persistedSnippets = $this->fetchSnippetsFromShop($client, $snippetSet['id'], array_keys($snippets));

        $payload = [];
        foreach ($snippets as $translationKey => $snippet) {
            $entityData = [
                'translationKey' => $translationKey,
                'value' => $snippet,
                'author' => $this->author,
                'setId' => $snippetSet['id'],
            ];

            if (isset($persistedSnippets[$translationKey]) &&
                $persistedSnippets[$translationKey]['author'] !== $this->author
            ) {
                // don't override snippets changed by the merchant
                continue;
            }

            if (isset($persistedSnippets[$translationKey])) {
                $entityData['id'] = $persistedSnippets[$translationKey]['id'];
            }

            $payload[] = $entityData;
        }

        return $payload;
    }

    private function fetchSnippetsFromShop(ClientInterface $client, string $snippetSetId, array $translationKeys): array
    {
        $snippetSearch = $client->sendRequest(new Request(
            'POST',
            '/api/search/snippet',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            [
                'filter' => [
                    [
                        'type' => 'equals',
                        'field' => 'setId',
                        'value' => $snippetSetId,
                    ],
                    [
                        'type' => 'equalsAny',
                        'field' => 'translationKey',
                        'value' => $translationKeys,
                    ]
                ]
            ]
        ));

        $snippets = \json_decode(
            $snippetSearch['data'] ?? [],
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $keyed = [];
        foreach ($snippets as $snippet) {
            $keyed[$snippet['translationKey']] = $snippet;
        }

        return $keyed;
    }
}