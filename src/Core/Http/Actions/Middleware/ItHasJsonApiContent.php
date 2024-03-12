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
