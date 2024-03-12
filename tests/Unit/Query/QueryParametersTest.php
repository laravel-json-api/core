<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Query;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\FilterParameters;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Query\SortFields;
use PHPUnit\Framework\TestCase;

class QueryParametersTest extends TestCase
{

    /**
     * @return array
     */
    public static function keyProvider(): array
    {
        return [
            'fields' => ['fields', 'sparseFieldSets'],
            'filter' => ['filter', 'filter'],
            'include' => ['include', 'includePaths'],
            'page' => ['page', 'page'],
            'sort' => ['sort', 'sortFields'],
        ];
    }

    public function test(): QueryParameters
    {
        $parameters = QueryParameters::cast($value = [
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
        ]);

        $this->assertEquals(FieldSets::fromArray($value['fields']), $parameters->sparseFieldSets());
        $this->assertEquals(FilterParameters::fromArray($value['filter']), $parameters->filter());
        $this->assertEquals(IncludePaths::fromString($value['include']), $parameters->includePaths());
        $this->assertEquals($value['page'], $parameters->page());
        $this->assertEquals(SortFields::fromString($value['sort']), $parameters->sortFields());
        $this->assertEquals([
            'foobar' => 'bazbat',
            'bazbat' => 'foobar',
        ], $parameters->unrecognisedParameters());

        $arr = $value;
        $arr['include'] = ['author', 'comments.user'];
        $arr['sort'] = ['-createdAt', 'id'];

        $this->assertSame($arr, $parameters->toArray());
        $this->assertSame($value, $parameters->toQuery());
        $this->assertSame(Arr::query($value), (string) $parameters);

        return $parameters;
    }

    /**
     * @param QueryParameters $expected
     * @depends test
     */
    public function testCastWithRequest(QueryParameters $expected): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('query')->willReturn($expected->toQuery());

