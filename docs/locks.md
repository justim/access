---
id: locks
title: Locks
slug: /locks
---

A simple locking mechanism is in place to prevent race-conditions.

## Setup lock

You create an `Access\Lock` instance, this instance in itself will not so
anything. You need to tell the lock which tables you want to lock and for what
kind of access.

```php title="Setup lock"
use Access\Database;

$db = new Database(..);

// returns a `Access\Lock` instance
$lock = $db->createLock();

// lock the underlaying table of `User` with the alias `u`
$lock->read(User::class, 'u');

// alternatively you can use a `WRITE` lock
$lock->write(Project::class);
```

You can pass an alias to the `lock` and `write` methods, you'll need this if
query your data with aliases.

## Locking tables

Once you've setup the lock you need to actually lock the tables with
`Lock::lock()`, this will send a `LOCK` query to the database.

```php title="Locking the tables"
$lock->lock();
```

## Unlocking tables

When you're done with the tables you can unlock the tables with
`Lock::unlock()`, which will send a `UNLOCK` query to the database.

```php title="Unlocking the tables"
$lock->unlock();
```

:::note
Failing to unlock the tables after will result in an exception, this happens
when the `$lock` instance goes out of scope. Make sure to keep it around for as
long as you need your lock.
:::
