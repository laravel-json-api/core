<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Values;

use LaravelJsonApi\Contracts\Support\Stringable;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use PHPUnit\Framework\TestCase;

class HrefTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsValid(): void
    {
        $href = new Href($value = '/posts');

        $this->assertSame($value, $href->value);
        $this->assertInstanceOf(Stringable::class, $href);
        $this->assertSame($value, (string) $href);
        $this->assertSame($value, $href->toString());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['href' => $value]),
            json_encode(['href' => $href]),
        );
    }

    /**
     * @return array<array<string>>
     */
    public static function invalidProvider(): array
    {
        return [
            [''],
            ['  '],
        ];
    }

    /**
     * @param string $value
     * @return void
     * @dataProvider invalidProvider
     */
    public function testItIsInvalid(string $value): void
    {
        $this->expectException(\LogicException::class);
        new Href($value);
    }
}
