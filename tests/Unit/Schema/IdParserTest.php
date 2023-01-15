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

namespace LaravelJsonApi\Core\Tests\Unit\Schema;

use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Contracts\Schema\IdEncoder as IdEncoderContract;
use LaravelJsonApi\Core\Schema\IdParser;
use PHPUnit\Framework\TestCase;

class IdParserTest extends TestCase
{

    public function testEncoder(): void
    {
        $encoder = IdParser::encoder($this->createMock(ID::class));

        $this->assertEquals(new IdParser(null), $encoder);

        $encoder = $this->createMock(IdEncoderContract::class);
        $this->assertSame($encoder, IdParser::encoder($encoder));
    }

    public function testWithoutField(): void
    {
        $encoder = IdParser::make();

        $this->assertSame('123', $encoder->encode(123));
        $this->assertSame('456', $encoder->decode('456'));
        $this->assertSame(['1', '2', '3'], $encoder->encodeIds([1, 2, 3]));
        $this->assertSame(['4', '5', '6'], $encoder->decodeIds(['4', '5', '6']));
    }

    public function testWithField(): void
    {
        $mock = $this->createMock(TestId::class);
        $mock->method('match')->willReturnCallback(fn($value) => '999' !== $value);
        $mock->method('encode')->willReturnCallback(fn(int $value) => strval($value + 10));
        $mock->method('decode')->willReturnCallback(function (string $value) {
            if ('99' === $value) {
                return null;
            }

            return intval($value) - 10;
        });

        $encoder = IdParser::make($mock);

        $this->assertSame('21', $encoder->encode(11));
        $this->assertSame(11, $encoder->decode('21'));
        $this->assertSame(['11', '12', '13'], $encoder->encodeIds([1, 2, 3]));
        $this->assertSame([1, 2, 3], $encoder->decodeIds(['11', '999', '12', '99', '13']));
    }
}
