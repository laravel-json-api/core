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

namespace LaravelJsonApi\Core\Operations\Commands\Store\Middleware;

use LaravelJsonApi\Contracts\Auth\Container as AuthorizerContainer;
use LaravelJsonApi\Contracts\Operations\Commands\Store\StoreCommandMiddleware;
use LaravelJsonApi\Contracts\Operations\Result\Result;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Core\Operations\Commands\Store\StoreCommand;

class AuthorizeStoreCommand implements StoreCommandMiddleware
{
    /**
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
    public function __invoke(StoreCommand $command, \Closure $next): Result
    {
        if ($command->mustAuthorize() && $command->hasRequest()) {
            $this->authorize($command);
        }

        return $next($command);
    }

    /**
     * @param StoreCommand $command
     * @return void
     * @throws \Throwable
     */
    private function authorize(StoreCommand $command): void
    {
        $authorizer = $this->authorizerContainer->authorizerFor($command->type());
        $schema = $this->schemaContainer->schemaFor($command->type());
        $passes = $authorizer->store(
            $command->requestOrFail(),
            $schema->model(),
        );

        if ($passes === false) {
            throw $authorizer->failed();
        }
    }
}
