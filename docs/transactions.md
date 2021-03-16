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
