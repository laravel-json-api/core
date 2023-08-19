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

namespace LaravelJsonApi\Core\Http\Actions\Destroy\Middleware;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\Destroy\DestroyActionInput;
use LaravelJsonApi\Core\Http\Actions\Destroy\HandlesDestroyActions;
use Symfony\Component\HttpFoundation\Response;

class ParseDeleteOperation implements HandlesDestroyActions
{
    /**
     * @inheritDoc
     */
    public function handle(DestroyActionInput $action, Closure $next): Responsable|Response
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