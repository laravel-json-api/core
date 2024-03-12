<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\DetachRelationship;

use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\DetachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\DetachRelationship\Middleware\AuthorizeDetachRelationshipAction;
use LaravelJsonApi\Core\Http\Actions\DetachRelationship\Middleware\ParseDetachRelationshipOperation;
use LaravelJsonApi\Core\Http\Actions\Middleware\CheckRelationshipJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateRelationshipQueryParameters;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;

class DetachRelationshipActionHandler
{
    /**
     * DetachRelationshipActionHandler constructor
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
     * Execute a detach relationship action.
     *
     * @param DetachRelationshipActionInput $action
     * @return RelationshipResponse|NoContentResponse
     */
    public function execute(DetachRelationshipActionInput $action): RelationshipResponse|NoContentResponse
    {
        $pipes = [
            ItHasJsonApiContent::class,
            ItAcceptsJsonApiResponses::class,
            LookupModelIfMissing::class,
            AuthorizeDetachRelationshipAction::class,
            CheckRelationshipJsonIsCompliant::class,
            ValidateRelationshipQueryParameters::class,
            ParseDetachRelationshipOperation::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn(DetachRelationshipActionInput $passed): RelationshipResponse => $this->handle($passed));

        assert(
            ($response instanceof RelationshipResponse) || ($response instanceof NoContentResponse),
            'Expecting action pipeline to return a data response.',
        );

        return $response;
    }

    /**
     * Handle the detach relationship action.
     *
     * @param DetachRelationshipActionInput $action
     * @return RelationshipResponse
     * @throws JsonApiException
     */
    private function handle(DetachRelationshipActionInput $action): RelationshipResponse
    {
        $commandResult = $this->dispatch($action);
        $queryResult = $this->query($action);
        $payload = $queryResult->payload();

        assert($payload->hasData, 'Expecting query result to have data.');

        return RelationshipResponse::make($action->modelOrFail(), $action->fieldName(), $payload->data)
            ->withMeta(array_merge($commandResult->meta, $payload->meta))
            ->withQueryParameters($queryResult->query());
    }

    /**
     * Dispatch the detach relationship command.
     *
     * @param DetachRelationshipActionInput $action
     * @return Payload
     * @throws JsonApiException
     */
    private function dispatch(DetachRelationshipActionInput $action): Payload
    {
        $command = DetachRelationshipCommand::make($action->request(), $action->operation())
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
     * Execute the query for the detach relationship action.
     *
     * @param DetachRelationshipActionInput $action
     * @return Result
     * @throws JsonApiException
     */
    private function query(DetachRelationshipActionInput $action): Result
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
