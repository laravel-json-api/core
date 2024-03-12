<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Update\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Spec\ResourceDocumentComplianceChecker;
use LaravelJsonApi\Contracts\Support\Result;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Update\Middleware\CheckRequestJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckRequestJsonIsCompliantTest extends TestCase
{
    /**
     * @var MockObject&ResourceDocumentComplianceChecker
     */
    private ResourceDocumentComplianceChecker&MockObject $complianceChecker;

    /**
     * @var CheckRequestJsonIsCompliant
     */
    private CheckRequestJsonIsCompliant $middleware;

    /**
     * @var UpdateActionInput
     */
    private UpdateActionInput $action;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var ResourceId
     */
    private ResourceId $id;

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

        $this->middleware = new CheckRequestJsonIsCompliant(
            $this->complianceChecker = $this->createMock(ResourceDocumentComplianceChecker::class),
        );

        $this->action = new UpdateActionInput(
            $this->request = $this->createMock(Request::class),
            $type = new ResourceType('posts'),
            $this->id = new ResourceId('123'),
        );

        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($content = '{}');

        $this->complianceChecker
            ->expects($this->once())
            ->method('mustSee')
            ->with($this->identicalTo($type), $this->identicalTo($this->id))
            ->willReturnSelf();

        $this->complianceChecker
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

        $expected = new DataResponse(null);

        $actual = $this->middleware->handle($this->action, function ($passed) use ($expected): DataResponse {
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
