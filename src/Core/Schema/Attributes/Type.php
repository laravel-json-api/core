<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Schema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Type
{
    /**
     * Type constructor.
     *
     * @param non-empty-string|null $type
     * @param non-empty-string|null $uri how the type appears in a URI.
     */
    public function __construct(public ?string $type = null, public ?string $uri = null)
    {
    }
}