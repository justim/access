---
id: presenters
title: Presenters
slug: /presenters
---

Presenters are Access' way to serialize entities and collections to something
you can send over the wire with `json_encode`. To be more specific, it converts
entities to `array`s and because collections are basically lists of entities,
those get converted to `array`s of `array`s.

But why, you might ask, not just use `\JsonSerializable` for this? Presenters
add a layer inbetween the entity and the resulting array to allow for more
configuration and flexibility. For example, for different reasons you might want
to expose different fields of a single entity. And, by using the extra layer
Access can provide a lot of helper methods to convert your entities with ease,
including, but not limited to, solving the `n+1` problem for nested entities.

## Simple entity presenter

To start simples, you need to create a class that extends the `EntityPresenter`,
and implement two methods. One method to tell which entity is associated with
this presenter (`getEntityKlass`) and the other to do the converting
(`fromEntity`).

```php
use Access\Presenter\EntityPresenter;

class UserPresenter extends EntityPresenter
{
    /**
     * Get the entity class associated with this presenter
     */
    public static function getEntityKlass(): string
    {
        return User::class;
    }

    /**
     * Convert entity to an array
     */
    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => $user->getId(),
        ]
    }
}
```

To use this presenter you need an instance of [`Access\Database`](database) and
call `presentEntity`.

```php
// get a user from somewhere
$user = $db->findOne(User::class, 1);

$result = $db->presentEntity(UserPresenter::class, $user);

// =>
[
    'id' => 1,
];
```

As you might have noticed, the return type of the `fromEntity` method is
`?array`, indicating that it is possible to return `null`. Returning `null`
could help in cases where you don't want to output the entity, based on some
kind of logic. For example, you never want to return admin users from your
entity presenter, just return `null` when the user is an admin and the presenter
will completely filter out the user. This is more useful when you have a list of
users and want to filter some out.

## Collection presenters

Presenting a collection uses the same presenter as the entity, it just executes
the `fromEntity` multiple times and puts them in an `array`.

```php
// get a list of users from somewhere
$users = $db->getRepository(User::class)->findAllCollection();

$result = $db->presentCollection(UserPresenter::class, $users);

// =>
[
    [
        'id' => 1,
    ],
    [
        'id' => 2,
    ],
    // ..
];
```

## Nested presenters

To make things more interesting, we need create another presenter, this time for
the `Project` entity. This `ProjectPresenter` presenter references another
presenter by using `present()`, you need to provide the presenter you want to
use for your field and the ID of the entity. Calling the `present` method by
itself does not really do anything, it merely saves the needed information and
"marks" the location in the result. The presenter will then lookup all the
"markers", fetch the needed entities in a single query and resolve all the
markers with the result of entity presenter.

```php
use Access\Presenter\EntityPresenter;

class ProjectPresenter extends EntityPresenter
{
    public static function getEntityKlass(): string
    {
        return Project::class;
    }

    public function fromEntity(Entity $project): ?array
    {
        return [
            'id' => $project->getId(),
            'owner' => $this->present(UserPresenter::class, $project->getOwnerId()),
        ]
    }
}
```

Executing this entity presenter will automatically create a `UserPresenter`, fetch
the associated `User` entity with the ID coming from `getOwnerId`.

```php
// get a project from somewhere
$project = $db->findOne(Project::class, 1);

$result = $db->presentEntity(ProjectPresenter::class, $project);

// =>
[
    'id' => 1,
    'owner' => [
        'id' => 1,
    ],
];
```

The underlying presenter fetches the needed `User` entity based on the mark left
my `present`. In this case it is obvious that it's done with a single query,
but this scales to using this entity presenter for collections as well.

```php
// get a list of projects from somewhere
$projects = $db->getRepository(Project::class)->findAllCollection();

$result = $db->presentCollection(ProjectPresenter::class, $projects);

// =>
[
    [
        'id' => 1,
        'owner' => [
            'id' => 1,
        ],
    ],
    [
        'id' => 2,
        'owner' => [
            'id' => 2,
        ],
    ],
    // ..
];
```

