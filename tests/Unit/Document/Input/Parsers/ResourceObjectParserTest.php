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

use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use PHPUnit\Framework\TestCase;

class ResourceObjectParserTest extends TestCase
{
    /**
     * @var ResourceObjectParser
     */
    private ResourceObjectParser $parser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ResourceObjectParser();
    }

    /**
     * @return void
     */
    public function testItParsesWithoutIdAndLid(): void
    {
        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => 'Hello World!',
            ],
            'relationships' => [
                'author' => [
                    'data' => null,
                ],
            ],
            'meta' => [
                'foo' => 'bar',
            ],
        ];

        $actual = $this->parser->parse($data);

        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $data]),
            json_encode(['data' => $actual]),
        );
    }

    /**
     * @return void
     */
    public function testItParsesWithLidWithoutId(): void
    {
        $data = [
            'type' => 'posts',
            'lid' => '01H1PRN3CPP9G18S4XSACS5WD1',
            'attributes' => [
                'title' => 'Hello World!',
            ],
        ];

        $actual = $this->parser->parse($data);

        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $data]),
            json_encode(['data' => $actual]),
        );
    }

    /**
     * @return void
     */
    public function testItParsesWithIdWithoutLid(): void
    {
        $data = [
            'type' => 'posts',
            'id' => '01H1PRN3CPP9G18S4XSACS5WD1',
            'attributes' => [
                'title' => 'Hello World!',
            ],
        ];

        $actual = $this->parser->parse($data);

        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $data]),
            json_encode(['data' => $actual]),
        );
    }

    /**
     * @return void
     */
    public function testItParsesWithIdAndLid(): void
    {
        $data = [
            'type' => 'posts',
            'id' => '123',
            'lid' => '01H1PRN3CPP9G18S4XSACS5WD1',
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => '456',
                    ],
                ],
            ],
        ];

        $actual = $this->parser->parse($data);

        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $data]),
            json_encode(['data' => $actual]),
        );
    }

    /**
     * @return void
     */
    public function testItMustHaveType(): void
    {
        $data = [
            'type' => null,
            'id' => '01H1PRN3CPP9G18S4XSACS5WD1',
            'attributes' => [
                'title' => 'Hello World!',
            ],
        ];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Resource object array must contain a type.');
        $this->parser->parse($data);
    }
}
