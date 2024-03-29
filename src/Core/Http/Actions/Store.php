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
use LaravelJsonApi\Contracts\Http\Actions\Store as StoreContract;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionHandler;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInputFactory;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Values\ResourceType;
use Symfony\Component\HttpFoundation\Response;

class Store implements StoreContract
{
    /**
     * @var ResourceType|null
     */
    private ?ResourceType $type = null;

    /**
     * @var object|null
     */
    private ?object $hooks = null;

    /**
     * Store constructor
     *
     * @param Route $route
     * @param StoreActionInputFactory $factory
     * @param StoreActionHandler $handler
     */
    public function __construct(
        private readonly Route $route,
        private readonly StoreActionInputFactory $factory,
        private readonly StoreActionHandler $handler,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function withType(ResourceType|string $type): static
    {
        $this->type = ResourceType::cast($type);

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

        $input = $this->factory
            ->make($request, $type)
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