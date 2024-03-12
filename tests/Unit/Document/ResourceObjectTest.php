<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

namespace LaravelJsonApi\Core\Tests\Unit\Document;

use Illuminate\Contracts\Routing\UrlRoutable;
use LaravelJsonApi\Core\Document\ResourceObject;
use PHPUnit\Framework\TestCase;

class ResourceObjectTest extends TestCase
{

    /**
     * @var array
     */
    private array $values;

    /**
     * @var ResourceObject
     */
    private ResourceObject $resource;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->values = [
            'type' => 'posts',
            'id' => '1',
            'attributes' => [
                'content' => '...',
                'published' => null,
                'title' => 'Hello World',
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => '123',
                    ],
                    'links' => [
                        'related' => 'http://localhost/api/v1/posts/1/author',
                        'self' => 'http://localhost/api/v1/posts/1/relationships/author',
                    ],
                ],
                'comments' => [
                    'links' => [
                        'related' => 'http://localhost/api/v1/posts/1/comments',
                        'self' => 'http://localhost/api/v1/posts/1/relationships/comments',
                    ],
                ],
                'tags' => [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => '4',
                        ],
                        [
                            'type' => 'tags',
                            'id' => '5',
                        ],
                    ],
                    'links' => [
                        'related' => 'http://localhost/api/v1/posts/1/tags',
                        'self' => 'http://localhost/api/v1/posts/1/relationships/tags',
                    ],
                    'meta' => [
                        'count' => 2,
                    ],
                ],
            ],
            'links' => [
                'self' => 'http://localhost/api/v1/posts/1',
            ],
        ];

        $this->resource = ResourceObject::cast($this->values);
    }

    public function testTypeAndId(): void
    {
        $this->assertSame($this->values['type'], $this->resource->getType());
        $this->assertSame($this->values['id'], $this->resource->getId());
    }

    public function testIdIsZero(): void
    {
        $this->values['id'] = '0';

        $resource = ResourceObject::cast($this->values);

        $this->assertSame('0', $resource->getId());
    }

    public function testFields(): array
    {
        $expected = [
            'author' => [
                'type' => 'users',
                'id' => '123',
            ],
            'content' => '...',
            'id' => '1',
            'published' => null,
            'tags' => [
                [
                    'type' => 'tags',
                    'id' => '4',
                ],
                [
                    'type' => 'tags',
                    'id' => '5',
                ],
            ],
            'title' => 'Hello World',
            'type' => 'posts',
        ];

        $this->assertSame($expected, $this->resource->all(), 'all');
        $this->assertSame($expected, iterator_to_array($this->resource), 'iterator');

        $this->assertSame($fields = [
            'author',
            'comments', // we expect comments to be included even though it has no data.
            'content',
            'id',
            'published',
            'tags',
            'title',
            'type',
        ], $this->resource->fields()->all(), 'fields');

        $this->assertTrue($this->resource->has(...$fields), 'has all fields');
        $this->assertFalse($this->resource->has('title', 'foobar'), 'does not have field');

        return $expected;
    }

    public function testFieldsWithEmptyToOne(): void
    {
        $this->values['relationships']['author']['data'] = null;

        $expected = [
            'author' => null,
            'content' => '...',
            'id' => '1',
            'published' => null,
            'tags' => [
                [
                    'type' => 'tags',
                    'id' => '4',
                ],
                [
                    'type' => 'tags',
                    'id' => '5',
                ],
            ],
            'title' => 'Hello World',
            'type' => 'posts',
        ];

        $resource = ResourceObject::cast($this->values);
        $this->assertSame($expected, $resource->all());
        $this->assertNull($resource['author']);
        $this->assertNull($resource->get('author', true));
    }

    /**
     * @param array $expected
     * @depends testFields
     */
    public function testGetValue(array $expected): void
    {
        foreach ($expected as $field => $value) {
            $this->assertTrue(isset($this->resource[$field]), "$field exists as array");
            $this->assertTrue(isset($this->resource[$field]), "$field exists as object");
            $this->assertSame($value, $this->resource[$field], "$field array value");
            $this->assertSame($value, $this->resource->{$field}, "$field object value");
            $this->assertSame($value, $this->resource->get($field), "$field get value");
        }

        $this->assertFalse(isset($this->resource['foo']), 'foo does not exist');
    }

    public function testGetWithDotNotation(): void
    {
        $this->assertSame('123', $this->resource->get('author.id'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertSame('123', $this->resource->get('author.id', true));
        $this->assertNull($this->resource->get('published', true));
        $this->assertTrue($this->resource->get('foobar', true));
    }

    /**
     * Fields share a common namespace, so if there is a duplicate field
     * name in the attributes and relationships, there is a collision.
     * We expect the relationship to be returned.
     */
    public function testDuplicateFields(): void
    {
        $this->values['attributes']['author'] = null;

        $resource = ResourceObject::cast($this->values);

        $this->assertSame($this->values['relationships']['author']['data'], $resource['author']);
    }

    public function testCannotSetOffset(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('immutable');
        $this->resource['foo'] = 'bar';
    }

    public function testCannotUnsetOffset(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('immutable');
        unset($this->resource['content']);
    }

    public function testCannotUnset(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('immutable');
        unset($this->resource->content);
    }

    public function testCannotSet(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('immutable');
        $this->resource['foo'] = 'bar';
    }

    /**
     * @return array
     */
    public static function pointerProvider(): array
    {
        return [
            ['type', '/type'],
            ['id', '/id'],
            ['title', '/attributes/title'],
            ['title.foo.bar', '/attributes/title/foo/bar'],
            ['author', '/relationships/author'],
            ['author.type', '/relationships/author/data/type'],
            ['tags.0.id', '/relationships/tags/data/0/id'],
            ['comments', '/relationships/comments'],
            ['foo', '/'],
        ];
    }

    /**
     * @param string $key
     * @param string $expected
     * @dataProvider pointerProvider
     */
    public function testPointer(string $key, string $expected): void
    {
        $this->assertSame($expected, $this->resource->pointer($key));
        $this->assertSame($expected, $this->resource->pointer($key, '/'), 'with slash prefix');
    }

    /**
     * @param string $key
     * @param string $expected
     * @dataProvider pointerProvider
     */
    public function testPointerWithPrefix(string $key, string $expected): void
    {
        // @see https://github.com/cloudcreativity/laravel-json-api/issues/255
        $expected = rtrim("/data" . $expected, '/');

        $this->assertSame($expected, $this->resource->pointer($key, '/data'));
    }

    /**
     * @return array
     */
    public static function pointerForRelationshipProvider(): array
    {
        return [
            ['author', null],
            ['author.type', '/data/type'],
            ['tags.0.id', '/data/0/id'],
            ['tags', null],
        ];
    }

    /**
     * @param string $key
     * @param string|null $expected
     * @return void
     * @dataProvider pointerForRelationshipProvider
     */
    public function testPointerForRelationship(string $key, ?string $expected): void
    {
        if (!is_null($expected)) {
            $this->assertSame($expected, $this->resource->pointerForRelationship($key, '/foo/bar'));
            return;
        }

        $this->assertSame('/', $this->resource->pointerForRelationship($key));
        $this->assertSame('/data', $this->resource->pointerForRelationship($key, '/data'));
    }

    public function testPointerForRelationshipNotRelationship(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not a relationship');

        $this->resource->pointerForRelationship('title');
    }

    public function testForget(): void
    {
        $expected = $this->values;
        unset($expected['attributes']['content']);
        unset($expected['relationships']['comments']);

        $this->assertNotSame($this->resource, $actual = $this->resource->forget('content', 'comments'));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testOnly(): void
    {
        $expected = [
            'type' => $this->values['type'],
            'id' => $this->values['id'],
            'attributes' => [
                'content' => $this->values['attributes']['content'],
            ],
            'relationships' => [
                'comments' => $this->values['relationships']['comments'],
            ],
            'links' => $this->values['links'],
        ];

        $this->assertNotSame($this->resource, $actual = $this->resource->only('content', 'comments'));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testReplaceTypeAndId(): void
    {
        $expected = $this->values;
        $expected['type'] = 'foobars';
        $expected['id'] = '999';

        $actual = $this->resource
            ->replace('type', 'foobars')
            ->replace('id', '999');

        $this->assertNotSame($this->resource, $actual);
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testReplaceAttribute(): void
    {
        $expected = $this->values;
        $expected['attributes']['content'] = 'My first post.';

        $this->assertNotSame($this->resource, $actual = $this->resource->replace('content', 'My first post.'));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testReplaceToOne(): void
    {
        $author = ['type' => 'users', 'id' => '999'];

        $expected = $this->values;
        $expected['relationships']['author']['data'] = $author;

        $this->assertNotSame($this->resource, $actual = $this->resource->replace('author', $author));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testReplaceToOneWithUrlRoutable(): void
    {
        $mock = $this->createMock(UrlRoutable::class);
        $mock->method('getRouteKey')->willReturn(999);

        $author = ['type' => 'users', 'id' => $mock];

        $expected = $this->values;
        $expected['relationships']['author']['data'] = ['type' => 'users', 'id' => '999'];

        $this->assertNotSame($this->resource, $actual = $this->resource->replace('author', $author));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testReplaceToOneNull(): void
    {
        $expected = $this->values;
        $expected['relationships']['author']['data'] = null;

        $this->assertNotSame($this->resource, $actual = $this->resource->replace('author', null));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testReplaceToMany(): void
    {
        $mock = $this->createMock(UrlRoutable::class);
        $mock->method('getRouteKey')->willReturn(999);

        $comments = [
            ['type' => 'comments', 'id' => '123456'],
            ['type' => 'comments', 'id' => $mock],
        ];

        $expected = $this->values;
        $expected['relationships']['comments']['data'] = [
            ['type' => 'comments', 'id' => '123456'],
            ['type' => 'comments', 'id' => '999'],
        ];

        $this->assertNotSame($this->resource, $actual = $this->resource->replace('comments', $comments));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testPutAttribute(): void
    {
        $expected = $this->values;
        $expected['attributes']['foobar'] = 'My first post.';
        ksort($expected['attributes']);

        $this->assertNotSame($this->resource, $actual = $this->resource->put('foobar', 'My first post.'));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testPutArrayAttribute(): void
    {
        $expected = $this->values;
        $expected['attributes']['foobar'] = ['baz', 'bat'];
        ksort($expected['attributes']);

        $this->assertNotSame($this->resource, $actual = $this->resource->put('foobar', ['baz', 'bat']));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testPutToOne(): void
    {
        $author = ['type' => 'users', 'id' => '999'];

        $expected = $this->values;
        $expected['relationships']['foobar']['data'] = $author;
        ksort($expected['relationships']);

        $this->assertNotSame($this->resource, $actual = $this->resource->putRelation('foobar', $author));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testPutToOneWithUrlRoutable(): void
    {
        $mock = $this->createMock(UrlRoutable::class);
        $mock->method('getRouteKey')->willReturn(999);

        $author = ['type' => 'users', 'id' => $mock];

        $expected = $this->values;
        $expected['relationships']['foobar']['data'] = ['type' => 'users', 'id' => '999'];
        ksort($expected['relationships']);

        $this->assertNotSame($this->resource, $actual = $this->resource->putRelation('foobar', $author));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testPutToOneNull(): void
    {
        $expected = $this->values;
        $expected['relationships']['foobar']['data'] = null;
        ksort($expected['relationships']);

        $this->assertNotSame($this->resource, $actual = $this->resource->putRelation('foobar', null));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testPutToMany(): void
    {
        $mock = $this->createMock(UrlRoutable::class);
        $mock->method('getRouteKey')->willReturn(999);

        $comments = [
            ['type' => 'comments', 'id' => '123456'],
            ['type' => 'comments', 'id' => $mock],
        ];

        $expected = $this->values;
        $expected['relationships']['foobar']['data'] = [
            ['type' => 'comments', 'id' => '123456'],
            ['type' => 'comments', 'id' => '999'],
        ];
        ksort($expected['relationships']);

        $this->assertNotSame($this->resource, $actual = $this->resource->putRelation('foobar', $comments));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithType(): void
    {
        $expected = $this->values;
        $expected['type'] = 'foobar';

        $this->assertNotSame($this->resource, $actual = $this->resource->withType('foobar'));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithoutId(): void
    {
        $expected = $this->values;
        unset($expected['id']);

        $this->assertNotSame($this->resource, $actual = $this->resource->withoutId());
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithId(): void
    {
        $expected = $this->values;
        $expected['id'] = '99';

        $this->assertNotSame($this->resource, $actual = $this->resource->withId('99'));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithZeroId(): void
    {
        $expected = $this->values;
        $expected['id'] = '0';

        $this->assertNotSame($this->resource, $actual = $this->resource->withId('0'));
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithAttributes(): void
    {
        $expected = $this->values;
        $expected['attributes'] = ['foo' => 'bar'];

        $this->assertNotSame($this->resource, $actual = $this->resource->withAttributes($expected['attributes']));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithoutAttributes(): void
    {
        $expected = $this->values;
        unset($expected['attributes']);

        $this->assertNotSame($this->resource, $actual = $this->resource->withoutAttributes());
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithRelationships(): void
    {
        $expected = $this->values;
        $expected['relationships'] = [
            'foo' => ['data' => ['type' => 'foos', 'id' => 'bar']]
        ];

        $this->assertNotSame($this->resource, $actual = $this->resource->withRelationships($expected['relationships']));
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithoutRelationships(): void
    {
        $expected = $this->values;
        unset($expected['relationships']);

        $this->assertNotSame($this->resource, $actual = $this->resource->withoutRelationships());
        $this->assertSame($this->values, $this->resource->jsonSerialize(), 'original resource is not modified');
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testJsonSerialize(): void
    {
        $this->assertJsonStringEqualsJsonString(
            json_encode($this->values),
            json_encode($this->resource)
        );
    }

    public function testWithoutLinks(): void
    {
        $expected = $this->values;

        unset(
            $expected['links'],
            $expected['relationships']['author']['links'],
            $expected['relationships']['comments'], // was links-only
            $expected['relationships']['tags']['links'],
        );

        $this->assertNotSame($this->resource, $resource = $this->resource->withoutLinks());

        $this->assertSame($this->values, $this->resource->jsonSerialize());
        $this->assertSame($expected, $resource->jsonSerialize());

        /**
         * Although comments cannot now appear in the JSON (because it was links-only),
         * the resource should remember that `comments` is a relationship.
         */
        $this->assertTrue(isset($resource['comments']));
        $this->assertTrue($resource->has('comments'));
        $this->assertTrue($resource->isRelationship('comments'));
        $this->assertNull($resource->get('comments'));

        $fields = $resource->all();

        $this->assertArrayNotHasKey('comments', $fields);
    }

    public function testWithoutMeta(): void
    {
        $this->values['meta'] = ['foo' => 'bar'];
        $this->resource = ResourceObject::fromArray($this->values);

        $expected = $this->values;

        unset(
            $expected['meta'],
            $expected['relationships']['tags']['meta'],
        );

        $this->assertNotSame($this->resource, $resource = $this->resource->withoutMeta());

        $this->assertSame($this->values, $this->resource->jsonSerialize());
        $this->assertSame($expected, $resource->jsonSerialize());
    }

    public function testWithRelationshipMeta(): void
    {
        $expected = $this->values;
        $expected['relationships']['comments']['meta'] = ['count' => 5];

        $actual = $this->resource->withRelationshipMeta('comments', ['count' => 5]);

        $this->assertNotSame($this->resource, $actual);
        $this->assertSame($this->values, $this->resource->jsonSerialize());
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testWithRelationshipMetaOnNewRelationship(): void
    {
        $expected = $this->values;
        $expected['relationships']['likes'] = [];
        $expected['relationships']['likes']['meta'] = ['count' => 5];
        ksort($expected['relationships']);

        $actual = $this->resource->withRelationshipMeta('likes', ['count' => 5]);

        $this->assertNotSame($this->resource, $actual);
        $this->assertSame($this->values, $this->resource->jsonSerialize());
        $this->assertSame($expected, $actual->jsonSerialize());
    }

    public function testMerge(): void
    {
        $merge = [
            'type' => 'posts',
            'id' => '1',
            'attributes' => [
                'slug' => 'hello-world',
                'title' => 'Helloooooo World!',
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'blog-users',
                        'id' => '456',
                    ],
                ],
                'site' => [
                    'data' => [
                        'type' => 'sites',
                        'id' => '2',
                    ],
                ],
                'tags' => [
                    'data' => [
                        [
                            'tags' => '999',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $this->values;
        $expected['attributes']['slug'] = $merge['attributes']['slug'];
        $expected['attributes']['title'] = $merge['attributes']['title'];
        $expected['relationships']['author']['data'] = $merge['relationships']['author']['data'];
        $expected['relationships']['site'] = $merge['relationships']['site'];
        $expected['relationships']['tags']['data'] = $merge['relationships']['tags']['data'];

        ksort($expected['attributes']);
        ksort($expected['relationships']);

        $this->assertNotSame($this->resource, $merged = $this->resource->merge($merge));

        $this->assertSame($this->values, $this->resource->jsonSerialize());
        $this->assertSame($expected, $merged->jsonSerialize());
    }

    public function testFromString(): void
    {
        $json = json_encode(['data' => $this->values]);

        $this->assertEquals($this->resource, ResourceObject::fromString($json));
    }
}
