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
use LaravelJsonApi\Core\Values\ResourceType;

interface RelationshipDocumentComplianceChecker
{
    /**
     * Set the resource type the relationship belongs to, and the relationship field name.
     *
     * @param ResourceType|string $type
     * @param string $fieldName
     * @return $this
     */
    public function mustSee(ResourceType|string $type, string $fieldName): static;

    /**
     * Check whether the provided content passes compliance with the JSON:API spec.
     *
     * @param string $json
     * @return Result
     */
    public function check(string $json): Result;
}
