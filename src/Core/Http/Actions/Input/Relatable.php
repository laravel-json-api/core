<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Input;

trait Relatable
{
    use Identifiable;

    /**
     * @var string
     */
    private readonly string $fieldName;

    /**
     * @return string
     */
    public function fieldName(): string
    {
        return $this->fieldName;
    }
}