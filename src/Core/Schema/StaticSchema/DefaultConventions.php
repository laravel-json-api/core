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

use LaravelJsonApi\Contracts\Schema\StaticSchema\ServerConventions;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Support\Str;

final class DefaultConventions implements ServerConventions
{
    /**
     * @inheritDoc
     */
    public function getTypeFor(string $schema): string
    {
        return Str::plural(Str::dasherize(
            Str::replaceLast('Schema', '', class_basename($schema)),
        ));
    }

    /**
     * @inheritDoc
     */
    public function getUriTypeFor(string $type): string
    {
        return Str::dasherize($type);
    }

    /**
     * @inheritDoc
     */
    public function getResourceClassFor(string $schema): string
    {
        $guess = Str::replaceLast('Schema', 'Resource', $schema);

        return class_exists($guess) ? $guess : JsonApiResource::class;
    }
}