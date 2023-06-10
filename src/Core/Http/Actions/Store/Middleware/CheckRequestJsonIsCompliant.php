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

namespace LaravelJsonApi\Core\Http\Actions\Store\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Spec\ResourceDocumentComplianceChecker;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Store\HandlesStoreActions;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;

class CheckRequestJsonIsCompliant implements HandlesStoreActions
{
    /**
     * CheckJsonApiSpecCompliance constructor
     *
     * @param ResourceDocumentComplianceChecker $complianceChecker
     */
    public function __construct(private readonly ResourceDocumentComplianceChecker $complianceChecker)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(StoreActionInput $action, Closure $next): DataResponse
    {
        $result = $this->complianceChecker
            ->mustSee($action->type())
            ->check($action->request()->getContent());

        if ($result->didFail()) {
            throw new JsonApiException($result->errors());
        }

        return $next($action);
    }
}
