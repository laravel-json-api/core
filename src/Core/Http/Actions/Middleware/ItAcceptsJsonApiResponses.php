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
use LaravelJsonApi\Core\Http\Exceptions\HttpNotAcceptableException;
use Symfony\Component\HttpFoundation\Response;

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
    public function handle(ActionInput $action, Closure $next): Responsable|Response
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
