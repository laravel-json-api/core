<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Schema\StaticSchema;

use IteratorAggregate;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Core\Values\ResourceType;

/**
 * @implements IteratorAggregate<StaticSchema>
 */
interface StaticContainer extends IteratorAggregate
{
    /**
     * Get a static schema for the specified schema class.
     *
     * @param class-string<Schema>|Schema $schema
     * @return StaticSchema
     */
    public function schemaFor(string|Schema $schema): StaticSchema;

    /**
     * Does a schema exist for the supplied JSON:API resource type?
     *
     * @param ResourceType|non-empty-string $type
     * @return bool
     */
    public function exists(ResourceType|string $type): bool;

    /**
     * Get the (non-static) schema class for a JSON:API resource type.
     *
     * @param ResourceType|non-empty-string $type
     * @return class-string<Schema>
     */
    public function schemaClassFor(ResourceType|string $type): string;

    /**
     * Get the fully qualified model class for the provided JSON:API resource type.
     *
     * @param ResourceType|non-empty-string $type
     * @return string
     */
    public function modelClassFor(ResourceType|string $type): string;

    /**
     * Get the JSON:API resource type for the provided type as it appears in URLs.
     *
     * @param non-empty-string $uriType
     * @return ResourceType|null
     */
    public function typeForUri(string $uriType): ?ResourceType;

    /**
     * Get a list of all the supported JSON:API resource types.
     *
     * @return array<non-empty-string>
     */
    public function types(): array;
}