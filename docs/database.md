---
id: database
title: Database
slug: /database
---

The starting point for everything you can do with Access.

## Creating a database instance

There are two ways to create a database connection, with a PDO connection
string, or directly with an `\PDO` object.

```php title="With a connection string"
use Access\Database;

$db = Database::create('sqlite::memory:');
```

Using an existing connection might come in handy if you already have some
database connection available in your program. Or, if you want to configure the
connection that is used a bit more.

```php title="With an existing \PDO connection"
use Access\Database;

$connection = new \PDO('sqlite::memory:');

$db = new Database($connection);
```

Upon creating a connection a driver is selected based on the
`\PDO::ATTR_DRIVER_NAME` connection attribute. This driver has some information
about how to generate some queries/use features. For example, `MySQL` uses a
different function name for random than `SQLite`: `RAND()` vs `RANDOM()`.

The driver is injected into the query generator state when generating the SQL
and values, this is done with a new argument for the `getSql` method. This is a
nullable argument to be backward compatible with previous versions. All calls
inside Access will provide the right driver to this method; if you call this
method manually, make sure to provide the right driver
(`Database::getDriver()`).

In a future major version this argument will be mandatory.

Currently there are only drivers for MySQL and SQLite, with a very limited
feature set.

## Starting point for

-   Fetching and saving [entities](entities)
-   Instantiating [repositories](repositories)
-   Starting [transactions](transactions)
-   Accessing the configured [profiler](profiler)
-   Creating [locks](locks)
-   [Presenting](presenters) your entities as plain arrays
