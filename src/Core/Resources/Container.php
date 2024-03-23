<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Resources;

use Generator;
use LaravelJsonApi\Contracts\Resources\Container as ContainerContract;
use LaravelJsonApi\Contracts\Resources\Factory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use LogicException;
use function get_class;
use function is_iterable;
use function is_object;
use function sprintf;

final readonly class Container implements ContainerContract
{
    /**
     * Container constructor.
     *
     * @param Factory $factory
     */
    public function __construct(private Factory $factory)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve($value)
    {
        if ($value instanceof JsonApiResource) {
            return $value;
        }

        if (is_object($value) && $this->exists($value)) {
            return $this->create($value);
        }

        if (is_iterable($value)) {
            return $this->cursor($value);
        }

        throw new LogicException(sprintf(
            'Unable to resolve %s to a resource object. Check your resource configuration.',
            is_object($value) ? get_class($value) : 'non-object value'
        ));
    }

    /**
     * @inheritDoc
     */
    public function exists(object $model): bool
    {
        return $this->factory->canCreate($model);
    }

    /**
     * @inheritDoc
     */
    public function create(object $model): JsonApiResource
    {
        return $this->factory->createResource(
            $model
        );
    }

    /**
     * @inheritDoc
     */
    public function cast(object $modelOrResource): JsonApiResource
    {
        if ($modelOrResource instanceof JsonApiResource) {
            return $modelOrResource;
        }

        return $this->create($modelOrResource);
    }

    /**
     * @inheritDoc
     */
    public function cursor(iterable $models): Generator
    {
        foreach ($models as $model) {
            if ($model instanceof JsonApiResource) {
                yield $model;
                continue;
            }

            yield $this->create($model);
        }
    }

    /**
     * @inheritDoc
     */
    public function idFor(object $model): ResourceId
    {
        return new ResourceId(
            $this->create($model)->id(),
        );
    }

    /**
     * @inheritDoc
     */
    public function idForType(ResourceType $expected, object $model): ResourceId
    {
        $resource = $this->create($model);

        if ($expected->value !== $resource->type()) {
            throw new LogicException(sprintf(
                'Expecting resource type "%s" but provided model is of type "%s".',
                $expected,
                $resource->type(),
            ));
        }

        return new ResourceId($resource->id());
    }
}