This is also done with a single query; all the marks are collected, then the
entities are fetched in one go, and after that the markers are replaced with
the result of the `fromEntity` of the associated entity presenter. It is
possible that the result also contains a bunch of markers, like the profile
image of the owner, for example. The presenter will keep resolving the markers
for as long as there are any in the result. Adding a profile image from another
entity presenter would only increase the number of queries with one.

Of course `present` is a little bit limited, it only works for `has-one`
relations. The `EntityPresenter` provides a bunch more methods to resolve more
complicated relations as well.

### `has-many` relation

```php
use Access\Presenter\EntityPresenter;

class UserWithProjectsPresenter extends EntityPresenter
{
    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => $user->getId(),
            'projects' => $this->presentMultipleInversedRefs(
                UserPresenter::class, // entity presenter
                'owner_id', // field name linking the user to the projects
                $user->getId(), // value of the field
            ),
        ]
    }
}
```

In the background this will do a query to fetch all the projects needed to
resolve these markers, something like this:

```sql
SELECT * FROM `projects` WHERE `owner_id` IN (:userId)
```

When there are multiple markers that need to be resolved, the query stays the
same, but will include all user IDs that are needed. Then the result is split
when the markers are resolved.

This entity presenter can be used just like any other.

```php
// get a user from somewhere
$user = $db->findOne(User::class, 1);

$result = $db->presentEntity(UserWithProjectsPresenter::class, $user);

// =>
[
    'id' => 1,
    'projects' => [
        [
            'id' => 1,
            'owner' => [
                'id' => 1,
            ],
        ],
    ],
];
```

As you can see the `owner` field of the project is also filled, as that entity
presenter uses `UserPresenter`. This feature is very powerful, you can easily
create more complex entity presenters by composing them together. Keep in mind,
though, prevent circular entity presenters. This will throw an exception.

:::note Avoid circular entity presenters
This will completely blow up with an `Access\Exception`.
:::

You can prevent the circular entity presenters is by creating a completely new
one for every need, like we did in our example from above. We created a new
`UserWithProjectsPresenter` entity presenter to prevent the circular entity
presenter.

## Clauses

Even though it is possible to do some manipulation with the future helpers,
this can be cumbersome when you only want to sort them, or filter on an extra
field. That's were the optional clauses come into play. You can add an extra
clause to some of the helper methods to provide that extra functionality to the
marker. Currently there are two kinds of clauses that you can use, a condition
to filter on an extra field or ordering the results for the "multiple" helpers.

### Condition clauses

```php
use Access\Presenter\EntityPresenter;
use Access\Clause;

class UserWithPublishedProjectsPresenter extends EntityPresenter
{
    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => $user->getId(),
            'publishedProjects' => $this->presentMultipleInversedRefs(
                UserPresenter::class, // entity presenter
                'owner_id', // field name linking the user to the projects
                $user->getId(), // value of the field
                new Clause\Condition\Equals('status', 'PUBLISHED'),
            ),
        ]
    }
}
```

This will only add projects to the `'publishedProjects'` list that are published
(`status = 'PUBLISHED'`).

There are a whole bunch of conditions you can use and their names speak for
themselves.

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

### Ordering clauses

To order the projects you can use the `Access\Clause\OrderBy` clauses.

```php
use Access\Presenter\EntityPresenter;
use Access\Clause;

class UserWithOrderedProjectsPresenter extends EntityPresenter
{
    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => $user->getId(),
            'publishedProjects' => $this->presentMultipleInversedRefs(
                UserPresenter::class, // entity presenter
                'owner_id', // field name linking the user to the projects
                $user->getId(), // value of the field
                new Clause\OrderBy\Ascending('name'),
            ),
        ]
    }
}
```

There are only three order by clauses:

-   `Access\Clause\OrderBy\Ascending`
-   `Access\Clause\OrderBy\Descending`
-   `Access\Clause\OrderBy\Random`

### Multiple clauses

And if you want to mix multiple clauses together, if, for example, you want the
list to contain published projects and also want to order them. The special
`Access\Clauses\Multiple` clause will help you here, if combines multiple
conditions together and all conditions need to be true. Or if you provide
multiple order by clauses they all will be used (you can sorting on status and
then on name, for example). There is also the `Access\Clauses\MultipleOr` clause
if you want only one of the condition clauses to be true. You can add as many
clauses to the `Multiple` as you like, and you can mix order by and condition
clauses.

