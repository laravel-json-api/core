<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\DetachRelationship;

use ArrayObject;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Contracts\Store\ToManyBuilder;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\DetachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\DetachRelationshipCommandHandler;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\Middleware\AuthorizeDetachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\Middleware\TriggerDetachRelationshipHooks;
use LaravelJsonApi\Core\Bus\Commands\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Middleware\ValidateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class DetachRelationshipCommandHandlerTest extends TestCase
{
    /**
     * @var PipelineFactory&MockObject
     */
    private PipelineFactory&MockObject $pipelineFactory;

    /**
     * @var MockObject&StoreContract
     */
    private StoreContract&MockObject $store;

    /**
     * @var DetachRelationshipCommandHandler
     */
    private DetachRelationshipCommandHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new DetachRelationshipCommandHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->store = $this->createMock(StoreContract::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $operation = new UpdateToMany(
            OpCodeEnum::Remove,
            new Ref(type: new ResourceType('posts'), id: new ResourceId('123'), relationship: 'tags'),
            new ListOfResourceIdentifiers(),
        );

        $original = new DetachRelationshipCommand(
            $request = $this->createMock(Request::class),
            $operation,
        );

        $validated = [
            'tags' => [
                ['type' => 'tags', 'id' => '1'],
                ['type' => 'tags', 'id' => '2'],
            ],
        ];

        $passed = DetachRelationshipCommand::make($request, $operation)
            ->withModel($model = new stdClass())
            ->withValidated($validated);

        $sequence = [];

        $this->pipelineFactory
            ->expects($this->once())
            ->method('pipe')
            ->with($this->identicalTo($original))
            ->willReturn($pipeline = $this->createMock(Pipeline::class));

        $pipeline
            ->expects($this->once())
            ->method('through')
            ->willReturnCallback(function (array $actual) use (&$sequence, $pipeline): Pipeline {
                $sequence[] = 'through';
                $this->assertSame([
                    SetModelIfMissing::class,
                    AuthorizeDetachRelationshipCommand::class,
                    ValidateRelationshipCommand::class,
                    TriggerDetachRelationshipHooks::class,
                ], $actual);
                return $pipeline;
            });

        $pipeline
            ->expects($this->once())
            ->method('via')
            ->with('handle')
            ->willReturnCallback(function () use (&$sequence, $pipeline): Pipeline {
                $sequence[] = 'via';
                return $pipeline;
            });

        $pipeline
            ->expects($this->once())
            ->method('then')
            ->willReturnCallback(function (\Closure $fn) use ($passed, &$sequence): Result {
                $this->assertSame(['through', 'via'], $sequence);
                return $fn($passed);
            });

        $this->store
            ->expects($this->once())
            ->method('modifyToMany')
            ->with($this->identicalTo($passed->type()), $this->identicalTo($model), 'tags')
            ->willReturn($builder = $this->createMock(ToManyBuilder::class));

        $builder
            ->expects($this->once())
            ->method('withRequest')
            ->with($this->identicalTo($request))
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('detach')
            ->with($this->identicalTo($validated['tags']))
            ->willReturn($expected = new ArrayObject());

        $payload = $this->handler
            ->execute($original)
            ->payload();

        $this->assertTrue($payload->hasData);
        $this->assertSame($expected, $payload->data);
        $this->assertEmpty($payload->meta);
    }
}
