<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\FetchMany;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Values\ResourceType;

class FetchManyActionInputFactory
{
    /**
     * Make an input object for a fetch-many action.
     *
     * @param Request $request
     * @param ResourceType|string $type
     * @return FetchManyActionInput
     */
    public function make(Request $request, ResourceType|string $type): FetchManyActionInput
    {
        return new FetchManyActionInput(
            $request,
            ResourceType::cast($type),
        );
    }
}
