<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\AttachRelationship;

use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\AttachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\Middleware\AuthorizeAttachRelationshipAction;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\Middleware\ParseAttachRelationshipOperation;
use LaravelJsonApi\Core\Http\Actions\Middleware\CheckRelationshipJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateRelationshipQueryParameters;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;

class AttachRelationshipActionHandler
{
    /**
     * AttachRelationshipActionHandler constructor
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
     * Execute an attach relationship action.
     *
     * @param AttachRelationshipActionInput $action
     * @return RelationshipResponse|NoContentResponse
     */
    public function execute(AttachRelationshipActionInput $action): RelationshipResponse|NoContentResponse
    {
        $pipes = [
            ItHasJsonApiContent::class,
            ItAcceptsJsonApiResponses::class,
            LookupModelIfMissing::class,
            AuthorizeAttachRelationshipAction::class,
            CheckRelationshipJsonIsCompliant::class,
            ValidateRelationshipQueryParameters::class,
            ParseAttachRelationshipOperation::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn(AttachRelationshipActionInput $passed): RelationshipResponse => $this->handle($passed));

        assert(
            ($response instanceof RelationshipResponse) || ($response instanceof NoContentResponse),
            'Expecting action pipeline to return a data response.',
        );

        return $response;
    }

    /**
     * Handle the attach relationship action.
     *
     * @param AttachRelationshipActionInput $action
     * @return RelationshipResponse
     * @throws JsonApiException
     */
    private function handle(AttachRelationshipActionInput $action): RelationshipResponse
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
     * Dispatch the attach relationship command.
     *
     * @param AttachRelationshipActionInput $action
     * @return Payload
     * @throws JsonApiException
     */
    private function dispatch(AttachRelationshipActionInput $action): Payload
    {
        $command = AttachRelationshipCommand::make($action->request(), $action->operation())
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
     * Execute the query for the attach relationship action.
     *
     * @param AttachRelationshipActionInput $action
     * @return Result
     * @throws JsonApiException
     */
    private function query(AttachRelationshipActionInput $action): Result
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
