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
final readonly class ResourceClass
{
    /**
     * ResourceClass constructor.
     *
     * @param class-string $value
     */
    public function __construct(public string $value)
    {
    }
}