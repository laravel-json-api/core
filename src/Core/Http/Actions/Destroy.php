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
use LaravelJsonApi\Contracts\Http\Actions\Destroy as DestroyContract;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Core\Http\Actions\Destroy\DestroyActionHandler;
use LaravelJsonApi\Core\Http\Actions\Destroy\DestroyActionInputFactory;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Values\ResourceType;
use Symfony\Component\HttpFoundation\Response;

class Destroy implements DestroyContract
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
     * Destroy constructor
     *
     * @param Route $route
     * @param DestroyActionInputFactory $factory
     * @param DestroyActionHandler $handler
     */
    public function __construct(
        private readonly Route $route,
        private readonly DestroyActionInputFactory $factory,
        private readonly DestroyActionHandler $handler,
    )
    {
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
    public function execute(Request $request): MetaResponse|NoContentResponse
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
