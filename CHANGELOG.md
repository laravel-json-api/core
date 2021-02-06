# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added

- **BREAKING** The `Contracts\Schema\Relation` contract now has a `isValidated()` method, to determine if the relation
  value should be merged when validating update requests. There is now a `Core\Schema\Concerns\RequiredForValidation`
  trait that can be used on relationship fields to implement the required method.
- New features for the `Core\Documents\ResourceObject` class:
    - New `merge()` method for merging two resource objects together. This is useful for update validation, where the
      values supplied by a client need to be merged over the existing resource field values.
    - The `putRelation` and `replace` methods now accept an instance of `UrlRoutable` for the `id` value of to-one or
      to-many relations.
- The schema container instance is now injected into schema classes via the constructor `$schemas` property. This has
  been added so that a schema class can be instantiated directly from the service container if the schema container is
  bound in the service container.
- New `Core\Resources\ConditionalList` class, for iterating over conditional attributes but yielding them as a
  zero-indexed array list.

### Changed

- The `Core\Document\ResourceObject::withoutLinks()` method now correctly removes both resource links and relationship
  links.

### Removed

- **BREAKING** Removed the `mustValidate()` and `isValidated()` methods from the `Core\Resources\Relation` class. These
  fields are now defined on the schema's relation field instead of the resource's relation.
- **BREAKING** Made changes to the `Core\Documents\ResourceObject` class:
    - Removed the deprecated `create()` method, as this was never intended to be brought in from the old package.
    - Remove the `Arrayable` contract (and therefore the `toArray()` method). This is because `toArray()` was always
      ambiguous - would it return the field values, or the JSON representation of the resource? Replace `toArray()`
      with `jsonSerialize()`. The `all()` method continues to return the field values.

## [1.0.0-alpha.2] - 2021-02-02

### Added

- [#2](https://github.com/laravel-json-api/core/pull/2)
  **BREAKING** The `Core\Resources\JsonApiResource` is no longer abstract, and now expects the schema *and* the model to
  be injected via its constructor. It will use the schema to convert a model to a JSON:API resource. This allows the
  resource classes to be optional, as the resource resolution logic can now fall-back to the `JsonApiResource` when no
  specific resource class exists. Schema fields must implement the `Contracts\Resources\Serializer\Attribute`
  and `Contracts\Resources\Serializer\Relation` interfaces on their fields for the serialization to work.
- **BREAKING** The `Contracts\Encoder\Encoder` contract now has a `withRequest` method to inject the current HTTP
  request into the encoding process. The response classes have been updated to pass the request through to the encoder
  in their `toResponse()` methods.
- **BREAKING** The `Contracts\Schema\Container` contract now has a `schemaForModel` method to lookup a schema by
  providing either a model instance, or the fully-qualified class name of a model.
- **BREAKING** The `Contracts\Schema\ID` contract now has a `key()` method, that can return the model key for the ID.
- **BREAKING** The `Contracts\Schema\Schema` contract now has new methods:
    - `uriType()` which returns the resource type as it appears in URIs.
    - `idKeyName()` which returns the object key for the `id` value.
- New `Contracts\Resources\JsonApiRelation` contract for the relation object returned by the
  `JsonApiResource::relationships()` method. This has the methods on it that encoders can rely on when encoding the
  relationship to JSON.
- **BREAKING** The `Contracts\Schema\Relation` contract now has a `uriName()` method for retrieving the relationship's
  field name as it appears in a URI. The `JsonApiResource`
  class now automatically injects this value from the schema field into the resource relation object.
- New `Core\Resources\ConditionalIterator` class for iterating over values that could contain conditional attributes.

### Changed

- **BREAKING** The `attributes`, `relationships`, `meta` and `links` method of the `JsonApiResource`
  class now require the request to be passed as a parameter. This is to bring the resource in line with Laravel's
  Eloquent resource, though our implementation allows the request to be `null` to cater for resource encoding outside of
  HTTP requests (e.g. queued broadcasting). Additionally, the `relationship` method return type has been changed to the
  new `Contracts\Resources\JsonApiResource`
  contract.
- **BREAKING** The `exists` and `create` methods on the `Contracts\Resources\Container` contract now correctly type-hint
  the parameter as an `object`.
- **BREAKING** The `createResource` method on the `Contracts\Resources\Factory` contract now correctly type-hints the
  parameter as an `object`.
- **BREAKING** The constructor of the `Core\Resources\Factory` class now expects a schema container and an optional
  array of resource bindings (instead of an iterable). If a `null` value is provided for the bindings, the bindings will
  be retrieved from the schema container. Additionally, the protected
  `build` method signature has been updated to correctly type-hint the second argument as an `object`.
- **BREAKING** The constructor arguments for the `Core\Resources\Relation` class have been changed so that it now
  receives the model and base URI - rather than the `JsonApiResource` object. This change was made so that it can be
  used more broadly.

### Removed

- **BREAKING** The `attach` and `attachAll` methods of the `Core\Resources\Factory` class have been removed, because
  they were not in use.

## [1.0.0-alpha.1] - 2021-01-25

Initial release.
