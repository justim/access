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

## Combining locks

Centralizing lock information can be tricky in some cases, sometimes another
method needs to be called that also requests access to some tables. By merging
locks you can colocate the lock creation by the method that needs them.

```php
// create a lock like normal
$lock = $db->createLock();
$lock->read(User::class, 'u');

// merge the lock from some other service
$lock->merge($someService->createLockForSomeMethod());

// lock like normal
$lock->lock();

// call the method, with a lock in place
$someService->someMethod();
```

To be sure inside the `someMethod` method that the locks are actually in place,
it's possible to check for lock requirements.

```php
// request the lock as part of the argument contract
public function someMethod(Lock $lock): void
{
    // must be locked
    if (!$lock->isLocked()) {
        throw new Exception('Must be locked');
    }

    // the needed tables are locked
    if (!$lock->contains($this->createLockForSomeMethod())) {
        throw new Exception('Missing tables');
    }

    // ..
}
```

This way it's no possibe to call `someMethod` without the proper lock in place.
