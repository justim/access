---
id: queries
title: Queries
slug: /queries
---

At the heart of any database access tool is creating queries that you send to
the database. Access has a query builder to create queries with information from
your entities.

## Select queries

The most basic `SELECT` query (`SELECT * FROM users`) is created like this:

```php
use Access\Query;

$query = new Query\Select(User::class);

$query->getSql(); // SELECT * FROM `users`
```

### `WHERE` clauses

To make things a little bit more interesting you can add a `WHERE` clause to the
query.

```php
use Access\Query;

$query = new Query\Select(User::class);
$query->where('id = ?', 1);

$query->getSql(); // SELECT * FROM `users` WHERE (id = :w0)
$query->getValues(); // ['w0' => 1]
```

As you can see the query and the values are separate, when sending the query to
the database a prepared statement is used to prevent all kinds of injection
issues and speed up multiple of the same queries with different values. The `w0`
placeholder might seem a little big weird; every value used in the query has its
own unique name, this makes combining queries straight forward with subqueries.

All calls to `where` are added to the query with `AND`.

```php
use Access\Query;

$query = new Query\Select(User::class);
$query->where('id = ?', 1);
$query->where('username = ?', 'Dave');

$query->getSql(); // SELECT * FROM `users` WHERE (id = :w0) AND (username = :w1)
$query->getValues(); // ['w0' => 1, 'username' => 'Dave']
```

If you want to add them with `OR` you can use `whereOr`. `whereOr` accepts an
array of where clauses.

```php
use Access\Query;

$query = new Query\Select(User::class);
$query->whereOr([
    'id = ?' => 1,
    'id = ?' => 2,
]);

$query->getSql(); // SELECT * FROM `users` WHERE (id = :w0 OR id = :w1)
$query->getValues(); // ['w0' => 1, 'w1' => 2]
```

Another way to create the query from about is by using an array as a value for
the `id = ?` clauses.

```php
use Access\Query;

$query = new Query\Select(User::class);
$query->where('id IN (?)', [1, 2]);

$query->getSql(); // SELECT * FROM `users` WHERE (id IN (:w0, :w1))
$query->getValues(); // ['w0' => 1, 'w1' => 2]
```

And you can combine them, if you like

```php
use Access\Query;

$query = new Query\Select(User::class);
$query->whereOr([
    'id = ?' => 1,
    'id = ?' => 2,
]);
$query->where('username = ?', 'Dave');

$query->getSql(); // SELECT * FROM `users` WHERE (id = :w0 OR id = :w1) AND (username = :w2)
$query->getValues(); // ['w0' => 1, 'w1' => 2, 'w2' => 'Dave']
```

This syntax, providing an array, is also possible with the regular `where`
method, with the difference that those are joined with `AND`.

### Table aliasing

It is possible to create an alias for the table you are using, the name of the
table comes from inside the entity class and might change at any time. Also, it
might be too many characters, like `users`. I mean, who got time for that.

```php
use Access\Query;

$query = new Query\Select(User::class, 'u');
$query->where('u.id = ?', 1);

$query->getSql(); // SELECT `u`.* FROM `users` AS `u` WHERE (u.id = :w0)
```

This is of course a lot more useful if there are more tables involved...

### Joining other tables

A simple join is created by calling `innerJoin` on the query.

```php
use Access\Query;

$query = new Query\Select(User::class, 'u');
$query->innerJoin(Project::class, 'p', ['p.owner_id = u.id', 'p.id > ?' => 1]);

// SELECT `u`.* FROM `users` AS `u`
//   INNER JOIN `projects` AS `p` ON ((p.owner_id = u.id) AND (p.id > :j0))
```

Currently the only other type of join available is `LEFT JOIN` with
`Query::leftJoin`, more may follow in the future.

### Subqueries

There are two ways to inject a subquery into a query, as a virtual field, or as
a value for a where clause.

