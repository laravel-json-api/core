<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizer as ResourceAuthorizerContract;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final readonly class ResourceAuthorizer implements ResourceAuthorizerContract
{
    /**
     * ResourceAuthorizer constructor
     *
     * @param AuthorizerContract $authorizer
     * @param string $modelClass
     */
    public function __construct(
        private AuthorizerContract $authorizer,
        private string $modelClass,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function index(?Request $request): ?ErrorList
    {
        $passes = $this->authorizer->index(
            $request,
            $this->modelClass,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function indexOrFail(?Request $request): void
    {
        if ($errors = $this->index($request)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function store(?Request $request): ?ErrorList
    {
        $passes = $this->authorizer->store(
            $request,
            $this->modelClass,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function storeOrFail(?Request $request): void
    {
        if ($errors = $this->store($request)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function show(?Request $request, object $model): ?ErrorList
    {
        $passes = $this->authorizer->show(
            $request,
            $model,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function showOrFail(?Request $request, object $model): void
    {
        if ($errors = $this->show($request, $model)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function update(?Request $request, object $model): ?ErrorList
    {
        $passes = $this->authorizer->update(
            $request,
            $model,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function updateOrFail(?Request $request, object $model): void
    {
        if ($errors = $this->update($request, $model)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function destroy(?Request $request, object $model): ?ErrorList
    {
        $passes = $this->authorizer->destroy(
            $request,
            $model,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function destroyOrFail(?Request $request, object $model): void
    {
        if ($errors = $this->destroy($request, $model)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function showRelated(?Request $request, object $model, string $fieldName): ?ErrorList
    {
        $passes = $this->authorizer->showRelated(
            $request,
            $model,
            $fieldName,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function showRelatedOrFail(?Request $request, object $model, string $fieldName): void
    {
        if ($errors = $this->showRelated($request, $model, $fieldName)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function showRelationship(?Request $request, object $model, string $fieldName): ?ErrorList
    {
        $passes = $this->authorizer->showRelationship(
            $request,
            $model,
            $fieldName,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function showRelationshipOrFail(?Request $request, object $model, string $fieldName): void
    {
        if ($errors = $this->showRelationship($request, $model, $fieldName)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function updateRelationship(?Request $request, object $model, string $fieldName): ?ErrorList
    {
        $passes = $this->authorizer->updateRelationship(
            $request,
            $model,
            $fieldName,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function updateRelationshipOrFail(?Request $request, object $model, string $fieldName): void
    {
        if ($errors = $this->updateRelationship($request, $model, $fieldName)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function attachRelationship(?Request $request, object $model, string $fieldName): ?ErrorList
    {
        $passes = $this->authorizer->attachRelationship(
            $request,
            $model,
            $fieldName,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function attachRelationshipOrFail(?Request $request, object $model, string $fieldName): void
    {
        if ($errors = $this->attachRelationship($request, $model, $fieldName)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @inheritDoc
     */
    public function detachRelationship(?Request $request, object $model, string $fieldName): ?ErrorList
    {
        $passes = $this->authorizer->detachRelationship(
            $request,
            $model,
            $fieldName,
        );

        return $this->parse($passes);
    }

    /**
     * @inheritDoc
     */
    public function detachRelationshipOrFail(?Request $request, object $model, string $fieldName): void
    {
        if ($errors = $this->detachRelationship($request, $model, $fieldName)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @param bool|Response $result
     * @return ErrorList|null
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws HttpExceptionInterface
     */
    private function parse(bool|Response $result): ?ErrorList
    {
        if ($result instanceof Response) {
            $result->authorize();
            return null;
        }

        return $result ? null : $this->failed();
    }

    /**
     * @return ErrorList
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    private function failed(): ErrorList
    {
        return ErrorList::cast(
            $this->authorizer->failed(),
        );
    }
}
