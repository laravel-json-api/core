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

namespace LaravelJsonApi\Core\Bus\Commands\AttachRelationship\Middleware;

use Closure;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\AttachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\HandlesAttachRelationshipCommands;
use LaravelJsonApi\Core\Bus\Commands\Result;
use RuntimeException;

class TriggerAttachRelationshipHooks implements HandlesAttachRelationshipCommands
{
    /**
     * @inheritDoc
     */
    public function handle(AttachRelationshipCommand $command, Closure $next): Result
    {
        $hooks = $command->hooks();

        if ($hooks === null) {
            return $next($command);
        }

        $request = $command->request() ?? throw new RuntimeException('Hooks require a request to be set.');
        $query = $command->query() ?? throw new RuntimeException('Hooks require a query to be set.');
        $model = $command->modelOrFail();
        $fieldName = $command->fieldName();

        $hooks->attachingRelationship($model, $fieldName, $request, $query);

        /** @var Result $result */
        $result = $next($command);

        if ($result->didSucceed()) {
            $hooks->attachedRelationship(
                $model,
                $fieldName,
                $result->payload()->data,
                $request,
                $query,
            );
        }

        return $result;
    }
}
