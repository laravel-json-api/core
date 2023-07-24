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

    /**
     * @return array
     */
    public static function relationshipNameProvider(): array
    {
        return [
            ['/posts/123', null],
            ['/posts/123/relationships/author', 'author'],
            ['/posts/123/relationships/blog-author', 'blog-author'],
            ['/posts/123/relationships/blog_author', 'blog_author'],
            ['/posts/123/relationships/blog-author_123', 'blog-author_123'],
        ];
    }

    /**
     * @param string $href
     * @param string|null $expected
     * @return void
     * @dataProvider relationshipNameProvider
     */
    public function testRelationshipName(string $href, ?string $expected): void
    {
        $href = new Href($href);
        $this->assertSame($expected, $href->getRelationshipName());
        $this->assertSame($expected !== null, $href->hasRelationshipName());
    }
}
