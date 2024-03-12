<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries;

use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Contracts\Support\Result as ResultContract;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\QueryParameters;
use LogicException;

class Result implements ResultContract
{
    /**
     * @var object|null
     */
    private ?object $relatedTo = null;

    /**
     * @var string|null
     */
    private ?string $fieldName = null;

    /**
     * @var ErrorList|null
     */
    private ?ErrorList $errors = null;

    /**
     * Return a success result.
     *
     * @param Payload $payload
     * @param QueryParametersContract $parameters
     * @return self
     */
    public static function ok(
        Payload $payload,
        QueryParametersContract $parameters = new QueryParameters()
    ): self
    {
        return new self(true, $payload, $parameters);
    }

    /**
     * Return a failure result.
     *
     * @param ErrorList|Error $errorOrErrors
     * @return self
     */
    public static function failed(ErrorList|Error $errorOrErrors = new ErrorList()): self
    {
        $result = new self(false);
        $result->errors = ErrorList::cast($errorOrErrors);

        return $result;
    }

    /**
     * Result constructor
     *
     * @param bool $success
     * @param Payload|null $payload
     * @param QueryParametersContract|null $query
     */
    private function __construct(
        private readonly bool $success,
        private readonly ?Payload $payload = null,
        private readonly ?QueryParametersContract $query = null,
    ) {
    }

    /**
     * @return Payload
     */
    public function payload(): Payload
    {
        if ($this->payload !== null) {
            return $this->payload;
        }

        throw new LogicException('Cannot get payload from a failed query result.');
    }

    /**
     * @return QueryParametersContract
     */
    public function query(): QueryParametersContract
    {
        if ($this->query !== null) {
            return $this->query;
        }

        throw new LogicException('Cannot get payload from a failed query result.');
    }

    /**
     * Return a new result instance that relates to the provided model and relation field name.
     *
     * For relationship results, the result will relate to the model via the provided
     * relationship field name. These need to be set on relationship results as JSON:API
     * relationship responses need both the model and field name to properly render the
     * JSON:API document.
     *
     * @param object $model
     * @param string $fieldName
     * @return self
     */
    public function withRelatedTo(object $model, string $fieldName): self
    {
        $copy = clone $this;
        $copy->relatedTo = $model;
        $copy->fieldName = $fieldName;

        return $copy;
    }

    /**
     * Return the model the result relates to.
     *
     * @return object
     */
    public function relatesTo(): object
    {
        return $this->relatedTo ?? throw new LogicException('Result is not a relationship result.');
    }

    /**
     * Return the relationship field name that the result relates to.
     *
     * @return string
     */
    public function fieldName(): string
    {
        return $this->fieldName ?? throw new LogicException('Result is not a relationship result.');
    }

    /**
     * @inheritDoc
     */
    public function didSucceed(): bool
    {
        return $this->success;
    }

    /**
     * @inheritDoc
     */
    public function didFail(): bool
    {
        return !$this->didSucceed();
    }

    /**
     * @inheritDoc
     */
    public function errors(): ErrorList
    {
        if ($this->errors) {
            return $this->errors;
        }

        return $this->errors = new ErrorList();
    }
}
