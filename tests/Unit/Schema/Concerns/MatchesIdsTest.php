<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Schema\Concerns;

use LaravelJsonApi\Core\Schema\Concerns\MatchesIds;
use PHPUnit\Framework\TestCase;

class MatchesIdsTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsNumeric(): void
    {
        $id = new class () {
            use MatchesIds;
        };

        $this->assertSame('[0-9]+', $id->pattern());
        $this->assertTrue($id->match('1234'));
        $this->assertFalse($id->match('123A45'));
    }

    /**
     * @return void
     */
    public function testItIsUuid(): void
    {
        $id = new class () {
            use MatchesIds;
        };

        $this->assertSame($id, $id->uuid());
        $this->assertSame('[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}', $id->pattern());
        $this->assertTrue($id->match('fca1509e-9178-45fd-8a2b-ae819d34f7e6'));
        $this->assertFalse($id->match('fca1509e917845fd8a2bae819d34f7e6'));
    }

    /**
     * @return void
     */
    public function testItIsUlid(): void
    {
        $id = new class () {
            use MatchesIds;
        };

        $this->assertSame($id, $id->ulid());
        $this->assertSame('[0-7][0-9a-hjkmnp-tv-zA-HJKMNP-TV-Z]{25}', $id->pattern());
        $this->assertTrue($id->match('01HT4PA8AZC8Q30ZGC5PEWZP0E'));
        $this->assertFalse($id->match('01HT4PA8AZC8Q30ZGC5PEWZP0'));
    }

    /**
     * @return void
     */
    public function testItIsCustom(): void
    {
        $id = new class () {
            use MatchesIds;
        };

        $this->assertSame($id, $id->matchAs('[A-D]+'));
        $this->assertSame('[A-D]+', $id->pattern());
        $this->assertTrue($id->match('abcd'));
        $this->assertTrue($id->match('ABCD'));
        $this->assertFalse($id->match('abcde'));

        $this->assertSame($id, $id->matchCase());
        $this->assertTrue($id->match('ABCD'));
        $this->assertFalse($id->match('abcd'));
    }
}