```php
use Access\Presenter\EntityPresenter;
use Access\Clause;

class UserWithOrderedPublishedProjectsPresenter extends EntityPresenter
{
    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => $user->getId(),
            'publishedProjects' => $this->presentMultipleInversedRefs(
                UserPresenter::class, // entity presenter
                'owner_id', // field name linking the user to the projects
                $user->getId(), // value of the field
                new Clause\Multiple(
                    new Clause\Condition\Equals('status', 'PUBLISHED'),
                    new Clause\OrderBy\Ascending('name'),
                ),
            ),
        ]
    }
}
```

More information about [clauses can be found here](clauses).

## `Presenter` instance

### Dependency injection

Sometimes you need a bit more information than just the entity you are
converting, like some service to generate URLs. The presenter behaves like a
rudimentary dependency container. First you need access to the `Presenter` that
is used for the actual presenting, so far we've only looked at the shortcuts to
skip creating a `Presenter`. To get a presenter you can just call
`createPresenter` on the [database](database) instance.

```php
// result an `Access\Presenter`
$presenter = $db->createPresenter();
```

This presenter instance provides the same methods to do the actual presenting as
the `Database` instance, `presentEntity` and `presentCollection`. But, there are
also some extra methods to prepare your presenting work for a bit more heavy
lifting.

First up, you can add a dependency to the presenter that your entity presenters
can use.

```php
// get a URL service somewhere
// for our exmple, this is an `App\UrlService` object
$urlService = $this->getSomeUrlService();

$presenter = $db->createPresenter();
$presenter->addDependency($urlService);
```

Now your presenter has a reference to the URL service, but on its own nothing
happens. We need a way for the entity presenters to access this dependency.
Dependencies are injected into the constructor of the entity presenter and are
matched on type.

```php
use Access\Presenter\EntityPresenter;

class UserWithExternalUrlPresenter extends EntityPresenter
{
    private App\UrlService $urlService;

    public function __construct(App\UrlService $urlService)
    {
        $this->urlService = $urlService;
    }

    // .. get presenter class

    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => 1,
            'externalUrl' => $this->urlService->generate($user),
        ];
    }
}
```

Dependencies are only injected into entity presenters with a constructor and
the order of dependencies does not matter. And your entity presenter can also
mark the dependency as optional with a default value for the constructor
argument. Otherwise the presenter will throw an exception when a dependency is
not available.

### Providing collections

In some cases you already know which entities are needed to resolve some
markers, when, for example, you already fetched those entities to does some
calculations. In this case you don't what to fetch them again when there is a
marker for one of those entities. You can add those entities as a collection to
the presenter for future use with `provideCollection`.

```php
// the some users from somewhere
$users = $db->getRepository(User::class)->findAllCollection();

// get a project from somewhere
$project = $db->findOne(Project::class, 1);

$presenter = $db->createPresenter();
$presenter->provideCollection(User::class, $users);

$result = $presenter->presentEntity(ProjectPresenter::class, $project);
```

This will _not_ do additional queries to fetch the user needed for the owner
marker in the `ProjectPresenter` entity presenter. Unless, of course, the
specific user is not available in the collection; in that case an addition
query will be executed.

## Custom markers

To have a bit of flexibility to lazily fetch other information than entities it
is also possible to use custom markers. Custom markers allow you to collect
information that you want to resolve in a single pass, like presenting
information about an entities coming from other source. For example:

```php
use Access\Presenter\CustomMarkerInterface;

class SomeMarker implements CustomMarkerInterface
{
    public function __construct(private mixed $someId)
    {
        // add some ID to a pool somewhere
    }

    public function fetch(): mixed
    {
        // do something with the information fetch based on the ID
    }
}
```

In your presenter you can just return an instance of `SomeMarker`, and in the
resolve pass of the presenter all custom markers will be fetched. A typical
setup would be to inject a `SomeMarker` provider that collects the IDs and will
do the needed calculations when the first fetch is done.
