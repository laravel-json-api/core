<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Schema\StaticSchema;

use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Schema\StaticSchema\StaticSchema;

final class ThreadCachedStaticSchema implements StaticSchema
{
    /**
     * @var class-string<Schema>|null
     */
    private ?string $schemaClass = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $type = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $uriType = null;

    /**
     * @var class-string|null
     */
    private ?string $model = null;

    /**
     * @var class-string|null
     */
    private ?string $resourceClass = null;

    /**
     * ThreadCachedStaticSchema constructor.
     *
     * @param StaticSchema $base
     */
    public function __construct(private readonly StaticSchema $base)
    {
    }

    /**
     * @inheritDoc
     */
    public function getSchemaClass(): string
    {
        if ($this->schemaClass !== null) {
            return $this->schemaClass;
        }

        return $this->schemaClass = $this->base->getSchemaClass();
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        if ($this->type !== null) {
            return $this->type;
        }

        return $this->type = $this->base->getType();
    }

    /**
     * @inheritDoc
     */
    public function getUriType(): string
    {
        if ($this->uriType !== null) {
            return $this->uriType;
        }

        return $this->uriType = $this->base->getUriType();
    }

    /**
     * @inheritDoc
     */
    public function getModel(): string
    {
        if ($this->model !== null) {
            return $this->model;
        }

        return $this->model = $this->base->getModel();
    }

    /**
     * @inheritDoc
     */
    public function getResourceClass(): string
    {
        if ($this->resourceClass !== null) {
            return $this->resourceClass;
        }

        return $this->resourceClass = $this->base->getResourceClass();
    }
}