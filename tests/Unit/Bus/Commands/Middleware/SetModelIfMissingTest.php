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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetModelIfMissingTest extends TestCase
{
    /**
     * @var MockObject&Store
     */
    private Store&MockObject $store;

    /**
     * @var SetModelIfMissing
     */
    private SetModelIfMissing $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new SetModelIfMissing(
            $this->store = $this->createMock(Store::class),
        );
    }

    /**
     * @return array<array<Closure>>
     */
    public static function modelRequiredProvider(): array
    {
        return [
            'update' => [
                static function (): UpdateCommand {
                    $operation = new Update(
                        null,
                        new ResourceObject(new ResourceType('posts'), new ResourceId('123')),
                    );
                    return UpdateCommand::make(null, $operation);
                },
            ],
            'destroy' => [
                static function (): DestroyCommand {
                    return DestroyCommand::make(
                        null,
                        new Delete(new Ref(new ResourceType('tags'), new ResourceId('999'))),
                    );
                },
            ],
        ];
    }

    /**
     * @param Closure<Command&IsIdentifiable> $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItSetsModel(Closure $scenario): void
    {
        $command = $scenario();

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($command->type()), $this->identicalTo($command->id()))
            ->willReturn($model = new \stdClass());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $command,
            function (Command&IsIdentifiable $passed) use ($command, $model, $expected): Result {
                $this->assertNotSame($passed, $command);
                $this->assertSame($model, $passed->model());
                $this->assertSame($model, $passed->model());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure<Command&IsIdentifiable> $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItDoesNotSetModel(Closure $scenario): void
    {
        $command = $scenario();
        $command = $command->withModel($model = new \stdClass());

        $this->store
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $command,
            function (Command&IsIdentifiable $passed) use ($command, $model, $expected): Result {
                $this->assertSame($passed, $command);
                $this->assertSame($model, $passed->model());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
