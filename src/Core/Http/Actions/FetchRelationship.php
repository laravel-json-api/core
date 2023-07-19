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
use LaravelJsonApi\Contracts\Http\Actions\FetchRelationship as FetchRelationshipContract;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Http\Actions\FetchRelationship\FetchRelationshipActionHandler;
use LaravelJsonApi\Core\Http\Actions\FetchRelationship\FetchRelationshipActionInput;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use Symfony\Component\HttpFoundation\Response;

class FetchRelationship implements FetchRelationshipContract
{
    /**
     * @var ResourceType|null
     */
    private ?ResourceType $type = null;

    /**
     * @var object|string|null
     */
    private object|string|null $idOrModel = null;

    /**
     * @var string|null
     */
    private ?string $fieldName = null;

    /**
     * @var object|null
     */
    private ?object $hooks = null;

    /**
     * FetchRelationship constructor
     *
     * @param Route $route
     * @param FetchRelationshipActionHandler $handler
     */
    public function __construct(
        private readonly Route $route,
        private readonly FetchRelationshipActionHandler $handler,
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
        $this->idOrModel = $idOrModel;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withFieldName(string $fieldName): static
    {
        $this->fieldName = $fieldName;

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
    public function execute(Request $request): RelationshipResponse
    {
        $type = $this->type ?? $this->route->resourceType();

        $input = FetchRelationshipActionInput::make($request, $type)
            ->withIdOrModel($this->idOrModel ?? $this->route->modelOrResourceId())
            ->withFieldName($this->fieldName ?? $this->route->fieldName())
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
