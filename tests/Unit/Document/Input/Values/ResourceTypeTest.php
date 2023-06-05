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

namespace LaravelJsonApi\Core\Tests\Unit\Document\Input\Values;

use LaravelJsonApi\Contracts\Support\Stringable;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
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
    public function invalidProvider(): array
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
