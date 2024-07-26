---
id: transactions
title: Transactions
slug: /transactions
---

Access provides a mechanism to use transactions.

## Start transaction

To start a transaction you need a `Access\Database` instance and call
`beginTransaction()`.

```php title="Start transction"
use Access\Database;

$db = new Database(..);

$transaction = $db->beginTransaction();
```

## Commit transaction

Once you've done all changes you can commit the transaction with
`Transaction::commit()`, this will send a `COMMIT` query to the database

```php title="Commit transaction"
$transaction->commit();
```

## Rollback transaction

If you're not happy with the changes you've made, most likely an invalid state
somewhere, somehow, you can roll back the changes you've made and prepend it
never happened. You can do this with `Transaction::rollBack`.

```php title="Roll back transaction"
$transaction->rollBack();
```

:::note
Failing to either commit or rollback the will result in an exception, this
happens when the `$transaction` instance goes out of scope. Make sure to keep it
around for as long as you need to keep the transaction open.
:::

:::note
The `BEGIN`, `COMMIT` and `ROLLBACK` queries are not actually executed. The
underlying connection is used directly to start/commit/rollback transactions.
The queries are added to the profiler to indicate these actions were executed.
:::

## Save points

When you try to start a transaction and one is already in progress, then a save
point will be created. From there, a commit will do nothing, as the outer
transaction is still in progress and will eventually do the actual commit. A
rollback will do a rollback to the save point.

```php title="Savepoints"
// start outer transaction
$transactionOuter = $db->beginTransaction();

// create a savepoint
$transactionInner = $db->beginTransaction();

try {
    // ..
    $transactionInner->commit(); // no-op
} catch (\Exception) {
    $transactionInner->rollBack(); // roll back to the created savepoint
}

// commit all changes. changes from the `try`-block might be rolled back,
// changes from before savepoint are commited
$transactionOuter->commit();
```
