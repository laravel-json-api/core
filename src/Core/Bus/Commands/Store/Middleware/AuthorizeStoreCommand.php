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

namespace LaravelJsonApi\Core\Bus\Commands\Store\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Container as AuthorizerContainer;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\HandlesStoreCommands;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class AuthorizeStoreCommand implements HandlesStoreCommands
{
    /**
     * AuthorizeStoreCommand constructor
     *
     * @param AuthorizerContainer $authorizerContainer
     * @param SchemaContainer $schemaContainer
     */
    public function __construct(
        private readonly AuthorizerContainer $authorizerContainer,
        private readonly SchemaContainer $schemaContainer,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(StoreCommand $command, Closure $next): Result
    {
        if ($command->mustAuthorize() && $request = $command->request()) {
            $this->authorize($request, $command->type());
        }

        return $next($command);
    }

    /**
     * @param Request $request
     * @param ResourceType $type
     * @return void
     * @throws \Throwable
     */
    private function authorize(Request $request, ResourceType $type): void
    {
        $authorizer = $this->authorizerContainer->authorizerFor($type);
        $schema = $this->schemaContainer->schemaFor($type);
        $passes = $authorizer->store($request, $schema->model());

        if ($passes === false) {
            throw $authorizer->failed();
        }
    }
}
