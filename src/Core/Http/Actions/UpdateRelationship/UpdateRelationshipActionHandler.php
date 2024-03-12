<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\UpdateRelationship;

use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\UpdateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\Middleware\CheckRelationshipJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateRelationshipQueryParameters;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\Middleware\AuthorizeUpdateRelationshipAction;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\Middleware\ParseUpdateRelationshipOperation;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class UpdateRelationshipActionHandler
{
    /**
     * UpdateRelationshipActionHandler constructor
     *
     * @param PipelineFactory $pipelines
     * @param CommandDispatcher $commands
     * @param QueryDispatcher $queries
     */
    public function __construct(
        private readonly PipelineFactory $pipelines,
        private readonly CommandDispatcher $commands,
        private readonly QueryDispatcher $queries,
    ) {
    }

    /**
     * Execute a update relationship action.
     *
     * @param UpdateRelationshipActionInput $action
     * @return RelationshipResponse
     */
    public function execute(UpdateRelationshipActionInput $action): RelationshipResponse
    {
        $pipes = [
            ItHasJsonApiContent::class,
            ItAcceptsJsonApiResponses::class,
            LookupModelIfMissing::class,
            AuthorizeUpdateRelationshipAction::class,
            CheckRelationshipJsonIsCompliant::class,
            ValidateRelationshipQueryParameters::class,
            ParseUpdateRelationshipOperation::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn(UpdateRelationshipActionInput $passed): RelationshipResponse => $this->handle($passed));

        if ($response instanceof RelationshipResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the update relationship action.
     *
     * @param UpdateRelationshipActionInput $action
     * @return RelationshipResponse
     * @throws JsonApiException
     */
    private function handle(UpdateRelationshipActionInput $action): RelationshipResponse
    {
        $commandResult = $this->dispatch($action);
        $model = $action->modelOrFail();
        $queryResult = $this->query($action, $model);
        $payload = $queryResult->payload();

        assert($payload->hasData, 'Expecting query result to have data.');

        return RelationshipResponse::make($model, $action->fieldName(), $payload->data)
            ->withMeta(array_merge($commandResult->meta, $payload->meta))
            ->withQueryParameters($queryResult->query());
    }

    /**
     * Dispatch the update relationship command.
     *
     * @param UpdateRelationshipActionInput $action
     * @return Payload
     * @throws JsonApiException
     */
    private function dispatch(UpdateRelationshipActionInput $action): Payload
    {
        $command = UpdateRelationshipCommand::make($action->request(), $action->operation())
            ->withModel($action->modelOrFail())
            ->withQuery($action->queryParameters())
            ->withHooks($action->hooks())
            ->skipAuthorization();

        $result = $this->commands->dispatch($command);

        if ($result->didSucceed()) {
            return $result->payload();
        }

        throw new JsonApiException($result->errors());
    }

    /**
     * Execute the query for the update relationship action.
     *
     * @param UpdateRelationshipActionInput $action
     * @return Result
     * @throws JsonApiException
     */
    private function query(UpdateRelationshipActionInput $action): Result
    {
        $query = FetchRelationshipQuery::make($action->request(), $action->query())
            ->withModel($action->modelOrFail())
            ->withValidated($action->queryParameters())
            ->skipAuthorization();

        $result = $this->queries->dispatch($query);

        if ($result->didSucceed()) {
            return $result;
        }

        throw new JsonApiException($result->errors());
    }
}
