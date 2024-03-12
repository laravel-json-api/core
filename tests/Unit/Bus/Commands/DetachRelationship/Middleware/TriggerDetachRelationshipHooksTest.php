<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\DetachRelationship\Middleware;

use ArrayObject;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\DetachRelationshipImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\DetachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\Middleware\TriggerDetachRelationshipHooks;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;
use stdClass;

class TriggerDetachRelationshipHooksTest extends TestCase
{
    /**
     * @var TriggerDetachRelationshipHooks
     */
    private TriggerDetachRelationshipHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TriggerDetachRelationshipHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $command = DetachRelationshipCommand::make(
            $this->createMock(Request::class),
            new UpdateToMany(
                OpCodeEnum::Remove,
                new Ref(type: new ResourceType('posts'), id: new ResourceId('123'), relationship: 'tags'),
                new ListOfResourceIdentifiers(),
            ),
        )->withModel(new stdClass());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (DetachRelationshipCommand $cmd) use ($command, $expected): Result {
                $this->assertSame($command, $cmd);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItTriggersHooks(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(DetachRelationshipImplementation::class);
        $query = $this->createMock(QueryParameters::class);
        $model = new stdClass();
        $related = new ArrayObject();
        $sequence = [];

        $operation = new UpdateToMany(
            OpCodeEnum::Remove,
            new Ref(type: new ResourceType('posts'), id: new ResourceId('123'), relationship: 'tags'),
            new ListOfResourceIdentifiers(),
        );

        $command = DetachRelationshipCommand::make($request, $operation)
            ->withModel($model)
            ->withHooks($hooks)
            ->withQuery($query);

        $hooks
            ->expects($this->once())
            ->method('detachingRelationship')
            ->willReturnCallback(function ($m, $f, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'detaching';
                $this->assertSame($model, $m);
                $this->assertSame('tags', $f);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('detachedRelationship')
            ->willReturnCallback(
                function ($m, $f, $rel, $req, $q) use (&$sequence, $model, $related, $request, $query): void {
                    $sequence[] = 'detached';
                    $this->assertSame($model, $m);
                    $this->assertSame('tags', $f);
                    $this->assertSame($related, $rel);
                    $this->assertSame($request, $req);
                    $this->assertSame($query, $q);
                },
            );

        $expected = Result::ok(new Payload($related, true));

        $actual = $this->middleware->handle(
            $command,
            function (DetachRelationshipCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['detaching'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['detaching', 'detached'], $sequence);
    }

    /**
     * @return void
     */
    public function testItDoesNotTriggerAfterHooksIfItFails(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(DetachRelationshipImplementation::class);
        $query = $this->createMock(QueryParameters::class);
        $model = new stdClass();
        $sequence = [];

        $operation = new UpdateToMany(
            OpCodeEnum::Remove,
            new Ref(type: new ResourceType('posts'), id: new ResourceId('123'), relationship: 'tags'),
            new ListOfResourceIdentifiers(),
        );

        $command = DetachRelationshipCommand::make($request, $operation)
            ->withModel($model)
            ->withHooks($hooks)
            ->withQuery($query);

        $hooks
            ->expects($this->once())
            ->method('detachingRelationship')
            ->willReturnCallback(function ($m, $f, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'detaching';
                $this->assertSame($model, $m);
                $this->assertSame('tags', $f);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->never())
            ->method('detachedRelationship');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $command,
            function (DetachRelationshipCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['detaching'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['detaching'], $sequence);
    }
}
