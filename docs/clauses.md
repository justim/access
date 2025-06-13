---
id: clauses
title: Clauses
slug: /clauses
---

Clauses are Access' way to store query conditions, order by information and
filters. These clauses can be converted to SQL, be used to filter manipulate
collections.

There are three types of clauses: conditions, filters, and orders.

## Condition clauses

Condition clauses can be used to add `WHERE` conditions to a query, there are a
bunch of them builtin. A lot of these clauses will speak for themselves.

-   `Access\Clauses\Condition\Equals`
-   `Access\Clauses\Condition\GreaterThan`
-   `Access\Clauses\Condition\GreaterThanOrEquals`
-   `Access\Clauses\Condition\IsNotNull`
-   `Access\Clauses\Condition\IsNull`
-   `Access\Clauses\Condition\LessThen`
-   `Access\Clauses\Condition\LessThanOrEquals`
-   `Access\Clauses\Condition\NotEquals`
-   `Access\Clauses\Condition\In`
-   `Access\Clauses\Condition\NotIn`

The condition clauses can be used in code and in SQL.

```php
// in query form
$query = new Select(...);
$query->where(Equals('id', 1));

// SELECT * FROM ... WHERE id = 1
```

```php
// in code form
$users = $userRepo->findAllCollection(...)
$users->applyClause(Equals('id', 1));
```

The code form will still fetch all the users from the database, so this form is
mostly useful you want to select a couple of records from the collection, but
also want to keep the original collection around. Pre-fetching a bunch of
records to process later to prevent `n+1` queries is a great example of this.
This is how the clauses are used in the [presenters](presenters).

## Ordering clauses

To order the projects you can use the `Access\Clause\OrderBy` clauses. There
are only three order by clauses:

-   `Access\Clause\OrderBy\Ascending`
-   `Access\Clause\OrderBy\Descending`
-   `Access\Clause\OrderBy\Random`

The condition clauses can be used in code and in SQL.

```php
// in query form
$query = new Select(...);
$query->orderBy(Ascending('id'));

// SELECT * FROM ... ORDER BY id ASC
```

```php
// in code form
$users = $userRepo->findAllCollection(...)
$users->applyClause(Ascending('id'));
```

## Filtering clauses

Currently there is a single filter clauses, and it only has a code form, it
can't be used in a query.

-   `Access\Clause\Filter\Unique`: Will filter out entities with a duplicate
    field value

## Multiple clauses

And if you want to mix multiple clauses together, if, for example, you want the
list to contain published projects and also want to order them. The special
`Access\Clauses\Multiple` clause will help you here, it combines multiple
conditions together and all conditions need to be true. Or if you provide
multiple order by clauses they all will be used (you can sorting on status and
then on name, for example). There is also the `Access\Clauses\MultipleOr` clause
if you want only one of the condition clauses to be true. You can add as many
clauses to the `Multiple` as you like, and you can mix order by and condition
clauses.

```php
// in query form
$query = new Select(...);
$query->where(new Multiple(
    Equals('id', 1),
    Equals('name', 'Dave'),
));

// SELECT * FROM ... WHERE id = 1 AND name = "Dave"
```

When applying `Multiple` clauses to a collection, conditions and orders can be
mixed. This is how presenter can use a "single" clause parameter for multiple
purposes.

```php
$users = $userRepo->findAllCollection(...)
$users->applyClause(new Multiple(
    Equals('name', 'Dave'),
    Ascending('id'),
));
```

### Empty multiple clauses

When a `Multiple` clause is empty there is a bit of special handling. When used
in the context of a condition, it will _not_ match any entities and `1 = 2` is
used in a query. This is to prevent accidental overfetching, when building
`Multiple` clauses programmatically. When there are mixed clauses inside the
`Multiple` clause, if it contains a single condition it is considered a
condition clause, or if it's completely empty (no "type" can be determined).

When used in the context of ordering, it will just be ignored.
