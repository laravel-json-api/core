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
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class ResourceTypeTest extends TestCase
{
    /**
     * @return ResourceType
     */
    public function testItIsValidValue(): ResourceType
    {
        $type = new ResourceType('posts');

        $this->assertSame('posts', $type->value);

        return $type;
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
        $this->expectExceptionMessage('Resource type must be a non-empty string.');
        new ResourceType($value);
    }

    /**
     * @return void
     */
    public function testItIsEqual(): void
    {
        $a = new ResourceType('posts');
        $b = new ResourceType('comments');

        $this->assertObjectEquals($a, clone $a);
        $this->assertFalse($a->equals($b));
    }

    /**
     * @param ResourceType $type
     * @return void
     * @depends testItIsValidValue
     */
    public function testItIsStringable(ResourceType $type): void
    {
        $this->assertInstanceOf(Stringable::class, $type);
        $this->assertSame($type->value, (string) $type);
        $this->assertSame($type->value, $type->toString());
    }

    /**
     * @param ResourceType $type
     * @return void
     * @depends testItIsValidValue
     */
    public function testItIsJsonSerializable(ResourceType $type): void
    {
        $this->assertJsonStringEqualsJsonString(
            json_encode(['type' => $type->value]),
            json_encode(['type' => $type]),
        );
    }

    /**
     * @param ResourceType $type
     * @return void
     * @depends testItIsValidValue
     */
    public function testItCanBeCastedToValue(ResourceType $type): void
    {
        $this->assertSame($type, ResourceType::cast($type));
        $this->assertObjectEquals($type, ResourceType::cast($type->value));
    }
}
