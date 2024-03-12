<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Document;

use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;
use PHPUnit\Framework\TestCase;

class LinksTest extends TestCase
{

    public function testCastSelf(): void
    {
        $links = new Links();

        $this->assertSame($links, Links::cast($links));
    }

    public function testCastNull(): void
    {
        $this->assertEquals(new Links(), Links::cast(null));
    }

    public function testCastLink(): void
    {
        $link = new Link('self', '/api/posts/1');

        $this->assertEquals(new Links($link), Links::cast($link));
    }

    public function testCastArray(): void
    {
        $link1 = new Link('foo', '/api/posts/1/foo');
        $link2 = new Link('bar', '/api/posts/1/bar');
        $expected = new Links($link1, $link2);

        $this->assertEquals($expected, Links::cast([$link1, $link2]));
        $this->assertEquals($expected, Links::cast(['foo' => $link1, 'bar' => $link2]));
        $this->assertEquals($expected, Links::cast([
            'foo' => $link1->toArray(),
            'bar' => $link2->toArray(),
        ]));
    }

    public function testCastUnexpected(): void
    {
        $this->expectException(\LogicException::class);
        Links::cast('blah');
    }

    public function testGet(): Links
    {
        $links = new Links(
            $self = new Link('self', '/api/posts/1/relationships/author'),
            $related = new Link('related', '/api/posts/1/author'),
        );

        $this->assertSame($self, $links->get('self'));
        $this->assertSame($related, $links->get('related'));
        $this->assertNull($links->get('foo'));

        return $links;
    }

    public function testSelf(): void
    {
        $self = new Link('self', '/api/posts/1/relationships/author');
        $links = new Links();

        $this->assertNull($links->getSelf());
        $this->assertFalse($links->hasSelf());

        $this->assertSame($links, $links->push($self));

        $this->assertSame($self, $links->getSelf());
        $this->assertTrue($links->hasSelf());
    }

    public function testRelated(): void
    {
        $related = new Link('related', '/api/posts/1/author');
        $links = new Links();

        $this->assertNull($links->getRelated());
        $this->assertFalse($links->hasRelated());

        $this->assertSame($links, $links->push($related));

        $this->assertSame($related, $links->getRelated());
        $this->assertTrue($links->hasRelated());
    }

    public function testPush(): void
    {
        $links = new Links();
        $self = new Link('self', '/api/posts/1/relationships/author');
        $related = new Link('related', '/api/posts/1/author');

        $this->assertSame($links, $links->push($self, $related));
        $this->assertCount(2, $links);
    }

    public function testIteratorAndAll(): void
    {
        $links = new Links(
            $self = new Link('self', '/api/posts/1/relationships/author'),
            $related = new Link('related', '/api/posts/1/author'),
        );

        $this->assertSame($expected = [
            'related' => $related,
            'self' => $self,
        ], iterator_to_array($links));

        $this->assertSame($expected, $links->all());
    }

    /**
     * @param Links $links
     * @return void
     * @depends testGet
     */
    public function testIsEmpty(Links $links): void
    {
        $this->assertFalse($links->isEmpty());
        $this->assertTrue($links->isNotEmpty());

        $empty = new Links();

        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($empty->isNotEmpty());
    }

    /**
     * @param Links $links
     * @return void
     * @depends testGet
     */
    public function testJsonSerialize(Links $links): void
    {
        $expected = <<<JSON
{
    "links": {
        "related": "/api/posts/1/author",
        "self": "/api/posts/1/relationships/author"
    }
}
JSON;

        $this->assertJsonStringEqualsJsonString($expected, json_encode(['links' => $links]));

        $empty = <<<JSON
{
    "links": null
}
JSON;

        $this->assertJsonStringEqualsJsonString($empty, json_encode(['links' => new Links()]));
    }

    /**
     * @param Links $links
     * @return void
     * @depends testGet
     */
    public function testToJson(Links $links): void
    {
        $expected = <<<JSON
{
    "related": "/api/posts/1/author",
    "self": "/api/posts/1/relationships/author"
}
JSON;

        $this->assertJsonStringEqualsJsonString($expected, $links->toJson());
        $this->assertJsonStringEqualsJsonString('null', (new Links)->toJson());
    }

    /**
     * @param Links $links
     * @return void
     * @depends testGet
     */
    public function testToArray(Links $links): void
    {
        $expected = [
            'related' => [
                'href' => '/api/posts/1/author',
            ],
            'self' => [
                'href' => '/api/posts/1/relationships/author',
            ],
        ];

        $this->assertSame($expected, $links->toArray());
    }

    public function testMerge(): void
    {
        $self = new Link('self', '/api/posts/1/relationships/author');
        $related = new Link('related', '/api/posts/1/author');

        $links1 = new Links($self);
        $links2 = new Links($related);

        $this->assertSame($links1, $links1->merge($links2));
        $this->assertSame(['related' => $related, 'self' => $self], $links1->all());
        $this->assertSame(['related' => $related], $links2->all());
    }

    public function testForget(): void
    {
        $links = new Links(
            $self = new Link('self', '/api/posts/1/relationships/author'),
            new Link('related', '/api/posts/1/author'),
            new Link('foo', '/api/posts/1/-actions/foo')
        );

        $this->assertSame($links, $links->forget('related', 'foo'));
        $this->assertSame(['self' => $self], $links->all());
    }

    public function testOffsetGet(): Links
    {
        $links = new Links(
            $self = new Link('self', '/api/posts/1/relationships/author'),
            $related = new Link('related', '/api/posts/1/author'),
        );

        $this->assertSame($self, $links['self']);
        $this->assertSame($related, $links['related']);

        return $links;
    }

    /**
     * @param Links $links
     * @return void
     * @depends testOffsetGet
     */
    public function testOffsetExists(Links $links): void
    {
        $this->assertTrue(isset($links['self']));
        $this->assertFalse(isset($links['foo']));
    }

    public function testOffsetSet(): void
    {
        $self = new Link('self', '/api/posts/1/relationships/author');
        $related = new Link('related', '/api/posts/1/author');

        $links = new Links();
        $links['self'] = $self;
        $links['related'] = $related;

        $this->assertSame(['related' => $related, 'self' => $self], $links->all());
    }

    public function testOffsetSetWithInvalidKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Expecting link to have the key 'related'.");

        $links = new Links();
        $links['related'] = new Link('self', '/api/posts/1/relationships/author');
    }

    public function testOffsetSetWithInvalidObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expecting a link object.');

        $links = new Links();
        $links['related'] = new \DateTime();
    }

    public function testOffsetUnset(): void
    {
        $links = new Links(
            new Link('self', '/api/posts/1/relationships/author'),
            $related = new Link('related', '/api/posts/1/author'),
        );

        unset($links['self'], $links['foo']);

        $this->assertSame(['related' => $related], $links->all());
    }
}
