<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Spec;

use LaravelJsonApi\Contracts\Support\Result;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

interface ResourceDocumentComplianceChecker
{
    /**
     * Set the expected resource type and id in the document.
     *
     * @param ResourceType|string $type
     * @param ResourceId|string|null $id
     * @return $this
     */
    public function mustSee(ResourceType|string $type, ResourceId|string $id = null): static;

    /**
     * Check whether the provided content passes compliance with the JSON:API spec.
     *
     * @param string $json
     * @return Result
     */
    public function check(string $json): Result;
}
