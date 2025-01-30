# Access
[![Continuous Integration](https://github.com/justim/access/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/justim/access/actions/workflows/continuous-integration.yml) [![Coverage Status](https://coveralls.io/repos/github/justim/access/badge.svg?branch=master)](https://coveralls.io/github/justim/access?branch=master)

> A simple MySQL wrapper optimized for bigger data sets

## Quick usage

```php
class User extends Entity {
    // ..
}

$db = Database::create('some PDO connection string');
$user = $db->findOne(User::class, 1);

$user->setName('Dave');
$db->update($user);

$users = $db->findBy(User::class, ['name' => 'Dave']);

// uses a generator
foreach ($users as $user) {
    // $user is an instance of User
}
```

## Features

- Uses a PDO prepared statement pool for faster queries
- Optimized for bulk queries (ie. easy to fetch collections of collections)
- Easily present your entities as JSON without worrying about n+1 queries

## Requirements

- PHP 8.1, or higher
- PDO extension

## Installation

- Available at [Packagist](https://packagist.org/packages/justim/access)
- `composer require justim/access=dev-master`

## Docs

For now, see the tests for usage.

## Nice to have

- For now only MySQL and SQLite is supported, it would be nice to have support for more RDMSs, like Postgres
- Tests are currently run with an in-memory SQLite database, a MySQL database would be better

## Final notes

Currently used in a single production product, features are tied to this product.
