<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Values;

use LaravelJsonApi\Contracts\Support\Stringable;
use LaravelJsonApi\Core\Values\ResourceId;
use PHPUnit\Framework\TestCase;

class ResourceIdTest extends TestCase
{
    /**
     * @return array<array<string>>
     */
    public static function idProvider(): array
    {
        return [
            ['0'],
            ['1'],
            ['123'],
            ['006cd3cb-8ec9-412b-9293-3272b9b1338d'],
            ['01H1PRN3CPP9G18S4XSACS5WD1'],
        ];
    }

    /**
     * @param string $value
     * @return void
     * @dataProvider idProvider
     */
    public function testItIsValidValue(string $value): void
    {
        $id = new ResourceId($value);

        $this->assertSame($value, $id->value);
    }

    /**
     * @return array<array<string>>
     */
    public static function invalidProvider(): array
    {
        return [
            [''],
            ['   '],
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
        $this->expectExceptionMessage('Resource id must be a non-empty string.');
        new ResourceId($value);
    }

    /**
     * @return void
     */
    public function testItIsEqual(): void
    {
        $a = new ResourceId('006cd3cb-8ec9-412b-9293-3272b9b1338d');
        $b = new ResourceId('123');

        $this->assertObjectEquals($a, clone $a);
        $this->assertFalse($a->equals($b));
    }

    /**
     * @return void
     */
    public function testItIsStringable(): void
    {
        $id = new ResourceId('006cd3cb-8ec9-412b-9293-3272b9b1338d');

        $this->assertInstanceOf(Stringable::class, $id);
        $this->assertSame($id->value, (string) $id);
        $this->assertSame($id->value, $id->toString());
    }

    /**
     * @return void
     */
    public function testItIsJsonSerializable(): void
    {
        $id = new ResourceId('006cd3cb-8ec9-412b-9293-3272b9b1338d');

        $this->assertJsonStringEqualsJsonString(
            json_encode(['id' => '006cd3cb-8ec9-412b-9293-3272b9b1338d']),
            json_encode(['id' => $id]),
        );
    }

    /**
     * @return void
     */
    public function testItCanBeCastedToValue(): void
    {
        $id = new ResourceId('006cd3cb-8ec9-412b-9293-3272b9b1338d');

        $this->assertSame($id, ResourceId::cast($id));
        $this->assertObjectEquals($id, ResourceId::cast($id->value));
    }

    /**
     * @return void
     */
    public function testItCanBeNullable(): void
    {
        $id = new ResourceId('006cd3cb-8ec9-412b-9293-3272b9b1338d');

        $this->assertSame($id, ResourceId::nullable($id));
        $this->assertObjectEquals($id, ResourceId::nullable($id->value));
        $this->assertNull(ResourceId::nullable(null));
    }
}
