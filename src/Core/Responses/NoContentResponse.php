<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Responses\Concerns\HasHeaders;

class NoContentResponse implements Responsable
{
    use HasHeaders;

    /**
     * @inheritDoc
     */
    public function toResponse($request): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT, $this->headers);
    }
}
