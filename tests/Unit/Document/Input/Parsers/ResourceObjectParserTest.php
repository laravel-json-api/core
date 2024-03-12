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

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Resource object array must contain a type.');
        $this->parser->parse($data);
    }
}
