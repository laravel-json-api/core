<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Values;

class ModelOrResourceId
{
    /**
     * @var object|null
     */
    private ?object $model;

    /**
     * @var ResourceId|null
     */
    private ?ResourceId $id;

    /**
     * Fluent constructor.
     *
     * @param object|string $modelOrResourceId
     * @return static
     */
    public static function make(object|string $modelOrResourceId): self
    {
        return new self($modelOrResourceId);
    }

    /**
     * ModelOrResourceId constructor
     *
     * @param object|string $modelOrResourceId
     */
    public function __construct(object|string $modelOrResourceId)
    {
        if ($modelOrResourceId instanceof ResourceId || is_string($modelOrResourceId)) {
            $this->id = ResourceId::cast($modelOrResourceId);
            $this->model = null;
            return;
        }

        $this->model = $modelOrResourceId;
        $this->id = null;
    }

    /**
     * @return object|null
     */
    public function model(): ?object
    {
        return $this->model;
    }

    /**
     * @return object
     */
    public function modelOrFail(): object
    {
        assert($this->model !== null, 'Expecting a model to be set.');

        return $this->model;
    }

    /**
     * @return ResourceId|null
     */
    public function id(): ?ResourceId
    {
        return $this->id;
    }
}
