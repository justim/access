---
id: cascade
title: Cascade
slug: /cascade
---

Cascading in Access is done by adding some information to the entity fields, or
the relations definition. It is currently only possible to cascade deletes,
either regular deletes or soft deletes.

For entity fields there is a new options: `'cascade'`, it contains an instance
of [`Cascade`] detailing how it should behave. The most common way to set this
up is by by using `Cascade::deleteSame()`, which will enable cascading as much
as possible. In combination with this setting, there is also the `'target'`
setting to tell which entity class is targeted by the relation.

When an entity is deleted the `'cascade'` setting for the fields will determine
what will happen to those related entities. There are two different start
scenarios:

1.  The entity is regularly deleted (`DELETE FROM ..`); related entities will
    then also be regularly deleted. Foreign keys would otherwise break.

2.  The entity is soft deleted (`UPDATE .. SET deleted_at = NOW()`); the
    foreign keys stay intact, related will also be soft deleted. Some entities
    don't support soft deleting and the `'cascade'` setting will determine if the
    entity is regularly deleted, or if nothing will happen.

It's also possible to for force a regular delete when a cascade is started with
a soft delete; useful if not all entities in the cascade chain are soft
deletable. The setting for `'cascade'` is: `Cascade::deleteForceRegular`.

## Examples

The most basic example with a user and an attached photo.

```php
class Photo extends Entity {
}

class Project extends Entity {
    public static function fields(): array {
        return [
            'profile_image_id' => [
                'type' => 'int',
                'target' => Photo::class,
                'cascade' => Cascade::deleteSame(),
            ],
        ];
    }
}
```

Creating a user with a photo as a profile image, and then deleting the user
will also remove the photo.

```php
$photo = new Photo();
$db->save($photo);

$user = new User();
$user->setProfileImage($photo);
$db->save($user);

// will delete the user and the photo
$db->delete($user);
```

It's slightly more complicated when the entity that gets deleted is not the
"holder" of the relation; it does not have the field that creates the relation.
Some extra information is needed to tell Access what else needs to be deleted,
this is done with the `relations` method on the `Entity` class that can be
overridden to define its relations outside its fields.

```php
class Project extends Entity {
}

class User extends Entity {
    public static function relations(): array {
        return [
            'projects' => [
                'field' => 'user_id',
                'target' => Project::class,
                'cascade' => Cascade::deleteSame(),
            ],
        ];
    }
}
```

When deleting a user, all of its projects should also be deleted.

```php
$user = new User();
$db->save($user);

$project = new Project();
$project->setUser($user);
$db->save($project);

// will delete the user and all its projects
$db->delete($user);
```

## Cascade chains

When a related entity also has relations that should be deleted, then it is
done recursively for all entities until there is nothing that needs deleting
anymore.
