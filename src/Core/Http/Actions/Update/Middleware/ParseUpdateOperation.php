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
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Http\Actions\Update\HandlesUpdateActions;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;

class ParseUpdateOperation implements HandlesUpdateActions
{
    /**
     * ParseUpdateOperation constructor
     *
     * @param ResourceObjectParser $parser
     */
    public function __construct(private readonly ResourceObjectParser $parser)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(UpdateActionInput $action, Closure $next): DataResponse
    {
        $request = $action->request();

        $resource = $this->parser->parse(
            $request->json('data'),
        );

        return $next($action->withOperation(
            new Update(
                null,
                $resource,
                $request->json('meta') ?? [],
            ),
        ));
    }
}
