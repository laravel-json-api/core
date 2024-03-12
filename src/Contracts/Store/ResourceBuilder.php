<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Store;

use Illuminate\Support\ValidatedInput;

interface ResourceBuilder extends Builder
{
    /**
     * Store the resource using the supplied validated data.
     *
     * @param ValidatedInput $input
     * @return object
     *      the created or updated model.
     */
    public function store(ValidatedInput $input): object;
}
