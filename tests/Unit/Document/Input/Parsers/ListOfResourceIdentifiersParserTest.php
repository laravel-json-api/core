<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Document\Input\Parsers;

use LaravelJsonApi\Core\Document\Input\Parsers\ListOfResourceIdentifiersParser;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierParser;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
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
