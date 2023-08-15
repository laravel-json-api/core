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
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Exceptions\HttpUnsupportedMediaTypeException;

class ItHasJsonApiContent implements HandlesActions
{
    /** @var string */
    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    /**
     * ItHasJsonApiContent constructor
     *
     * @param Translator $translator
     */
    public function __construct(private readonly Translator $translator)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(ActionInput $action, Closure $next): Responsable
    {
        if (!$this->isSupported($action->request())) {
            throw new HttpUnsupportedMediaTypeException(
                $this->translator->get(
                    'The request entity has a media type which the server or resource does not support.',
                ),
            );
        }

        return $next($action);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isSupported(Request $request): bool
    {
        return Request::matchesType(self::JSON_API_MEDIA_TYPE, $request->header('CONTENT_TYPE'));
    }
}
