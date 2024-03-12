<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Query\Custom;

use Illuminate\Support\Arr;
use LaravelJsonApi\Core\Query\Custom\CountablePaths;
use LaravelJsonApi\Core\Query\Custom\ExtendedQueryParameters;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\FilterParameters;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Query\SortFields;
use PHPUnit\Framework\TestCase;

class ExtendedQueryParametersTest extends TestCase
{


    public function test(): ExtendedQueryParameters
    {
        $parameters = ExtendedQueryParameters::cast($value = [
            'bazbat' => 'foobar',
            'fields' => [
                'comments' => 'user,content',
                'posts' => 'author,createdAt,synopsis,title',
            ],
            'filter' => ['foo' => 'bar', 'baz' => 'bat'],
            'foobar' => 'bazbat',
            'include' => 'author,comments.user',
            'page' => ['number' => '1', 'size' => '25'],
            'sort' => '-createdAt,id',
            'withCount' => 'comments,tags',
        ]);

        $this->assertInstanceOf(ExtendedQueryParameters::class, $parameters);
        $this->assertEquals(FieldSets::fromArray($value['fields']), $parameters->sparseFieldSets());
        $this->assertEquals(FilterParameters::fromArray($value['filter']), $parameters->filter());
        $this->assertEquals(IncludePaths::fromString($value['include']), $parameters->includePaths());
        $this->assertEquals($value['page'], $parameters->page());
        $this->assertEquals(SortFields::fromString($value['sort']), $parameters->sortFields());
        $this->assertEquals(new CountablePaths('comments', 'tags'), $parameters->countable());

        /**
         * Our extended query parameters e.g. withCount, need to appear in unrecognised parameters,
         * because the interface defines that as returning all parameters that are not defined in the
         * JSON:API spec.
         */
        $this->assertEquals([
            'foobar' => 'bazbat',
            'bazbat' => 'foobar',
            'withCount' => 'comments,tags',
        ], $parameters->unrecognisedParameters());

        $arr = $value;
        $arr['include'] = ['author', 'comments.user'];
        $arr['sort'] = ['-createdAt', 'id'];
        $arr['withCount'] = ['comments', 'tags'];

        $this->assertSame($arr, $parameters->toArray());
        $this->assertSame($value, $parameters->toQuery());
        $this->assertSame(Arr::query($value), (string) $parameters);

        return $parameters;
    }

    /**
     * @param ExtendedQueryParameters $expected
     * @depends test
     */
    public function testCastBaseQueryParameters(ExtendedQueryParameters $expected): void
    {
        $base = QueryParameters::fromArray($expected->toQuery());

        $this->assertSame([
            'bazbat' => 'foobar',
            'foobar' => 'bazbat',
            'withCount' => 'comments,tags',
        ], $base->unrecognisedParameters());

        $this->assertEquals(
            $expected,
            $actual = ExtendedQueryParameters::cast($base)
        );

        $this->assertEquals($expected->toQuery(), $actual->toQuery());
        $this->assertEquals($expected->countable(), $actual->countable());
    }

    /**
     * @param ExtendedQueryParameters $expected
     * @depends test
     */
    public function testCastToBase(ExtendedQueryParameters $expected): void
    {
        $base = QueryParameters::cast($expected);

        $this->assertSame($expected, $base);
    }

    public function testCastEmpty(): void
    {
        $parameters = ExtendedQueryParameters::cast($value = ['withCount' => '']);

        $this->assertEquals(new CountablePaths(), $parameters->countable());
        $this->assertEquals($value, $parameters->toQuery());

        $parameters = ExtendedQueryParameters::cast([]);

        $this->assertNull($parameters->countable());
    }

    public function testSetCountable(): void
    {
        $parameters = ExtendedQueryParameters::fromArray([
            'withCount' => 'comments,tags',
        ]);

        $this->assertSame($parameters, $parameters->setCountable('posts,tags'));
        $this->assertEquals(new CountablePaths('posts', 'tags'), $parameters->countable());
        $this->assertNull($parameters->setCountable(null)->countable());
    }

    public function testWithoutCountable(): void
    {
        $parameters = ExtendedQueryParameters::fromArray([
            'withCount' => 'comments,tags',
        ]);

        $this->assertSame($parameters, $parameters->withoutCountable());
        $this->assertNull($parameters->countable());
    }
}
