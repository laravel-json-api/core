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

interface ServerConventions
{
    /**
     * Resolve the JSON:API resource type for the provided schema.
     *
     * @param class-string<Schema> $schema
     * @return non-empty-string
     */
    public function getTypeFor(string $schema): string;

    /**
     * Resolve the JSON:API resource type as it appears in URIs, for the provided resource type.
     *
     * @param non-empty-string $type
     * @return non-empty-string|null
     */
    public function getUriTypeFor(string $type): ?string;

    /**
     * @param class-string<Schema> $schema
     * @return class-string
     */
    public function getResourceClassFor(string $schema): string;
}