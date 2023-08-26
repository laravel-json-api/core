<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Middleware;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Contracts\Spec\RelationshipDocumentComplianceChecker;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\IsRelatable;

class CheckRelationshipJsonIsCompliant
{
    /**
     * CheckRequestJsonIsCompliant constructor
     *
     * @param RelationshipDocumentComplianceChecker $complianceChecker
     */
    public function __construct(private readonly RelationshipDocumentComplianceChecker $complianceChecker)
    {
    }

    /**
     * Handle a relatable action.
     *
     * @param ActionInput&IsRelatable $action
     * @param Closure $next
     * @return Responsable
     * @throws JsonApiException
     */
    public function handle(ActionInput&IsRelatable $action, Closure $next): Responsable
    {
        $result = $this->complianceChecker
            ->mustSee($action->type(), $action->fieldName())
            ->check($action->request()->getContent());

        if ($result->didFail()) {
            throw new JsonApiException($result->errors());
        }

        return $next($action);
    }
}
