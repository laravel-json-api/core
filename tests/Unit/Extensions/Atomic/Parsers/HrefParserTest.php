<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Parsers;

use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Extensions\Atomic\Parsers\HrefParser;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HrefParserTest extends TestCase
{
    /**
     * @var MockObject&Server
     */
    private Server&MockObject $server;

    /**
     * @var MockObject&Container
     */
    private Container&MockObject $schemas;

    /**
     * @var HrefParser
     */
    private HrefParser $parser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new HrefParser(
            $this->server = $this->createMock(Server::class),
        );

        $this->server
            ->method('schemas')
            ->willReturn($this->schemas = $this->createMock(Container::class));
    }

    /**
     * @return array[]
     */
    public static function hrefProvider(): array
    {
        return [
            'create:domain' => [
                new ParsedHref(
                    new Href('https://example.com/api/v1/posts'),
                    new ResourceType('posts'),
                ),
            ],
            'create:relative' => [
                new ParsedHref(
                    new Href('/api/v1/blog-posts'),
                    new ResourceType('blog-posts'),
                ),
            ],
            'update:domain' => [
                new ParsedHref(
                    new Href('https://example.com/api/v1/posts/123'),
                    new ResourceType('posts'),
                    new ResourceId('123'),
                ),
            ],
            'update:relative' => [
                new ParsedHref(
                    new Href('/api/v1/blog-posts/b66f6f48-50ce-4145-bf0b-c78c6d76fe88'),
                    new ResourceType('blog-posts'),
                    new ResourceId('b66f6f48-50ce-4145-bf0b-c78c6d76fe88'),
                ),
            ],
            'update-relationship:domain' => [
                new ParsedHref(
                    new Href('https://example.com/api/v1/posts/123/relationships/tags'),
                    new ResourceType('posts'),
                    new ResourceId('123'),
                    'tags',
                ),
            ],
            'update-relationship:relative dash-case' => [
                new ParsedHref(
                    new Href('/api/v1/blog-posts/b66f6f48-50ce-4145-bf0b-c78c6d76fe88/relationships/blog-tags'),
                    new ResourceType('blog-posts'),
                    new ResourceId('b66f6f48-50ce-4145-bf0b-c78c6d76fe88'),
                    'blog-tags',
                ),
            ],
            'update-relationship:relative camel-case' => [
                new ParsedHref(
                    new Href('/api/v1/blogPosts/b66f6f48-50ce-4145-bf0b-c78c6d76fe88/relationships/blogTags'),
                    new ResourceType('blogPosts'),
                    new ResourceId('b66f6f48-50ce-4145-bf0b-c78c6d76fe88'),
                    'blogTags',
                ),
            ],
            'update-relationship:relative snake-case' => [
                new ParsedHref(
                    new Href('/api/v1/blog_posts/b66f6f48-50ce-4145-bf0b-c78c6d76fe88/relationships/blog_tags'),
                    new ResourceType('blog_posts'),
                    new ResourceId('b66f6f48-50ce-4145-bf0b-c78c6d76fe88'),
                    'blog_tags',
                ),
            ],
        ];
    }

    /**
     * @param ParsedHref $expected
     * @return void
     * @dataProvider hrefProvider
     */
    public function testItParsesHref(ParsedHref $expected): void
    {
        $this->server
            ->expects($this->once())
            ->method('url')
            ->with($this->identicalTo([]))
            ->willReturn('https://example.com/api/v1');

        $this->withSchema($expected);

        $actual = $this->parser->parse($expected->href);

        $this->assertEquals($expected, $actual);
        $this->assertSame(
            $expected->relationship !== null,
            $this->parser->hasRelationship($expected->href->value),
        );
    }
    /**
     * @param ParsedHref $in
     * @return void
     * @dataProvider hrefProvider
     */
    public function testItParsesHrefAndConvertsUriSegmentsToExpectedValues(ParsedHref $in): void
    {
        $expected = new ParsedHref(
            $in->href,
            new ResourceType('foo'),
            $in->id,
            $in->relationship ? 'bar' : null,
        );

        $this->server
            ->expects($this->once())
            ->method('url')
            ->with($this->identicalTo([]))
            ->willReturn('https://example.com/api/v1');

        $this->withSchema($in, $expected->type, $expected->relationship);

        $actual = $this->parser->parse($in->href);

        $this->assertEquals($expected, $actual);
        $this->assertSame(
            $in->relationship !== null,
            $this->parser->hasRelationship($in->href->value),
        );
    }


    /**
     * @param ParsedHref $expected
     * @param ResourceType|null $type
     * @param string|null $relationship
     * @return void
     */
    private function withSchema(ParsedHref $expected, ?ResourceType $type = null, ?string $relationship = null): void
    {
        $type = $type ?? $expected->type;

        $this->schemas
            ->expects($this->once())
            ->method('schemaTypeForUri')
            ->with($expected->type->value)
            ->willReturn($type);

        $this->schemas
            ->expects($this->once())
            ->method('schemaFor')
            ->with($this->identicalTo($type))
            ->willReturn($schema = $this->createMock(Schema::class));

        if ($expected->id) {
            $schema
                ->expects($this->once())
                ->method('id')
                ->willReturn($id = $this->createMock(ID::class));
            $id
                ->expects($this->once())
                ->method('match')
                ->with($expected->id->value)
                ->willReturn(true);
        }

        if ($expected->relationship) {
            $schema
                ->expects($this->once())
                ->method('relationshipForUri')
                ->with($expected->relationship)
                ->willReturn($relation = $this->createMock(Relation::class));
            $relation
                ->expects($this->once())
                ->method('name')
                ->willReturn($relationship ?? $expected->relationship);
        }
    }
}
