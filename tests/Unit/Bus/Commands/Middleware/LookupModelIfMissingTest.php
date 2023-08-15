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
use LaravelJsonApi\Core\Bus\Commands\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class LookupModelIfMissingTest extends TestCase
{
    /**
     * @var MockObject&Store
     */
    private Store&MockObject $store;

    /**
     * @var LookupModelIfMissing
     */
    private LookupModelIfMissing $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new LookupModelIfMissing(
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
        ];
    }

    /**
     * @param Closure $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItFindsModel(Closure $scenario): void
    {
        /** @var Command&IsIdentifiable $command */
        $command = $scenario();
        $type = $command->type();
        $id = $command->id();

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn($model = new stdClass());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $command,
            function (Command&IsIdentifiable $passed) use ($command, $model, $expected): Result {
                $this->assertNotSame($passed, $command);
                $this->assertSame($model, $passed->model());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItDoesNotFindModelIfAlreadySet(Closure $scenario): void
    {
        /** @var Command&IsIdentifiable $command */
        $command = $scenario();
        /** @var Command&IsIdentifiable $command */
        $command = $command->withModel(new \stdClass());

        $this->store
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $command,
            function (Command $passed) use ($command, $expected): Result {
                $this->assertSame($passed, $command);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItDoesNotFindModel(Closure $scenario): void
    {
        /** @var Command&IsIdentifiable $command */
        $command = $scenario();
        $type = $command->type();
        $id = $command->id();

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn(null);

        $result = $this->middleware->handle(
            $command,
            fn() => $this->fail('Not expecting next middleware to be called.'),
        );

        $this->assertTrue($result->didFail());
        $this->assertEquals(new ErrorList(Error::make()->setStatus(404)), $result->errors());
    }
}
