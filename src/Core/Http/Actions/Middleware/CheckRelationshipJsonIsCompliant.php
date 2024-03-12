<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
