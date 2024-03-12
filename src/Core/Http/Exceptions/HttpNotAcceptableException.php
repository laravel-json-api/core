<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class HttpNotAcceptableException extends HttpException
{
    /**
     * HttpNotAcceptableException constructor.
     *
     * @param string $message
     * @param Throwable|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(
        string $message = '',
        Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        parent::__construct(Response::HTTP_NOT_ACCEPTABLE, $message, $previous, $headers, $code);
    }
}