```php
use Access\Query;

$subQuery = new Query\Select(Project::class, 'p');
$subQuery->select('COUNT(p.id)');
$subQuery->where('p.owner_id = u.id');
$subQuery->where('p.id > ?', 1);

$query = new Query\Select(User::class, 'u', [
    'total_projects' => $subQuery,
]);

// SELECT `u`.*,
//   (SELECT COUNT(p.id) FROM `projects` AS `p`
//     WHERE (p.user_id = u.id) AND (p.id > :s0w0)
//   ) AS `total_projects`
// FROM `users` AS `u`
```

> Note the with `s0` prefixed placeholder for the subquery

The other way to inject a subquery is a value.

```php
use Access\Query;

$subQuery = new Query\Select(Project::class, 'p');
$subQuery->select('p.owner_id');
$subQuery->where('p.status = ?', 'IN_PROGRESS');
$subQuery->limit(1);

$query = new Select(User::class, 'u');
$query->where('u.id = ?', $subQuery);

// SELECT `u`.* FROM `users` AS `u`
//   WHERE (u.id = (SELECT p.user_id FROM `projects` AS `p` WHERE (p.status = :z0w0) LIMIT 1))',
```

Be careful when using subqueries, in our most cases you can only return a single
field and a single record, but this is not enforced. Keep this in mind.

### `HAVING` clauses

The `having` method works the same as the `where` method, with the difference
that the result clause is a `HAVING` clause. Not _having_ any special treatment,
besides, of course, it works on the fields from a subquery/aggregate clause.

### `ORDER BY` clause

Plain and simple; input is directly in output.

```php
use Access\Query;

$query = new Select(User::class, 'u');
$query->orderBy('u.id DESC');

// SELECT `u`.* FROM `users` AS `u` ORDER BY u.id DESC
```

### `LIMIT` clause

Also, plain and simple; input is directly in output.

```php
use Access\Query;

$query = new Select(User::class, 'u');
$query->limit(10);

// SELECT `u`.* FROM `users` AS `u` LIMIT 10
```

### Pagination cursors

A common reason to limit your query is for pagination, Access provideds a
mechanism to simplify this. There are two ways to get started with cursors,
first there is the simple page number cursor.

```php
use Access\Query\Select;
use Access\Query\Cursor\PageCursor;

$cursor = new PageCursor(3, 10); // defaults are 1 and 50

$query = new Select(User::class);
$query->orderBy('id ASC');
$query->applyCursor($cursor);

// SELECT `users`.* FROM `users` ORDER BY id ASC LIMIT 10 OFFSET 20
```

Using a simple limit/offset does not work in all cases, for example when your
list changes a lot and records would appear on a different page then when you
requested the page. A solution for this is to ask for the next number of
records, but skip the ones you already have.

```php
use Access\Query\Select;
use Access\Query\Cursor\CurrentIdsCursor;

$cursor = new CurrentIdsCursor([1, 2, 3], 10); // defaults are [] and 50

$query = new Select(User::class);
$query->orderBy('RAND()');
$query->applyCursor($cursor);

// SELECT `users`.* FROM `users`
//   WHERE (users.id NOT IN  (1, 2, 3)) ORDER BY RAND() LIMIT 10
```

The order is set to random, but you will still get a next "page" with completely
new records.

## Insert queries

You can create an insert query by creating `Query\Insert` and adding some values
to it.

```php
use Access\Query;

$query = new Query\Insert(User::class);
$query->values(['username' => 'Dave']);

// INSERT INTO `users` (username) VALUES (:p0)
```

:::note
All of the other methods are not supposed to be called, they bork your query as
some of them manipulate the values used in the query.
:::

In practice you would just use `Database::insert` with an entity.

```php
$user = new User();
$user->setUsername('Dave');

$this->db->insert($user);
```

## Update queries

