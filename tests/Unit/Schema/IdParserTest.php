<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
