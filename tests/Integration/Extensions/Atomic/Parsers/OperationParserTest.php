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

use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Store;
use LaravelJsonApi\Core\Extensions\Atomic\Parsers\OperationParser;
use LaravelJsonApi\Core\Support\ContractException;
use PHPUnit\Framework\TestCase;

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
        $this->parser = new OperationParser(
            new Pipeline(new Container()),
        );
    }

    /**
     * @return void
     */
    public function testItParsesStoreOperation(): void
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

        $this->assertInstanceOf(Store::class, $op);
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
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Indeterminate operation.');
        $this->parser->parse(['op' => 'blah!']);
    }
}
