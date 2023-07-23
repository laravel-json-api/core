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

namespace LaravelJsonApi\Core\Http\Actions\Store\Middleware;

use Closure;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Http\Actions\Store\HandlesStoreActions;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;

class ParseStoreOperation implements HandlesStoreActions
{
    /**
     * ParseStoreOperation constructor
     *
     * @param ResourceObjectParser $parser
     */
    public function __construct(private readonly ResourceObjectParser $parser)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(StoreActionInput $action, Closure $next): DataResponse
    {
        $request = $action->request();

        $resource = $this->parser->parse(
            $request->json('data'),
        );

        return $next($action->withOperation(
            new Create(
                new Href($request->url()),
                $resource,
                $request->json('meta') ?? [],
            ),
        ));
    }
}
