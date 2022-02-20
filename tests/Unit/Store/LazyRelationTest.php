<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Store;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Store\LazyRelation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LazyRelationTest extends TestCase
{
    /**
     * @var Server|MockObject
     */
    private MockObject $server;

    /**
     * @var Store|MockObject
     */
    private MockObject $store;

    /**
     * @var Relation|MockObject
     */
    private MockObject $field;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->server = $this->createMock(Server::class);

        $this->server
            ->method('store')
            ->willReturn($this->store = $this->createMock(Store::class));

        $this->field = $this->createMock(Relation::class);
    }

    /**
     * @return array
     */
    public function validToOneProvider(): array
    {
        return [
            'user' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '1',
                    ],
                ],
            ],
            'author' => [
                [
                    'data' => [
                        'type' => 'authors',
                        'id' => '1',
                    ],
                ],
            ],
            'zero id' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '0',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $json
     * @dataProvider validToOneProvider
     */
    public function testToOne(array $json): void
    {
        $expected = new \stdClass();

        $this->setupToOne(['users', 'authors']);
        $this->willFind(
            $json['data']['type'],
            $json['data']['id'],
            $expected,
        );

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            $json,
        );

        $this->assertSame($expected, $relation->get());
        $this->assertSame($expected, $relation->get(), 'The related object must be cached.');
    }

    public function testToOneIsNull(): void
    {
        $json = ['data' => null];

        $this->setupToOne('users');
        $this->willNotUseStore();

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            $json,
        );

        $this->assertNull($relation->get());
    }

    /**
     * @return array
     */
    public function invalidIdentifierProvider(): array
    {
        return [
            'no type' => [
                [
                    'id' => '1',
                ],
            ],
            'empty type' => [
                [
                    'type' => '',
                    'id' => '1',
                ],
            ],
            'invalid type' => [
                [
                    'type' => 'tags',
                    'id' => '1',
                ],
            ],
            'no id' => [
                [
                    'type' => 'users',
                ],
            ],
            'empty id' => [
                [
                    'type' => 'users',
                    'id' => '',
                ],
            ],
        ];
    }

    /**
     * @param array $identifier
     * @dataProvider invalidIdentifierProvider
     */
    public function testToOneIsInvalid(array $identifier): void
    {
        $this->setupToOne(['users', 'authors']);
        $this->willNotUseStore();

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            ['data' => $identifier],
        );

        $this->assertNull($relation->get());
    }

    public function testToOneWithToManyField(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('to-one');

        $this->setupToMany('users');

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            ['data' => []],
        );

        $relation->get();
    }

    public function testToMany(): void
    {
        $json = [
            'data' => [
                [
                    'type' => 'users',
                    'id' => '1',
                ],
                [
                    'type' => 'authors',
                    'id' => '2',
                ],
            ],
        ];

        $this->setupToMany(['users', 'authors']);
        $expected = $this->willFindMany($json['data']);

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            $json,
        );

        $actual = $relation->collect();

        $this->assertInstanceOf(Collection::class, $actual);
        $this->assertSame($expected, $actual->all());
        $this->assertSame($expected, iterator_to_array($relation));
        $this->assertSame($expected, $relation->all());
    }

    public function testToManyIsEmpty(): void
    {
        $json = ['data' => []];

        $this->setupToMany('users');
        $this->willNotUseStore();

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            $json,
        );

        $this->assertEmpty($relation->all());
    }

    public function testToManyWithSomeInvalidIdentifiers(): void
    {
        $json = [
            'data' => [
                [
                    'type' => 'users',
                    'id' => '1',
                ],
                [
                    'type' => 'foos',
                    'id' => '999',
                ],
                [
                    'type' => 'authors',
                    'id' => '2',
                ],
                [
                    'type' => 'bars',
                    'id' => '999',
                ],
            ],
        ];

        $this->setupToMany(['users', 'authors']);
        $expected = $this->willFindMany([
            $json['data'][0],
            $json['data'][2],
        ]);

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            $json,
        );

        $this->assertSame($expected, $relation->all());
    }

    public function testToManyWithOnlyInvalidIdentifiers(): void
    {
        $json = [
            'data' => [
                [
                    'type' => 'foos',
                    'id' => '999',
                ],
                [
                    'type' => 'bars',
                    'id' => '999',
                ],
            ],
        ];

        $this->setupToMany(['users', 'authors']);
        $this->willNotUseStore();

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            $json,
        );

        $this->assertEmpty($relation->all());
    }

    /**
     * @param array $identifier
     * @dataProvider invalidIdentifierProvider
     */
    public function testToManyWithInvalidIdentifier(array $identifier): void
    {
        $json = [
            'data' => [
                $identifier,
                [
                    'type' => 'users',
                    'id' => '1',
                ],
            ],
        ];

        $this->setupToMany(['users', 'authors']);
        $expected = $this->willFindMany([$json['data'][1]]);

        $relation = new LazyRelation(
            $this->server,
            $this->field,
            $json,
        );

        $this->assertSame($expected, $relation->all());
    }

    /**
     * @param string|string[] $typeOrTypes
     * @return void
     */
    private function setupToOne($typeOrTypes): void
    {
        $this->field->method('toOne')->willReturn(true);
        $this->field->method('toMany')->willReturn(false);
        $this->field->method('allInverse')->willReturn(Arr::wrap($typeOrTypes));
    }

    /**
     * @param $typeOrTypes
     * @return void
     */
    private function setupToMany($typeOrTypes): void
    {
        $this->field->method('toOne')->willReturn(false);
        $this->field->method('toMany')->willReturn(true);
        $this->field->method('allInverse')->willReturn(Arr::wrap($typeOrTypes));
    }

    /**
     * @param string $type
     * @param string $id
     * @param object|null $result
     */
    private function willFind(string $type, string $id, ?object $result): void
    {
        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($type, $id)
            ->willReturn($result);
    }

    /**
     * @param array $identifiers
     * @return array
     */
    private function willFindMany(array $identifiers): array
    {
        $expected = Collection::make($identifiers)
            ->map(static fn (array $identifier): object => (object) $identifier)
            ->all();

        $this->store
            ->expects($this->once())
            ->method('findMany')
            ->with($identifiers)
            ->willReturn($expected);

        return $expected;
    }

    /**
     * @return void
     */
    private function willNotUseStore(): void
    {
        $this->store
            ->expects($this->never())
            ->method($this->anything());
    }
}