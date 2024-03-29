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
        $this->assertTrue($id->match('1234,5678,90', ','));
        $this->assertTrue($id->match('1234-5678-90', '-'));
        $this->assertTrue($id->match('1234', ','));
        $this->assertTrue($id->matchAll(['1234', '5678', '90']));
        $this->assertFalse($id->match('123A45'));
        $this->assertFalse($id->match('1234,567E,1234', ','));
        $this->assertFalse($id->matchAll(['1234', '5678', '90E']));
    }

    /**
     * @return void
     */
    public function testItIsUuid(): void
    {
        $id = new class () {
            use MatchesIds;
        };

        $uuids = [
            '1e1cc75c-dc37-488d-b862-828529088261',
            'fca1509e-9178-45fd-8a2b-ae819d34f7e6',
            '2935a487-85e1-4f3c-b585-cd64e9a776f3',
        ];

        $this->assertSame($id, $id->uuid());
        $this->assertSame('[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}', $id->pattern());
        $this->assertTrue($id->match($uuids[0]));
        $this->assertTrue($id->match(implode(',', $uuids), ','));
        $this->assertTrue($id->match($uuids[0], ','));
        $this->assertTrue($id->matchAll($uuids));
        $this->assertFalse($id->match('fca1509e917845fd8a2bae819d34f7e6'));
        $this->assertFalse($id->match(implode(',', $invalid = [...$uuids, 'fca1509e917845fd8a2bae819d34f7e6']), ','));
        $this->assertFalse($id->matchAll($invalid));
    }

    /**
     * @return void
     */
    public function testItIsUlid(): void
    {
        $id = new class () {
            use MatchesIds;
        };

        $ulids = [
            '01HT4PA8AZC8Q30ZGC5PEWZP0E',
            '01HT4QSVZXQX89AZNSXGYYB3PB',
            '01HT4QT51KE7NJ12SDS48N3CWB',
        ];

        $this->assertSame($id, $id->ulid());
        $this->assertSame('[0-7][0-9a-hjkmnp-tv-zA-HJKMNP-TV-Z]{25}', $id->pattern());
        $this->assertTrue($id->match($ulids[0]));
        $this->assertTrue($id->match(implode(',', $ulids), ','));
        $this->assertTrue($id->matchAll($ulids));
        $this->assertFalse($id->match('01HT4PA8AZC8Q30ZGC5PEWZP0'));
        $this->assertFalse($id->match(implode(',', $invalid = [...$ulids, '01HT4PA8AZC8Q30ZGC5PEWZP0']), ','));
        $this->assertFalse($id->matchAll($invalid));
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