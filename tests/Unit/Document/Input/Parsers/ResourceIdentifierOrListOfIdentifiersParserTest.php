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

use LaravelJsonApi\Core\Document\Input\Parsers\ListOfResourceIdentifiersParser;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierOrListOfIdentifiersParser;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierParser;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResourceIdentifierOrListOfIdentifiersParserTest extends TestCase
{
    /**
     * @var ResourceIdentifierParser&MockObject
     */
    private ResourceIdentifierParser&MockObject $identifierParser;

    /**
     * @var ListOfResourceIdentifiersParser
     */
    private ListOfResourceIdentifiersParser $listParser;

    /**
     * @var ResourceIdentifierOrListOfIdentifiersParser
     */
    private ResourceIdentifierOrListOfIdentifiersParser $parser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ResourceIdentifierOrListOfIdentifiersParser(
            $this->identifierParser = $this->createMock(ResourceIdentifierParser::class),
            $this->listParser = $this->createMock(ListOfResourceIdentifiersParser::class),
        );
    }

    /**
     * @return void
     */
    public function testItParsesIdentifier(): void
    {
        $expected = new ResourceIdentifier(
            new ResourceType('posts'),
            new ResourceId('1'),
        );

        $this->identifierParser
            ->method('parse')
            ->with($data = $expected->toArray())
            ->willReturn($expected);

        $this->listParser
            ->expects($this->never())
            ->method('parse');

        $this->assertSame($expected, $this->parser->parse($data));
        $this->assertSame($expected, $this->parser->nullable($data));
    }

    /**
     * @return void
     */
    public function testItParsesList(): void
    {
        $expected = new ListOfResourceIdentifiers(new ResourceIdentifier(
            new ResourceType('posts'),
            new ResourceId('1'),
        ));

        $this->listParser
            ->method('parse')
            ->with($data = $expected->toArray())
            ->willReturn($expected);

        $this->identifierParser
            ->expects($this->never())
            ->method('parse');

        $this->assertSame($expected, $this->parser->parse($data));
        $this->assertSame($expected, $this->parser->nullable($data));
    }

    /**
     * @return void
     */
    public function testItParsesEmpty(): void
    {
        $this->listParser
            ->method('parse')
            ->with([])
            ->willReturn($expected = new ListOfResourceIdentifiers());

        $this->identifierParser
            ->expects($this->never())
            ->method('parse');

        $this->assertSame($expected, $this->parser->parse([]));
    }

    /**
     * @return void
     */
    public function testItParsesNull(): void
    {
        $this->identifierParser
            ->expects($this->never())
            ->method('parse');

        $this->listParser
            ->expects($this->never())
            ->method('parse');

        $this->assertNull($this->parser->nullable(null));
    }
}
