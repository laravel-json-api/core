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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\UpdateRelationship\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierOrListOfIdentifiersParser;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\Middleware\ParseUpdateRelationshipOperation;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\UpdateRelationshipActionInput;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParseUpdateRelationshipOperationTest extends TestCase
{
    /**
     * @var MockObject&ResourceIdentifierOrListOfIdentifiersParser
     */
    private ResourceIdentifierOrListOfIdentifiersParser&MockObject $parser;

    /**
     * @var ParseUpdateRelationshipOperation
     */
    private ParseUpdateRelationshipOperation $middleware;

    /**
     * @var Request&MockObject
     */
    private Request&MockObject $request;

    /**
     * @var UpdateRelationshipActionInput
     */
    private UpdateRelationshipActionInput $action;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new ParseUpdateRelationshipOperation(
            $this->parser = $this->createMock(ResourceIdentifierOrListOfIdentifiersParser::class),
        );

        $this->action = new UpdateRelationshipActionInput(
            $this->request = $this->createMock(Request::class),
            new ResourceType('posts'),
            new ResourceId('99'),
            'tags',
        );
    }

    /**
     * @return void
     */
    public function testItParsesToOne(): void
    {
        $data = ['type' => 'tags', 'id' => '1'];
        $meta = ['foo' => 'bar'];

        $this->request
            ->expects($this->exactly(2))
            ->method('json')
            ->willReturnCallback(fn(string $key): array => match($key) {
                'data' => $data,
                'meta' => $meta,
            });

        $this->parser
            ->expects($this->once())
            ->method('nullable')
            ->with($this->identicalTo($data))
            ->willReturn($identifier = new ResourceIdentifier(
                new ResourceType('tags'),
                new ResourceId('1'),
            ));

        $expected = $this->createMock(RelationshipResponse::class);
        $operation = new UpdateToOne(
            new Ref(
                type: $this->action->type(),
                id: $this->action->id(),
                relationship: $this->action->fieldName(),
            ),
            $identifier,
            $meta,
        );

        $actual = $this->middleware->handle(
            $this->action,
            function (UpdateRelationshipActionInput $passed) use ($operation, $expected): RelationshipResponse {
                $this->assertNotSame($this->action, $passed);
                $this->assertEquals($operation, $passed->operation());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItParsesToOneWithNull(): void
    {
        $this->request
            ->expects($this->exactly(2))
            ->method('json')
            ->willReturn(null);

        $this->parser
            ->expects($this->once())
            ->method('nullable')
            ->with(null)
            ->willReturn(null);

        $expected = $this->createMock(RelationshipResponse::class);
        $operation = new UpdateToOne(
            new Ref(
                type: $this->action->type(),
                id: $this->action->id(),
                relationship: $this->action->fieldName(),
            ),
            null,
            [],
        );

        $actual = $this->middleware->handle(
            $this->action,
            function (UpdateRelationshipActionInput $passed) use ($operation, $expected): RelationshipResponse {
                $this->assertNotSame($this->action, $passed);
                $this->assertEquals($operation, $passed->operation());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItParsesToMany(): void
    {
        $identifiers = new ListOfResourceIdentifiers(
            new ResourceIdentifier(
                new ResourceType('tags'),
                new ResourceId('1'),
            ),
        );

        $data = $identifiers->toArray();
        $meta = ['foo' => 'bar'];

        $this->request
            ->expects($this->exactly(2))
            ->method('json')
            ->willReturnCallback(fn(string $key): array => match($key) {
                'data' => $data,
                'meta' => $meta,
            });

        $this->parser
            ->expects($this->once())
            ->method('nullable')
            ->with($this->identicalTo($data))
            ->willReturn($identifiers);

        $expected = $this->createMock(RelationshipResponse::class);
        $operation = new UpdateToMany(
            OpCodeEnum::Update,
            new Ref(
                type: $this->action->type(),
                id: $this->action->id(),
                relationship: $this->action->fieldName(),
            ),
            $identifiers,
            $meta,
        );

        $actual = $this->middleware->handle(
            $this->action,
            function (UpdateRelationshipActionInput $passed) use ($operation, $expected): RelationshipResponse {
                $this->assertNotSame($this->action, $passed);
                $this->assertEquals($operation, $passed->operation());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
