<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\UpdateRelationship\Middleware;

use Closure;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierOrListOfIdentifiersParser;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\HandlesUpdateRelationshipActions;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\UpdateRelationshipActionInput;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

class ParseUpdateRelationshipOperation implements HandlesUpdateRelationshipActions
{
    /**
     * ParseUpdateRelationshipOperation constructor
     *
     * @param ResourceIdentifierOrListOfIdentifiersParser $parser
     */
    public function __construct(private readonly ResourceIdentifierOrListOfIdentifiersParser $parser)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(UpdateRelationshipActionInput $action, Closure $next): RelationshipResponse
    {
        $request = $action->request();

        $data = $this->parser->nullable(
            $request->json('data'),
        );

        $meta = $request->json('meta') ?? [];

        $ref = new Ref(
            type: $action->type(),
            id: $action->id(),
            relationship: $action->fieldName(),
        );

        $operation = match(true) {
            ($data === null || $data instanceof ResourceIdentifier) => new UpdateToOne($ref, $data, $meta),
            $data instanceof ListOfResourceIdentifiers => new UpdateToMany(
                OpCodeEnum::Update,
                $ref,
                $data,
                $meta,
            ),
        };

        return $next($action->withOperation($operation));
    }
}
