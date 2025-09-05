---
id: migrations
title: migrations
slug: /migrations
---

Get your desired schema applied to a database by running migrations. Migrations
are split into two parts: a constructive part and a destructive part. The
constructive part is meant to be run automatically, it should no break any
existing code. When this is done and the new code has been deployed and no old
is live anymore, the destructive part can be run manually.

This makes sure database changes related to each are colocated in the same
migration, but can be run on different times. Replacing one feature for another
would be a great use case.

## Migration class

Migrations are created by creating a class that extends
`Access\Migrations\Migration`. This class has two abstract methods that you
need to override to make the migration functional: `constructive` and
`revertConstructive`.

```php
class SomeMigration extends Migration
{
    public function constructive(SchemaChanges $schemaChanges): void
    {
        $schemaChanges->createTable('users');
    }
    
    public function revertConstructive(SchemaChanges $schemaChanges): void
    {
        $schemaChanges->dropTable('users');
    }
}
```

The `SchemaChanges` argument is used to add changes you the migration will
apply to the database.

## Migrator

Once the migration is created, it needs to be executed. For this, the
`Migrator` class exists. First the table that keep track of which migrations
are already executed needs to be created.

```php
$db = ...;
$migrator = new Migrator($db);
$migrator->init();
```

After the migrator has been initialized, the first migration can be run:

```php
$migration = new SomeMigration();
$result = $migrator->constructive($migration);
```

The `$result` will be a `MigrationResult` that contains a status and the
queries that have been executed.

## Migrations lifecycle

Migrations go through a specific lifecycle.

```mermaid
stateDiagram-v2
[*] --> NotInitialized
NotInitialized --> Initialized : init()
Initialized --> ConstructiveExecuted : constructive()
ConstructiveExecuted --> ConstructiveReverted : revertConstructive()
ConstructiveExecuted --> DestructiveExecuted : destructive()
ConstructiveReverted --> ConstructiveExecuted : constructive()
DestructiveExecuted --> DestructiveReverted : revertDestructive()
DestructiveReverted --> DestructiveExecuted : destructive()
DestructiveReverted --> ConstructiveReverted : revertConstructive()
```
