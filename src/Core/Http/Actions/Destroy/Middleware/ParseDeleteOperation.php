<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Destroy\Middleware;

use Closure;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\Destroy\DestroyActionInput;
use LaravelJsonApi\Core\Http\Actions\Destroy\HandlesDestroyActions;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Core\Responses\NoContentResponse;

class ParseDeleteOperation implements HandlesDestroyActions
{
    /**
     * @inheritDoc
     */
    public function handle(DestroyActionInput $action, Closure $next): MetaResponse|NoContentResponse
    {
        $request = $action->request();

        return $next($action->withOperation(
            new Delete(
                new Ref($action->type(), $action->id()),
                $request->json('meta') ?? [],
            ),
        ));
    }
}
