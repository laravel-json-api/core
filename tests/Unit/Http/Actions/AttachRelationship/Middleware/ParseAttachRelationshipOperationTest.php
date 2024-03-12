<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\AttachRelationship\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Parsers\ListOfResourceIdentifiersParser;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\AttachRelationshipActionInput;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\Middleware\ParseAttachRelationshipOperation;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParseAttachRelationshipOperationTest extends TestCase
{
    /**
     * @var MockObject&ListOfResourceIdentifiersParser
     */
    private ListOfResourceIdentifiersParser&MockObject $parser;

    /**
     * @var ParseAttachRelationshipOperation
     */
    private ParseAttachRelationshipOperation $middleware;

    /**
     * @var Request&MockObject
     */
    private Request&MockObject $request;

    /**
     * @var AttachRelationshipActionInput
     */
    private AttachRelationshipActionInput $action;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new ParseAttachRelationshipOperation(
            $this->parser = $this->createMock(ListOfResourceIdentifiersParser::class),
        );

        $this->action = new AttachRelationshipActionInput(
            $this->request = $this->createMock(Request::class),
            new ResourceType('posts'),
            new ResourceId('99'),
            'tags',
        );
    }

    /**
     * @return void
     */
    public function test(): void
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
            ->method('parse')
            ->with($this->identicalTo($data))
            ->willReturn($identifiers);

        $expected = $this->createMock(RelationshipResponse::class);
        $operation = new UpdateToMany(
            OpCodeEnum::Add,
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
            function (AttachRelationshipActionInput $passed) use ($operation, $expected): RelationshipResponse {
                $this->assertNotSame($this->action, $passed);
                $this->assertEquals($operation, $passed->operation());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
