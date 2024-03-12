<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
