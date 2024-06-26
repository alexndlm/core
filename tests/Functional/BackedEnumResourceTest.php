<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6264\Availability;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6264\AvailabilityStatus;
use Symfony\Component\HttpClient\HttpOptions;

final class BackedEnumResourceTest extends ApiTestCase
{
    public static function providerEnumItemsJson(): iterable
    {
        // Integer cases
        foreach (Availability::cases() as $case) {
            yield ['/availabilities/'.$case->value, 'application/json', ['value' => $case->value]];

            yield ['/availabilities/'.$case->value, 'application/vnd.api+json', [
                'data' => [
                    'id' => '/availabilities/'.$case->value,
                    'type' => 'Availability',
                    'attributes' => [
                        'value' => $case->value,
                    ],
                ],
            ]];

            yield ['/availabilities/'.$case->value, 'application/hal+json', [
                '_links' => [
                    'self' => [
                        'href' => '/availabilities/'.$case->value,
                    ],
                ],
                'value' => $case->value,
            ]];

            yield ['/availabilities/'.$case->value, 'application/ld+json', [
                '@context' => '/contexts/Availability',
                '@id' => '/availabilities/'.$case->value,
                '@type' => 'Availability',
                'value' => $case->value,
            ]];
        }

        // String cases
        foreach (AvailabilityStatus::cases() as $case) {
            yield ['/availability_statuses/'.$case->value, 'application/json', ['value' => $case->value]];

            yield ['/availability_statuses/'.$case->value, 'application/vnd.api+json', [
                'data' => [
                    'id' => '/availability_statuses/'.$case->value,
                    'type' => 'AvailabilityStatus',
                    'attributes' => [
                        'value' => $case->value,
                    ],
                ],
            ]];

            yield ['/availability_statuses/'.$case->value, 'application/hal+json', [
                '_links' => [
                    'self' => [
                        'href' => '/availability_statuses/'.$case->value,
                    ],
                ],
                'value' => $case->value,
            ]];

            yield ['/availability_statuses/'.$case->value, 'application/ld+json', [
                '@context' => '/contexts/AvailabilityStatus',
                '@id' => '/availability_statuses/'.$case->value,
                '@type' => 'AvailabilityStatus',
                'value' => $case->value,
            ]];
        }
    }

    /** @dataProvider providerEnumItemsJson */
    public function testItemJson(string $uri, string $mimeType, array $expected): void
    {
        self::createClient()->request('GET', $uri, ['headers' => ['Accept' => $mimeType]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals($expected);
    }

    public static function providerEnumItemsGraphQl(): iterable
    {
        // Integer cases
        $query = <<<'GRAPHQL'
query GetAvailability($identifier: ID!) {
    availability(id: $identifier) {
        value
    }
}
GRAPHQL;
        foreach (Availability::cases() as $case) {
            yield [$query, ['identifier' => '/availabilities/'.$case->value], ['data' => ['availability' => ['value' => $case->value]]]];
        }

        // String cases
        $query = <<<'GRAPHQL'
query GetAvailabilityStatus($identifier: ID!) {
    availabilityStatus(id: $identifier) {
        value
    }
}
GRAPHQL;
        foreach (AvailabilityStatus::cases() as $case) {
            yield [$query, ['identifier' => '/availability_statuses/'.$case->value], ['data' => ['availability_status' => ['value' => $case->value]]]];
        }
    }

    /**
     * @dataProvider providerEnumItemsGraphQl
     *
     * @group legacy
     */
    public function testItemGraphql(string $query, array $variables, array $expected): void
    {
        $options = (new HttpOptions())
            ->setJson(['query' => $query, 'variables' => $variables])
            ->setHeaders(['Content-Type' => 'application/json']);
        self::createClient()->request('POST', '/graphql', $options->toArray());

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals($expected);
    }

    public function testCollectionJson(): void
    {
        self::createClient()->request('GET', '/availabilities', ['headers' => ['Accept' => 'application/json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            ['value' => 0],
            ['value' => 10],
            ['value' => 200],
        ]);
    }

    public function testCollectionJsonApi(): void
    {
        self::createClient()->request('GET', '/availabilities', ['headers' => ['Accept' => 'application/vnd.api+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'links' => [
                'self' => '/availabilities',
            ],
            'meta' => [
                'totalItems' => 3,
            ],
            'data' => [
                [
                    'id' => '/availabilities/0',
                    'type' => 'Availability',
                    'attributes' => [
                        'value' => 0,
                    ],
                ],
                [
                    'id' => '/availabilities/10',
                    'type' => 'Availability',
                    'attributes' => [
                        'value' => 10,
                    ],
                ],
                [
                    'id' => '/availabilities/200',
                    'type' => 'Availability',
                    'attributes' => [
                        'value' => 200,
                    ],
                ],
            ],
        ]);
    }

    public function testCollectionHal(): void
    {
        self::createClient()->request('GET', '/availabilities', ['headers' => ['Accept' => 'application/hal+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '_links' => [
                'self' => [
                    'href' => '/availabilities',
                ],
                'item' => [
                    ['href' => '/availabilities/0'],
                    ['href' => '/availabilities/10'],
                    ['href' => '/availabilities/200'],
                ],
            ],
            'totalItems' => 3,
            '_embedded' => [
                'item' => [
                    [
                        '_links' => [
                            'self' => ['href' => '/availabilities/0'],
                        ],
                        'value' => 0,
                    ],
                    [
                        '_links' => [
                            'self' => ['href' => '/availabilities/10'],
                        ],
                        'value' => 10,
                    ],
                    [
                        '_links' => [
                            'self' => ['href' => '/availabilities/200'],
                        ],
                        'value' => 200,
                    ],
                ],
            ],
        ]);
    }

    public function testCollectionJsonLd(): void
    {
        self::createClient()->request('GET', '/availabilities', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/Availability',
            '@id' => '/availabilities',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 3,
            'hydra:member' => [
                [
                    '@id' => '/availabilities/0',
                    '@type' => 'Availability',
                    'value' => 0,
                ],
                [
                    '@id' => '/availabilities/10',
                    '@type' => 'Availability',
                    'value' => 10,
                ],
                [
                    '@id' => '/availabilities/200',
                    '@type' => 'Availability',
                    'value' => 200,
                ],
            ],
        ]);
    }
}
