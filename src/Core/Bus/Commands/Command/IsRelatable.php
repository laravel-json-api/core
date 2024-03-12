<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Command;

use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;

interface IsRelatable extends IsIdentifiable
{
    /**
     * Get the JSON:API field name for the relationship the command is targeting.
     *
     * @return string
     */
    public function fieldName(): string;

    /**
     * @return UpdateToOne|UpdateToMany
     */
    public function operation(): UpdateToOne|UpdateToMany;
}
