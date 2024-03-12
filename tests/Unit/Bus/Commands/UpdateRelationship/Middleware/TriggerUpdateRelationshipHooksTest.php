<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\UpdateRelationship\Middleware;

use ArrayObject;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\UpdateRelationshipImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\Middleware\TriggerUpdateRelationshipHooks;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\UpdateRelationshipCommand;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;
use stdClass;

class TriggerUpdateRelationshipHooksTest extends TestCase
{
    /**
     * @var TriggerUpdateRelationshipHooks
     */
    private TriggerUpdateRelationshipHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TriggerUpdateRelationshipHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $command = UpdateRelationshipCommand::make(
            $this->createMock(Request::class),
            new UpdateToMany(
                OpCodeEnum::Update,
                new Ref(type: new ResourceType('posts'), id: new ResourceId('123'), relationship: 'tags'),
                new ListOfResourceIdentifiers(),
            ),
        )->withModel(new stdClass());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (UpdateRelationshipCommand $cmd) use ($command, $expected): Result {
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
        $hooks = $this->createMock(UpdateRelationshipImplementation::class);
        $query = $this->createMock(QueryParameters::class);
        $model = new stdClass();
        $related = new ArrayObject();
        $sequence = [];

        $operation = new UpdateToMany(
            OpCodeEnum::Update,
            new Ref(type: new ResourceType('posts'), id: new ResourceId('123'), relationship: 'tags'),
            new ListOfResourceIdentifiers(),
        );

        $command = UpdateRelationshipCommand::make($request, $operation)
            ->withModel($model)
            ->withHooks($hooks)
            ->withQuery($query);

        $hooks
            ->expects($this->once())
            ->method('updatingRelationship')
            ->willReturnCallback(function ($m, $f, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'updating';
                $this->assertSame($model, $m);
                $this->assertSame('tags', $f);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('updatedRelationship')
            ->willReturnCallback(
                function ($m, $f, $rel, $req, $q) use (&$sequence, $model, $related, $request, $query): void {
                    $sequence[] = 'updated';
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
            function (UpdateRelationshipCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['updating'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['updating', 'updated'], $sequence);
    }

    /**
     * @return void
     */
    public function testItDoesNotTriggerAfterHooksIfItFails(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(UpdateRelationshipImplementation::class);
        $query = $this->createMock(QueryParameters::class);
        $model = new stdClass();
        $related= new ArrayObject();
        $sequence = [];

        $operation = new UpdateToMany(
            OpCodeEnum::Update,
            new Ref(type: new ResourceType('posts'), id: new ResourceId('123'), relationship: 'tags'),
            new ListOfResourceIdentifiers(),
        );

        $command = UpdateRelationshipCommand::make($request, $operation)
            ->withModel($model)
            ->withHooks($hooks)
            ->withQuery($query);

        $hooks
            ->expects($this->once())
            ->method('updatingRelationship')
            ->willReturnCallback(function ($m, $f, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'updating';
                $this->assertSame($model, $m);
                $this->assertSame('tags', $f);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->never())
            ->method('updatedRelationship');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $command,
            function (UpdateRelationshipCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['updating'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['updating'], $sequence);
    }
}
