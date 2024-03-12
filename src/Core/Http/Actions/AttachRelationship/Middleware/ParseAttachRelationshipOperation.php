<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\AttachRelationship\Middleware;

use Closure;
use LaravelJsonApi\Core\Document\Input\Parsers\ListOfResourceIdentifiersParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\AttachRelationshipActionInput;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\HandlesAttachRelationshipActions;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

class ParseAttachRelationshipOperation implements HandlesAttachRelationshipActions
{
    /**
     * ParseAttachRelationshipOperation constructor
     *
     * @param ListOfResourceIdentifiersParser $parser
     */
    public function __construct(private readonly ListOfResourceIdentifiersParser $parser)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(
        AttachRelationshipActionInput $action,
        Closure $next,
    ): RelationshipResponse|NoContentResponse
    {
        $request = $action->request();

        $ref = new Ref(
            type: $action->type(),
            id: $action->id(),
            relationship: $action->fieldName(),
        );

        $operation = new UpdateToMany(
            OpCodeEnum::Add,
            $ref,
            $this->parser->parse($request->json('data')),
            $request->json('meta') ?? [],
        );

        return $next($action->withOperation($operation));
    }
}
