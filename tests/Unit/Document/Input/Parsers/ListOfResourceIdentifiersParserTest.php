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
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierParser;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class ListOfResourceIdentifiersParserTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $parser = new ListOfResourceIdentifiersParser(
            $identifierParser = $this->createMock(ResourceIdentifierParser::class),
        );

        $a = new ResourceIdentifier(new ResourceType('posts'), new ResourceId('123'));
        $b = new ResourceIdentifier(new ResourceType('tags'), new ResourceId('456'));

        $identifierParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->willReturnCallback(fn (array $data): ResourceIdentifier => match ($data['type']) {
                'posts' => $a,
                'tags' => $b,
            });

        $actual = $parser->parse([
            ['type' => 'posts', 'id' => '123'],
            ['type' => 'tags', 'id' => '456'],
        ]);

        $this->assertSame([$a, $b], $actual->all());
    }
}
