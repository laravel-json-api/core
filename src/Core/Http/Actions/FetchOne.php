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

namespace LaravelJsonApi\Core\Http\Actions;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Actions\FetchOne as FetchOneContract;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Http\Actions\FetchOne\FetchOneActionHandler;
use LaravelJsonApi\Core\Http\Actions\FetchOne\FetchOneActionInputFactory;
use LaravelJsonApi\Core\Responses\DataResponse;
use Symfony\Component\HttpFoundation\Response;

class FetchOne implements FetchOneContract
{
    /**
     * @var ResourceType|string|null
     */
    private ResourceType|string|null $type = null;

    /**
     * @var object|string|null
     */
    private object|string|null $idOrModel = null;

    /**
     * @var object|null
     */
    private ?object $hooks = null;

    /**
     * FetchOne constructor
     *
     * @param Route $route
     * @param FetchOneActionInputFactory $factory
     * @param FetchOneActionHandler $handler
     */
    public function __construct(
        private readonly Route $route,
        private readonly FetchOneActionInputFactory $factory,
        private readonly FetchOneActionHandler $handler,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function withTarget(ResourceType|string $type, object|string $idOrModel): static
    {
        $this->type = $type;
        $this->idOrModel = $idOrModel;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withHooks(?object $target): static
    {
        $this->hooks = $target;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function execute(Request $request): DataResponse
    {
        $type = $this->type ?? $this->route->resourceType();
        $idOrModel = $this->idOrModel ?? $this->route->modelOrResourceId();

        $input = $this->factory
            ->make($request, $type, $idOrModel)
            ->withHooks($this->hooks);

        return $this->handler->execute($input);
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request): Response
    {
        return $this
            ->execute($request)
            ->toResponse($request);
    }
}
