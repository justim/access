---
id: repositories
title: Repositories
slug: /repositories
---

To get the repository associated with an entity, you can call the
`getRepository` method on the database instance. This returns a
`Access\Repository` instance.

```php
$userRepo = $db->getRepository(User::class);
$user = $userRepo->findOne($userId);
```

When the default methods on the database instance are not enough anymore, and
the default repository also comes up short, it's time to create a custom
repository for an entity to fetch entity with more complex logic. A repository
is a collection of methods that hide away the query executed to the outside
world. For example, if you want a list of all users that have a project
attached, you create a method called `findUsersWithProjects` and define a query
inside. When you use the repository the only this you know is that you can get a
list of users with a project, but how that is achieved does not matter to that
the outside, only that you get a list.

So, let's first create a repository.

```php title="UserRepository.php"
use Access\Repository;

class UserRepository extends Repository
{
}
```

By default this repository has no more functionality that the default repository
already available for all entities. To make the repository more usefull, let's
add a method to it.

```php title="UserRepository.php"
use Access\Query;
use Access\Repository;

class UserRepository extends Repository
{
    public function findUsersWithProjects(): \Generator
    {
        $query = new Query\Select(User::class);
        $query->where('has_project = ?', true);

        return $this->select($query);
    }
}
```

:::note [Queries](queries)
For more information about queries, [you can read all about them here](queries).
:::

## Helper methods

The pattern is the same each time, you define a method, create a query and
return it with a helper method of the `Access\Repository` class. These methods
determine what gets returned to the user, some simply returning a single
entities to all kinds of lists.

### Single result helpers

-   `selectOne`: Select a single entity, will set the limit to `1` and return the
    first result as an entity, or `null` if there are no results.

    ```php
    public function findSomeUser(int $userId): \User
    {
        $query = new Query\Select(User::class);
        $query->where('id = ?', $userId);

        return $this->selectOne($query);
    }
    ```

-   `selectOneVirtualField`: Select a single field from the first result, this
    field does not need to be a field of the table, but does need to be defined in
    the entity fields as a `virtual` field.

    ```php
    public function findNumberOfUsers(int $userId): int
    {
        $query = new Query\Select(User::class, 'u', [
            'total' => 'COUNT(*)',
        ]);

        return $this->selectOneVirtualField($query, 'total', 'int');
    }
    ```

### Multiple result helpers

-   `select`: Returns all results that the query produces with a generator, the
    generator yields entities.

    ```php
    public function findUsersWithProjects(): \Generator
    {
        $query = new Query\Select(User::class);
        $query->where('has_project = ?', true);

        return $this->select($query);
    }
    ```

-   `selectCollection`: Combine all results into a `Access\Collection` to make
    working with a bunch of entities a lot easier. [Read more about
    collections](collections).

    ```php
    use Access\Collection;

    public function findUsersWithProjects(): Collection
    {
        $query = new Query\Select(User::class);
        $query->where('has_project = ?', true);

        return $this->selectCollection($query);
    }
    ```

-   `selectBatched`: With bigger result sets it might not be possible to use a
    single collection due to memory/performance constraints, but if you want to
    use the functionality of a collection to manipulate the list of entities, a
    batch provides a good middle ground. Using this helper will give back the
    result in smaller batches, each batch will have a subset of the result. These
    batches can be used just like a regular collection.

    ```php
    public function findAllUsers(): \Generator
    {
        $query = new Query\Select(User::class);

        return $this->selectBatched($query);
    }
    ```

-   `selectVirtualField`: The "multi-counterpart" of the `selectOneVirtualField`
    method to give back a single field for a list of results.

    ```php
    public function findUsernameInformation(): \Generator
    {
        $query = new Query\Select(User::class, 'u', [
            'total' => 'COUNT(*)',
        ]);
        $query->groupBy('username');

        return $this->selectVirtualField($query, 'total', 'int');
    }
    ```

-   `selectWithEntityProvider`: Provide a custom entity provider to use when
    selecting entities. With the introduction of the
    `VirtualArrayEntityProvider` it is now possible to select multiple arbitrary
    fields.

    ```php
    public function findUserNames(): \Generator
    {
        $query = new Query\Select(User::class, 'u', [
            'name' => 'u.name',
            'some_number' => 'u.some_number',
        ]);

        return $this->selectWithEntityProvider(
            $query,
            new VirtualArrayEntityProvider([
                'name' => [],
                'some_number' => [
                    'type' => 'int',
                ],
            ]),
        );
    }
    ```

### No result helpers

-   `query`: Execute a query without a result, useful for
    `INSERT`/`UPDATE`/`DELETE`/raw queries.

    ```php
    public function updateUsername(int $userId, string $username): void
    {
        $query = new Query\Update(User::class);
        $query->values([ 'username' => $username ]);
        $query->where('id = ?', $userId);

        $this->query($query);
    }
    ```
