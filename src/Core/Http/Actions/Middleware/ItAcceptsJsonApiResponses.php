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
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Http\Actions\Action;
use LaravelJsonApi\Core\Http\Exceptions\HttpNotAcceptableException;
use LaravelJsonApi\Core\Responses\DataResponse;

class ItAcceptsJsonApiResponses implements HandlesActions
{
    /** @var string */
    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    /**
     * ItAcceptsJsonApiResponses constructor
     *
     * @param Translator $translator
     */
    public function __construct(private readonly Translator $translator)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(Action $action, Closure $next): DataResponse
    {
        if (!$this->isAcceptable($action->request())) {
            $message = $this->translator->get(
                "The requested resource is capable of generating only content not acceptable "
                . "according to the Accept headers sent in the request.",
            );

            throw new HttpNotAcceptableException($message);
        }

        return $next($action);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isAcceptable(Request $request): bool
    {
        return in_array(self::JSON_API_MEDIA_TYPE, $request->getAcceptableContentTypes(), true);
    }
}
