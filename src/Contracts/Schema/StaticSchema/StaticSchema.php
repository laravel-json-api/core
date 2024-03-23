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

use LaravelJsonApi\Contracts\Schema\Schema;

interface StaticSchema
{
    /**
     * Get the schema class.
     *
     * @return class-string<Schema>
     */
    public function getSchemaClass(): string;

    /**
     * Get the JSON:API resource type.
     *
     * @return non-empty-string
     */
    public function getType(): string;

    /**
     * Get the JSON:API resource type as it appears in URIs.
     *
     * @return non-empty-string
     */
    public function getUriType(): string;

    /**
     * Get the fully-qualified class name of the model.
     *
     * @return class-string
     */
    public function getModel(): string;

    /**
     * Get the fully-qualified class name of the resource.
     *
     * @return class-string
     */
    public function getResourceClass(): string;
}