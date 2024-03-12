<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Update\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Spec\ResourceDocumentComplianceChecker;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Update\HandlesUpdateActions;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;

class CheckRequestJsonIsCompliant implements HandlesUpdateActions
{
    /**
     * CheckRequestJsonIsCompliant constructor
     *
     * @param ResourceDocumentComplianceChecker $complianceChecker
     */
    public function __construct(private readonly ResourceDocumentComplianceChecker $complianceChecker)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(UpdateActionInput $action, Closure $next): DataResponse
    {
        $result = $this->complianceChecker
            ->mustSee($action->type(), $action->id())
            ->check($action->request()->getContent());

        if ($result->didFail()) {
            throw new JsonApiException($result->errors());
        }

        return $next($action);
    }
}
