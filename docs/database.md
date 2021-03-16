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

\$connection = new \PDO('sqlite::memory:');

$db = new Database($connection);
```

## Starting point for

-   Fetching and saving [entities](entities)
-   Instantiating [repositories](repositories)
-   Starting [transactions](transactions)
-   Accessing the configured [profiler](profiler)
-   Creating [locks](locks)
-   [Presenting](presenters) your entities as plain arrays
