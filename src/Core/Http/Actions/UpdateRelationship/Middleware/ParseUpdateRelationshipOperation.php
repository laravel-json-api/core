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
