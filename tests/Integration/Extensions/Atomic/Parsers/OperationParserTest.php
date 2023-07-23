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

namespace LaravelJsonApi\Core\Tests\Integration\Extensions\Atomic\Parsers;

use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Extensions\Atomic\Parsers\OperationParser;
use LaravelJsonApi\Core\Tests\Integration\TestCase;

class OperationParserTest extends TestCase
{
    /**
     * @var OperationParser
     */
    private OperationParser $parser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = $this->container->make(OperationParser::class);
    }

    /**
     * @return void
     */
    public function testItParsesStoreOperationWithHref(): void
    {
        $op = $this->parser->parse($json = [
            'op' => 'add',
            'href' => '/posts',
            'data' => [
                'type' => 'posts',
                'attributes' => [
                    'title' => 'Hello World!',
                ],
            ],
        ]);

        $this->assertInstanceOf(Create::class, $op);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($op),
        );
    }

    /**
     * Check "href" is not compulsory for a store operation.
     *
     * @return void
     */
    public function testItParsesStoreOperationWithoutHref(): void
    {
        $op = $this->parser->parse($json = [
            'op' => 'add',
            'data' => [
                'type' => 'posts',
                'attributes' => [
                    'title' => 'Hello World!',
                ],
            ],
            'meta' => ['foo' => 'bar'],
        ]);

        $this->assertInstanceOf(Create::class, $op);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItParsesUpdateOperationWithRef(): void
    {
        $op = $this->parser->parse($json = [
            'op' => 'update',
            'ref' => [
                'type' => 'posts',
                'id' => '123',
            ],
            'data' => [
                'type' => 'posts',
                'id' => '123',
                'attributes' => [
                    'title' => 'Hello World',
                ],
            ],
            'meta' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertInstanceOf(Update::class, $op);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItParsesUpdateOperationWithHref(): void
    {
        $op = $this->parser->parse($json = [
            'op' => 'update',
            'href' => '/posts/123',
            'data' => [
                'type' => 'posts',
                'id' => '123',
                'attributes' => [
                    'title' => 'Hello World',
                ],
            ],
        ]);

        $this->assertInstanceOf(Update::class, $op);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItParsesUpdateOperationWithoutTarget(): void
    {
        $op = $this->parser->parse($json = [
            'op' => 'update',
            'data' => [
                'type' => 'posts',
                'id' => '123',
                'attributes' => [
                    'title' => 'Hello World',
                ],
            ],
        ]);

        $this->assertInstanceOf(Update::class, $op);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItParsesDeleteOperationWithHref(): void
    {
        $op = $this->parser->parse($json = [
            'op' => 'remove',
            'href' => '/posts/123',
            'meta' => ['foo' => 'bar'],
        ]);

        $this->assertInstanceOf(Delete::class, $op);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItParsesDeleteOperationWithRef(): void
    {
        $op = $this->parser->parse($json = [
            'op' => 'remove',
            'ref' => [
                'type' => 'posts',
                'id' => '123',
            ],
        ]);

        $this->assertInstanceOf(Delete::class, $op);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItIsIndeterminate(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Operation array must have a valid op code.');
        $this->parser->parse(['op' => 'blah!']);
    }
}
