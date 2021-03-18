---
id: entities
title: Entities
slug: /entities
---

You can create entities by extending `Access/Entity` and implementing some
methods to tell Access what your entity is about. The methods you must implement
are `tableName` and `fields`, first is used to query the right database table
and the second is used access the right fields of that table.

## Table name

A very straight forward method to get the name of the table that is used for all
operations on this entity, `tableName` just return a string with the name of the
table.

```php
use Access\Entity;

class User extends Entity
{
    /**
     * Get the table name for this entity
     */
    public static function tableName(): string
    {
        return 'users';
    }
}
```

## Fields

The result of `fields` is an array with the field name for a key and a
definition as its value.

```php
use Access\Entity;

class User extends Entity
{
    /**
     * Get all fields needed for this entity to work
     */
    public static function fields(): array
    {
        return [
            'username' => [
                // some definition
            ],
        ];
    }
}
```

The definition supports the following options:

-   `type`: The type of the field, to be used in the conversion from and to the database. Allowed values:

    | Type                        | From database                      | To database                    |
    | --------------------------- | ---------------------------------- | ------------------------------ |
    | _default_                   | `string` _(no conversion is done)_ | _(no conversion is done)_      |
    | `self::FIELD_TYPE_INT`      | `int`                              | _(no conversion is done)_      |
    | `self::FIELD_TYPE_BOOL`     | `bool`                             | `1`/`0`                        |
    | `self::FIELD_TYPE_DATE`     | `\DateTimeImmutable`               | `Y-m-d` formatted string       |
    | `self::FIELD_TYPE_DATETIME` | `\DateTimeImmutable`               | `Y-m-d H:i:s` formatted string |
    | `self::FIELD_TYPE_JSON`     | `json_decode(<value>, true)`       | `json_encode(<value>)`         |

    ```php
    'is_admin' => [
        'type' => self::FIELD_TYPE_BOOL,
    ],
    ```

-   `default`: The default value of the field when inserting a new entity, will
    go through the conversion defined by the `type`. When a callback is used, it
    will be called on demand with the entity as the only parameter, useful for
    dates, generating random values.

    ```php
    'profile_image' => [
        'default' => null,
    ],
    ```

    ```php
    'secret' => [
        'default' => fn() => mt_rand(0, 1000),
    ],
    ```

-   `virtual`: A boolean indicating this value is not a real value, but is
    included in the entity as the result of a join/subquery. Will not be saved.

    ```php
    'total_projects' => [
        'virtual' => true,
    ],
    ```

-   `excludeInCopy`: When creating a copy with `Entity::copy` this field will be
    excluded

    ```php
    'published_at' => [
        'excludeInCopy' => true,
    ],
    ```

### Special fields

There are a couple of special fields that you don't need to specify in the
result of `fields`, namely:

#### ID

The `id` field will be managed automatically by Access and can not be changes
once set, the `Entity` class provides a `getId` to access it. This method will
throw is the ID is not available this the entity, if you want to know if the ID
is available there is `hasId`.

#### Timestamps

It is possible to enable the timestamp fields `created_at` and `updated_at` by
implementing the `timestamps` method and returning `true`. These fields will no
be available inside the entity. An easy way to get this working is by using the
`TimestampableTrait`, this will implement the `timestamps` method and provide a
couple of getters for the fields.

Once enabled the `Entity` class will update the fields when needed, on insert
and on on update. No need to do this yourself.

#### Soft delete

Access supports soft deleting entities out of the box with the `deleted_at`
field, it can be enabled for an entity by implementing `isSoftDeletable` and
returning `true`. Once the fields is filled, Access will no longer return the
entity from any of the query it runs. An easy way to get this working is by
using the `SoftDeletableTrait`, this will implement the `isSoftDeletable` method
and provide a couple of getters/setters for the fields.

The `Database` instance has a helper method to soft delete with a single method
call, instead of setting the `deleted_at` field and saving it:
`Database::softDelete()`.

### Access fields

Now that we have all fields defined, we need a way to access them from the
outside. The values itself are privately stored inside the `Entity` class and
can only be accessed by the protected getter (`get`) and setter (`set`).

```php
use Access\Entity;

class User extends Entity
{
    /**
     * Get the value of the `username` field
     */
    public function getUsername(): string
    {
        return $this->get('username');
    }

    /**
     * Set the value of the `username` field
     */
    public function setUsername(string $username): void
    {
        $this->set('username', $username);
    }
}
```

## Full example

A complete example

```php title="User.php"
<?php

use Access\Entity;

class User extends Entity
{
    /**
     * Get the table name for this entity
     */
    public static function tableName(): string
    {
        return 'users';
    }

    /**
     * Get all fields needed for this entity to work
     */
    public static function fields(): array
    {
        return [
            'username' => [
                // by default a field is of the type string/varchar/text
            ],
            'is_admin' => [
                // Access will convert this field from/to bool
                'type' => self::FIELD_TYPE_BOOL,

                // the default value for when inserting a new entity
                'default' => false,
            ],
            'metadata' => [
                // Access will convert this field from/to json
                'type' => self::FIELD_TYPE_JSON,
            ],
        ];
    }

    /**
     * Get the value of the `username` field
     */
    public function getUsername(): string
    {
        return $this->get('username');
    }

    /**
     * Set the value of the `username` field
     */
    public function setUsername(string $username): void
    {
        $this->set('username', $username);
    }
}
```
