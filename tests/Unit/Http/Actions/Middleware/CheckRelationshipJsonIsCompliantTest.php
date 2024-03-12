<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Middleware;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Spec\RelationshipDocumentComplianceChecker;
use LaravelJsonApi\Contracts\Support\Result;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Middleware\CheckRelationshipJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\UpdateRelationshipActionInput;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class CheckRelationshipJsonIsCompliantTest extends TestCase
{
    /**
     * @var CheckRelationshipJsonIsCompliant
     */
    private CheckRelationshipJsonIsCompliant $middleware;

    /**
     * @var UpdateRelationshipActionInput
     */
    private UpdateRelationshipActionInput $action;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var Result|null
     */
    private ?Result $result = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CheckRelationshipJsonIsCompliant(
            $complianceChecker = $this->createMock(RelationshipDocumentComplianceChecker::class),
        );

        $this->action = new UpdateRelationshipActionInput(
            $this->request = $this->createMock(Request::class),
            $type = new ResourceType('posts'),
            new ResourceId('123'),
            'tags',
        );

        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($content = '{}');

        $complianceChecker
            ->expects($this->once())
            ->method('mustSee')
            ->with($this->identicalTo($type), $this->identicalTo('tags'))
            ->willReturnSelf();

        $complianceChecker
            ->expects($this->once())
            ->method('check')
            ->with($this->identicalTo($content))
            ->willReturnCallback(fn() => $this->result);
    }

    /**
     * @return void
     */
    public function testItPasses(): void
    {
        $this->result = $this->createMock(Result::class);
        $this->result->method('didSucceed')->willReturn(true);
        $this->result->method('didFail')->willReturn(false);
        $this->result->expects($this->never())->method('errors');

        $expected = $this->createMock(Responsable::class);

        $actual = $this->middleware->handle($this->action, function ($passed) use ($expected): Responsable {
            $this->assertSame($this->action, $passed);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFails(): void
    {
        $this->result = $this->createMock(Result::class);
        $this->result->method('didSucceed')->willReturn(false);
        $this->result->method('didFail')->willReturn(true);
        $this->result->method('errors')->willReturn($expected = new ErrorList());

        try {
            $this->middleware->handle(
                $this->action,
                fn() => $this->fail('Next middleware should not be called.'),
            );
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame($expected, $ex->getErrors());
        }
    }
}
