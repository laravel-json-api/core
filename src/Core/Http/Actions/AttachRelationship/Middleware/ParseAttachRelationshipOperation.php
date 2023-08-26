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
