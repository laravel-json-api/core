<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Actions\DetachRelationship as DetachRelationshipContract;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Core\Http\Actions\DetachRelationship\DetachRelationshipActionHandler;
use LaravelJsonApi\Core\Http\Actions\DetachRelationship\DetachRelationshipActionInputFactory;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Values\ResourceType;
use Symfony\Component\HttpFoundation\Response;

class DetachRelationship implements DetachRelationshipContract
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
     * @var string|null
     */
    private ?string $fieldName = null;

    /**
     * @var object|null
     */
    private ?object $hooks = null;

    /**
     * DetachRelationship constructor
     *
     * @param Route $route
     * @param DetachRelationshipActionInputFactory $factory
     * @param DetachRelationshipActionHandler $handler
     */
    public function __construct(
        private readonly Route $route,
        private readonly DetachRelationshipActionInputFactory $factory,
        private readonly DetachRelationshipActionHandler $handler,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function withTarget(ResourceType|string $type, object|string $idOrModel, string $fieldName): static
    {
        $this->type = $type;
        $this->idOrModel = $idOrModel;
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
    public function execute(Request $request): RelationshipResponse|NoContentResponse
    {
        $type = $this->type ?? $this->route->resourceType();
        $idOrModel = $this->idOrModel ?? $this->route->modelOrResourceId();
        $fieldName = $this->fieldName ?? $this->route->fieldName();

        $input = $this->factory
            ->make($request, $type, $idOrModel, $fieldName)
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