Updating a bunch of records, or just a single one, can be done with
`Query\Update`. This follows the same structure as insert queries, with the
addition of [where clauses](#where-clauses).

```php
use Access\Query;

$query = new Query\Update(User::class);
$query->values(['username' => 'Not Dave']);

// UPDATE `users` SET `username` = :p0
```

Most of the time you only want to update a few select records, you can just add
a `WHERE` clause to the query, just like `Query\Select` queries.

```php
use Access\Query;

$query = new Query\Update(User::class);
$query->values(['username' => 'Not Dave']);
$query->where('id = ?', 1);

// UPDATE `users` SET `username` = :p0 WHERE (id = :w0)
```

You also get a little bit fancy by letting some other tables join the query.

```php title="Updating users that own a project"
use Access\Query;

$query = new Query\Update(User::class, 'u');
$query->innerJoin(Project::class, 'p', ['p.owner_id = u.id']);
$query->values(['username' => 'Not Dave']);

// UPDATE `users` AS `u`
//   INNER JOIN `projects` AS `p` ON (p.owner_id = u.id) SET `username` = :p0
```

## Delete queries

Deleting records can be done with `Query\Delete`.

```php
use Access\Query;

$query = new Query\Delete(User::class);
$query->values(['username' => 'Not Dave']);
$query->where('id = ?', 1);

// DELETE FROM `users` SET `username` = :p0 WHERE (id = :w0)
```

And again, adding some table to join the query is possible.

```php title="Deleting users that own a projects"
use Access\Query;

$query = new Query\Delete(User::class, 'u');
$query->innerJoin(Project::class, 'p', 'p.owner_id = u.id');

// DELETE `u` FROM `users` AS `u` INNER JOIN `projects` AS `p` ON (p.owner_id = u.id)
```

## Raw queries

If you ever run into the situation where you want to execute a query that is
not supported by the query builder, then you can resort to just using a raw
query -- a plain string.

```php
use Access\Query;

$query = new Query\Raw('CREATE TABLE ...');

// CREATE TABLE ...
```

## Soft deletable entities

Queries run on entities that are soft deletable result in a query that has
`deleted_at IS NULL` injected into them automatically. This makes sure soft
deleted entities never show up in the results of an query, or are even used in
the query.

```php
use Access\Entity;
use Access\Query;

class User extends Entity
{
    use Entity\SoftDeletableTrait;

    // etc
}

$query = new Query\Select(User::class, 'u');
$query->where('id = ?', 1);

// SELECT `u`.* FROM `users` AS `u` WHERE (`u`.`deleted_at` IS NULL) AND (u.id = :w0)
```

The same is true for joins, the `deleted_at IS NULL` condition is automatically
added to the `ON` clause.

```php
use Access\Query;

// Project is also soft deletable

$query = new Query\Select(User::class, 'u');
$query->innerJoin(Project::class, 'p', ['p.owner_id = u.id']);

// SELECT `u`.* FROM `users` AS `u`
//   INNER JOIN `projects` AS `p` ON ((`p`.`deleted_at` IS NULL) AND (p.owner_id = u.id))
//   WHERE (`u`.`deleted_at` IS NULL)
```

## Debug queries

If you want to know what query is sent to the database in a more human readable
for, you can use `Access\DebugQuery`. This will automatically fill all the
placeholders and convert the values to its database format. The result is _not_
what gets send over the wire.

```php
use Access\DebugQuery;

$query = new Query\Select(User::class);
$query->where('id = ?', 1);

$debugQuery = new DebugQuery($query);
$debugQuery->toRunnableQuery();

// SELECT * FROM `users` WHERE id = 1
```

:::warning Do _not_ use this in production code
Only use this to _view_ a more friendly version of the query for debug purposes
:::

## Limitations

Outside missing SQL features, another limitation of the Access query builder is
that is quite possible to create invalid. Some protections are in place, but
mostly, anything goes. The only "magic" that is in place is for the placeholder,
the rest is pretty much a fancy string concat; no validation is taking place
when building a query another limitation of the Access query builder is that is
quite possible to create invalid. Some protections are in place, but mostly,
anything goes. The only "magic" that is in place is for the placeholder, the
rest is pretty much a fancy string concat; no validation is taking place when
building a query. Most string are used "as is".

With great power comes great responsibility :)