        $this->assertEquals($expected, QueryParameters::cast($request));
    }

    /**
     * @param QueryParameters $expected
     * @depends test
     */
    public function testCastWithEnumerable(QueryParameters $expected): void
    {
        $this->assertEquals($expected, QueryParameters::cast(collect($expected->toQuery())));
    }

    /**
     * @param QueryParameters $expected
     * @depends test
     */
    public function testCastWithSelf(QueryParameters $expected): void
    {
        $this->assertSame($expected, QueryParameters::cast($expected));
    }

    /**
     * @param QueryParameters $expected
     * @depends test
     */
    public function testCastWithContract(QueryParameters $expected): void
    {
        $mock = $this->createMock(QueryParametersContract::class);
        $mock->method('sparseFieldSets')->willReturn($expected->sparseFieldSets());
        $mock->method('filter')->willReturn($expected->filter());
        $mock->method('includePaths')->willReturn($expected->includePaths());
        $mock->method('page')->willReturn($expected->page());
        $mock->method('sortFields')->willReturn($expected->sortFields());
        $mock->method('unrecognisedParameters')->willReturn($expected->unrecognisedParameters());

        $this->assertEquals($expected, QueryParameters::cast($mock));
    }

    public function testCastWithNull(): void
    {
        $parameters = QueryParameters::cast(null);

        $this->assertNull($parameters->sparseFieldSets());
        $this->assertNull($parameters->filter());
        $this->assertNull($parameters->page());
        $this->assertNull($parameters->includePaths());
        $this->assertNull($parameters->sortFields());
        $this->assertEquals([], $parameters->unrecognisedParameters());
        $this->assertSame([], $parameters->toQuery());
    }

    public function testCastEmptyArray(): void
    {
        $parameters = QueryParameters::cast([]);

        $this->assertNull($parameters->sparseFieldSets());
        $this->assertNull($parameters->filter());
        $this->assertNull($parameters->page());
        $this->assertNull($parameters->includePaths());
        $this->assertNull($parameters->sortFields());
        $this->assertEquals([], $parameters->unrecognisedParameters());
        $this->assertSame([], $parameters->toQuery());
    }

    public function testCastWithEmptyValues(): void
    {
        $parameters = QueryParameters::cast($values = [
            'fields' => [],
            'filter' => [],
            'include' => '',
            'page' => [],
            'sort' => '',
        ]);

        $this->assertEquals(new FieldSets(), $parameters->sparseFieldSets());
        $this->assertEquals(new FilterParameters(), $parameters->filter());
        $this->assertEquals([], $parameters->page());
        $this->assertEquals(new IncludePaths(), $parameters->includePaths());
        $this->assertEquals(new SortFields(), $parameters->sortFields());
        $this->assertEquals([], $parameters->unrecognisedParameters());
        $this->assertSame($values, $parameters->toQuery());
    }

    /**
     * @param QueryParameters $expected
     * @depends test
     */
    public function testNullable(QueryParameters $expected): void
    {
        $this->assertNull(QueryParameters::nullable(null));
        $this->assertEquals(new QueryParameters(), QueryParameters::nullable([]));
        $this->assertEquals($expected, QueryParameters::nullable($expected->toQuery()));
    }

    public function testMake(): void
    {
        $this->assertEquals(new QueryParameters(), QueryParameters::make());
    }

    public function testSetIncludePaths(): void
    {
        $parameters = QueryParameters::fromArray(['include' => 'author,comments']);

        $this->assertSame($parameters, $parameters->setIncludePaths('author.profile,comments.user'));

        $this->assertEquals(
            IncludePaths::fromString('author.profile,comments.user'),
            $parameters->includePaths()
        );

        $this->assertNull($parameters->setIncludePaths(null)->includePaths());
    }

    public function testWithoutIncludePaths(): void
    {
        $parameters = QueryParameters::fromArray(['include' => 'author,comments']);

        $this->assertSame($parameters, $parameters->withoutIncludePaths());
        $this->assertNull($parameters->includePaths());
    }

    public function testSetFieldSets(): void
    {
        $parameters = QueryParameters::fromArray([
            'fields' => [
                'comments' => 'user,content',
                'posts' => 'author,createdAt,synopsis,title',
            ],
        ]);

        $this->assertSame($parameters, $parameters->setSparseFieldSets($expected = [
            'comments' => 'user',
            'posts' => 'author,createdAt,title',
            'tags' => 'displayName',
        ]));

        $this->assertEquals(FieldSets::fromArray($expected), $parameters->sparseFieldSets());

        $this->assertNull($parameters->setSparseFieldSets(null)->sparseFieldSets());
    }

    public function testWithoutFieldSets(): void
    {
        $parameters = QueryParameters::fromArray([
            'fields' => [
                'comments' => 'user,content',
                'posts' => 'author,createdAt,synopsis,title',
            ],
        ]);

        $this->assertSame($parameters, $parameters->withoutSparseFieldSets());
        $this->assertNull($parameters->sparseFieldSets());
    }

    public function testSetFieldSet(): void
    {
        $parameters = QueryParameters::fromArray([
            'fields' => $fields = [
                'comments' => 'user,content',
                'posts' => 'author,createdAt,synopsis,title',
            ],
        ]);

        $this->assertSame($parameters, $parameters->setFieldSet('tags', ['displayName']));

        $fields['tags'] = 'displayName';
        $this->assertEquals(FieldSets::fromArray($fields), $parameters->sparseFieldSets());
    }

    public function testSetSortFields(): void
    {
        $parameters = QueryParameters::fromArray([
            'sort' => '-createdAt,id',
        ]);

        $this->assertSame($parameters, $parameters->setSortFields('-updatedAt,id'));
        $this->assertEquals(SortFields::fromString('-updatedAt,id'), $parameters->sortFields());
        $this->assertNull($parameters->setSortFields(null)->sortFields());
    }

    public function testWithoutSortFields(): void
    {
        $parameters = QueryParameters::fromArray([
            'sort' => '-createdAt,id',
        ]);

        $this->assertSame($parameters, $parameters->withoutSortFields());
        $this->assertNull($parameters->sortFields());
    }

    public function testSetPagination(): void
    {
        $parameters = QueryParameters::fromArray([
            'page' => ['number' => '1', 'size' => '15'],
        ]);

        $this->assertSame($parameters, $parameters->setPagination(['number' => '2', 'size' => '25']));
        $this->assertSame(['number' => '2', 'size' => '25'], $parameters->page());
        $this->assertNull($parameters->setPagination(null)->page());
    }

    public function testWithoutPagination(): void
    {
        $parameters = QueryParameters::fromArray([
            'page' => ['number' => '1', 'size' => '15'],
        ]);

        $this->assertSame($parameters, $parameters->withoutPagination());
        $this->assertNull($parameters->page());
    }

    public function testSetFilters(): void
    {
        $parameters = QueryParameters::fromArray([
            'filter' => ['foo' => 'bar'],
        ]);

        $this->assertSame($parameters, $parameters->setFilters(['baz' => 'bat']));
        $this->assertEquals(FilterParameters::fromArray(['baz' => 'bat']), $parameters->filter());
        $this->assertNull($parameters->setFilters(null)->filter());
    }

    public function testWithoutFilters(): void
    {
        $parameters = QueryParameters::fromArray([
            'filter' => ['foo' => 'bar'],
        ]);

        $this->assertSame($parameters, $parameters->withoutFilters());
        $this->assertNull($parameters->filter());
    }

    public function testSetUnrecognisedParameters(): void
    {
        $parameters = QueryParameters::fromArray([
            'foo' => 'bar',
            'baz' => 'bat',
        ]);

        $this->assertSame($parameters, $parameters->setUnrecognisedParameters(['foobar' => 'bazbat']));
        $this->assertSame(['foobar' => 'bazbat'], $parameters->unrecognisedParameters());
        $this->assertSame([], $parameters->setUnrecognisedParameters(null)->unrecognisedParameters());
    }

    public function testWithoutUnrecognisedParameters(): void
    {
        $parameters = QueryParameters::fromArray([
            'foo' => 'bar',
            'baz' => 'bat',
        ]);

        $this->assertSame($parameters, $parameters->withoutUnrecognisedParameters());
        $this->assertSame([], $parameters->unrecognisedParameters());
    }

    /**
     * @param string $key
     * @param string $fn
     * @dataProvider keyProvider
     */
    public function testWithoutValue(string $key, string $fn): void
    {
        $value = [
            'fields' => [
                'comments' => 'user,content',
                'posts' => 'author,createdAt,synopsis,title',
            ],
            'filter' => ['foo' => 'bar', 'baz' => 'bat'],
            'include' => 'author,comments.user',
            'page' => ['number' => '1', 'size' => '25'],
            'sort' => '-createdAt,id',
        ];

        unset($value[$key]);

        $parameters = QueryParameters::cast($value);

        $this->assertNull($parameters->{$fn}());
    }
}
