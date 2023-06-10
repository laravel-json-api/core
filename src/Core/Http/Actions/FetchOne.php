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
use LaravelJsonApi\Contracts\Http\Actions\FetchOne as FetchOneActionContract;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Http\Actions\FetchOne\FetchOneActionHandler;
use LaravelJsonApi\Core\Http\Actions\FetchOne\FetchOneActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;
use Symfony\Component\HttpFoundation\Response;

class FetchOne implements FetchOneActionContract
{
    /**
     * @var ResourceType|null
     */
    private ?ResourceType $type = null;

    /**
     * @var ResourceId|null
     */
    private ?ResourceId $id = null;

    /**
     * @var object|null
     */
    private ?object $model = null;

    /**
     * @var object|null
     */
    private ?object $hooks = null;

    /**
     * FetchOne constructor
     *
     * @param Route $route
     * @param FetchOneActionHandler $handler
     */
    public function __construct(
        private readonly Route $route,
        private readonly FetchOneActionHandler $handler,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function withType(string|ResourceType $type): static
    {
        $this->type = ResourceType::cast($type);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withIdOrModel(object|string $idOrModel): static
    {
        if (is_string($idOrModel) || $idOrModel instanceof ResourceId) {
            $this->id = ResourceId::cast($idOrModel);
            $this->model = null;
            return $this;
        }

        $this->id = null;
        $this->model = $idOrModel;

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

        $input = FetchOneActionInput::make($request, $type)
            ->maybeWithId($this->id)
            ->withModel($this->model)
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
