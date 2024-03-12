<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Document\Input\Parsers;

use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierParser;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class ResourceIdentifierParserTest extends TestCase
{
    /**
     * @var ResourceIdentifierParser
     */
    private ResourceIdentifierParser $parser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ResourceIdentifierParser();
    }

    /**
     * @return void
     */
    public function testItParsesIdentifierWithId(): void
    {
        $expected = new ResourceIdentifier(
            type: new ResourceType('posts'),
            id: new ResourceId('123'),
            meta: ['foo' => 'bar'],
        );

        $actual = $this->parser->parse($data = [
            'type' => 'posts',
            'id' => '123',
            'meta' => ['foo' => 'bar'],
        ]);

        $this->assertEquals($expected, $actual);
        $this->assertEquals($expected, $this->parser->nullable($data));
    }

    /**
     * @return void
     */
    public function testItParsesIdentifierWithLid(): void
    {
        $expected = new ResourceIdentifier(
            type: new ResourceType('posts'),
            lid: new ResourceId('adb083bd-2474-422f-93c9-5ef64e257e92'),
        );

        $actual = $this->parser->parse($data = [
            'type' => 'posts',
            'lid' => 'adb083bd-2474-422f-93c9-5ef64e257e92',
        ]);

        $this->assertEquals($expected, $actual);
        $this->assertEquals($expected, $this->parser->nullable($data));
    }

    /**
     * @return void
     */
    public function testItParsesIdentifierWithLidAndId(): void
    {
        $expected = new ResourceIdentifier(
            type: new ResourceType('posts'),
            id: new ResourceId('123'),
            lid: new ResourceId('adb083bd-2474-422f-93c9-5ef64e257e92'),
        );

        $actual = $this->parser->parse($data = [
            'type' => 'posts',
            'id' => '123',
            'lid' => 'adb083bd-2474-422f-93c9-5ef64e257e92',
        ]);

        $this->assertEquals($expected, $actual);
        $this->assertEquals($expected, $this->parser->nullable($data));
    }

    /**
     * @return void
     */
    public function testItParsesNull(): void
    {
        $this->assertNull($this->parser->nullable(null));
    }
}
