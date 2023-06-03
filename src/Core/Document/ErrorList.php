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

namespace LaravelJsonApi\Core\Document;

use Countable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Enumerable;
use InvalidArgumentException;
use IteratorAggregate;
use LaravelJsonApi\Contracts\ErrorProvider;
use LaravelJsonApi\Contracts\Serializable;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LogicException;
use Traversable;
use function array_merge;
use function collect;

class ErrorList implements Serializable, Countable, IteratorAggregate, Responsable
{

    use Concerns\Serializable;

    /**
     * @var array
     */
    private array $stack;

    /**
     * Create a list of JSON:API error objects.
     *
     * @param ErrorList|ErrorProvider|Error|Enumerable|array $value
     * @return ErrorList
     */
    public static function cast($value): ErrorList
    {
        if ($value instanceof ErrorList) {
            return $value;
        }

        if ($value instanceof ErrorProvider) {
            return $value->toErrors();
        }

        if ($value instanceof Error) {
            return new ErrorList($value);
        }

        if (is_array($value) || $value instanceof Enumerable) {
            return ErrorList::fromArray($value);
        }

        throw new LogicException('Unexpected error collection value.');
    }

    /**
     * @param array|Enumerable $value
     * @return ErrorList
     */
    public static function fromArray($value): self
    {
        if (!is_array($value) && !$value instanceof Enumerable) {
            throw new InvalidArgumentException('Expecting an array or enumerable object.');
        }

        $errors = new self();
        $errors->stack = collect($value)->map(function ($error) {
            return Error::cast($error);
        })->values()->all();

        return $errors;
    }

    /**
     * Errors constructor.
     *
     * @param Error ...$errors
     */
    public function __construct(Error ...$errors)
    {
        $this->stack = $errors;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->stack = array_map(fn($error) => clone $error, $this->stack);
    }

    /**
     * Get the most applicable HTTP status code.
     *
     * When a server encounters multiple problems for a single request, the most generally applicable HTTP error
     * code SHOULD be used in the response. For instance, 400 Bad Request might be appropriate for multiple
     * 4xx errors or 500 Internal Server Error might be appropriate for multiple 5xx errors.
     *
     * @param int the default status to return, if there are no statuses.
     * @return int
     * @see https://jsonapi.org/format/#errors
     */
    public function status(int $default = Response::HTTP_INTERNAL_SERVER_ERROR): int
    {
        $statuses = collect($this->stack)
            ->map(fn(Error $error) => intval($error->status()))
            ->filter()
            ->unique();

        if (2 > count($statuses)) {
            return $statuses->first() ?: $default;
        }

        $only4xx = $statuses->every(fn(int $status) => 400 <= $status && 499 >= $status);

        return $only4xx ? Response::HTTP_BAD_REQUEST : Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Add errors.
     *
     * @param Error ...$errors
     * @return $this
     */
    public function push(Error ...$errors): self
    {
        foreach ($errors as $error) {
            $this->stack[] = $error;
        }

        return $this;
    }

    /**
     * Merge errors.
     *
     * @param iterable $errors
     * @return $this
     */
    public function merge(iterable $errors): self
    {
        if ($errors instanceof static) {
            $this->stack = array_merge($this->stack, $errors->stack);
            return $this;
        }

        return $this->push(...$errors);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->stack);
    }

    /**
     * @return Error[]
     */
    public function all(): array
    {
        return $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return collect($this->stack)->toArray();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->stack;
    }

    /**
     * @param $request
     * @return ErrorResponse
     */
    public function prepareResponse($request): ErrorResponse
    {
        return new ErrorResponse($this);
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return $this
            ->prepareResponse($request)
            ->toResponse($request);
    }

}
