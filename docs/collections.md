---
id: collections
title: Collections
slug: /collections
---

A collection is a list of entities, this in contrast with the `\Generator`
approach in some other parts of Access. You can use collections if you want to
operate on your list of entities as a whole and not the only the entities on
their own. If you just need to loop over a bunch of entities, it's advised to
stick with the generators, as they are a bit more performant (no need to keep
all entities in memory).

Collections can be created in different ways, but the most common way to get a
collection is through the [repository](repositories) of an entity. By default
the repository has a couple of methods to get a collection of entities, like the
`findByAsCollection` and `findAllCollection` methods.

Another way to create a collection is by doing it yourself and adding entities
yourself. `Access\Database::createCollection()` will return an empty collection
for you to use.`

## Helper methods

There are a bunch of helper methods defined to make handling collections a bit
more pleasant Also, the `Collection` class implements `\ArrayAccess`,
`\Countable` and `\IteratorAggregate` to blend in nicely into your other code.

-   `fromIterable`: Add a bunch of entities at once
-   `addEntity`: Add a entity to the collection
-   `isEmpty`: Is the collection empty?
-   `getIds`: Get all IDs of the entities inside the collection
-   `find`: Find the first matching entity with a callback
-   `first`: Returns the first entity
-   `merge`: Merge current collection with another one
-   `sort`: Sort the collection with `usort`
-   `map`: Map over all entities and return result
-   `filter`: Create a new collection only with accepted entities

## Relations

One big reason for using collections is if you want to fetch related entities
and want to prevent the `n+1` problem.

### Has one

To get all "has-one" entities you use the `findRefs` method.

```php
// get a list of users from somewhere
$users = $db->getRepository(User::class)->findAllCollection();

$profileImages = $users->findRefs(
    ProfileImage::class,

    // the ID of the profile image
    fn(User $user) => $user->getProfileImageId(),
);
```

> Executes the following SQL:
>
> ```sql
> SELECT * FROM profile_images WHERE id IN (1, 2, 3, ..)
> ```

This creates a new collection with all the profile images of those users.

The collection class implements the `\ArrayAccess` interface and returns
entities based on their ID. To continue the profile images example, you can do
the following to get the profile image of a specific user.

```php
// .. see above
$profileImages = ..;

$profileImage[$users->first()->getProfileImageId()]; // => ProfileImage entity
```

### Belongs to

Sometimes you want to access the relation the other why around.

```php
// get a list of profile images from somewhere
$profileImages = $db->getRepository(ProfileImage::class)->findAllCollection();

$users = $profileImages->findInversedRefs(
    User::class,

    // the field name where to look for profile image IDs
    'profile_image_id',
);
```

> Executes the following SQL:
>
> ```sql
> SELECT * FROM users WHERE profile_image_id IN (1, 2, 3, ..)
> ```
