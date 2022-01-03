<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use IteratorAggregate;
use LaravelJsonApi\Contracts\ErrorProvider;
use LaravelJsonApi\Contracts\Serializable as SerializableContract;
use LaravelJsonApi\Core\Document\Concerns\Serializable;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Responses\Concerns\IsResponsable;
use Traversable;

class ErrorResponse implements SerializableContract, Responsable, ErrorProvider, IteratorAggregate
{

    use IsResponsable;
    use Serializable;

    /**
     * @var ErrorList
     */
    private ErrorList $errors;

    /**
     * @var int|null
     */
    private ?int $status = null;

    /**
     * Create an error response for a single error.
     *
     * @param  mixed $error
     * @return ErrorResponse
     */
    public static function error($error): self
    {
        return new self(Error::cast($error));
    }

    /**
     * Fluently construct an error response.
     *
     * @param ErrorList|ErrorProvider|Error|Error[] $errors
     * @return static
     */
    public static function make($errors): self
    {
        return new self($errors);
    }

    /**
     * ErrorResponse constructor.
     *
     * @param ErrorList|ErrorProvider|Error|Error[] $errors
     */
    public function __construct($errors)
    {
        $this->errors = ErrorList::cast($errors);
    }

    /**
     * Set the response status.
     *
     * This overrides the default status, which is derived from
     * the error list.
     *
     * @param int $status
     * @return $this
     */
    public function withStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toErrors(): ErrorList
    {
        return $this->errors;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->errors;
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        $this->defaultJsonApi();

        return new Response(
            $this->toJson($this->encodeOptions),
            $this->status ?: $this->errors->status(),
            $this->headers()
        );
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array_filter([
            'jsonapi' => $this->jsonApi()->toArray() ?: null,
            'meta' => $this->meta()->toArray() ?: null,
            'links' => $this->links()->toArray() ?: null,
            'errors' => $this->errors,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'jsonapi' => $this->jsonApi()->jsonSerialize(),
            'meta' => $this->meta()->jsonSerialize(),
            'links' => $this->links()->jsonSerialize(),
            'errors' => $this->errors,
        ]);
    }

}
