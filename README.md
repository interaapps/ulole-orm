# Ulole-ORM `3.1`

## Getting started
UloleORM is an Object Relation Mapper written in PHP.
#### Uppm
```
uppm install uloleorm
```
#### Composer
```
composer require interaapps/uloleorm
```
### User.php
```php
<?php
use de\interaapps\ulole\orm\ORMModel;
use de\interaapps\ulole\orm\attributes\Column;
use de\interaapps\ulole\orm\attributes\Table;

#[Table("users")]
class User {
    use ORMModel;

    #[Column]
    public int $id;
    
    #[Column]
    public ?string $name;
    
    #[Column(name: 'mail')]
    public ?string $eMail;
    
    #[Column]
    public ?string $password;
    
    #[Column]
    public ?string $description;
}
```
### example.php
```php
<?php
UloleORM::database("main", new Database(
    'username', 
    'password', 
    'database', 
    'host',  /* PORT: default localhost */
    3306, /* PORT: default 3306 */
    'mysql' /* DRIVER: default mysql (Every PDO Driver usable. ) */
));

UloleORM::register(User::class);

// Auto migrates all tables (You might not do this every time the user opens the page. Move it into a cli-command or something like this)
UloleORM::autoMigrate();

// Inserting into table
$user = new User;
$user->name = "Okay";
$user->save();

// Fetching a single table entry
$user = User::table()
    ->where("id", 2)
    ->first();

echo $user->name;

// Fetching multiple entries
$users = User::table()
    ->like("description", "I am%")
    ->get();

foreach ($users as $user) {
    echo $user->name;
}

// Updating
User::table()
    ->where("id", 2)
    ->update();

// Updating entry
$user = User::table()->where("id", "1")->first();
$user->name = "ninel";
$user->save();

// Deleting
User::table()
    ->where("id", 2)
    ->delete();

// Deleting entry
$user = User::table()->where("id", "1")->first();
$user->delete();

// Where
User::table()->where("name", "John")->get();
User::table()->whereRaw("`name`", "=", "?", ["John"])->get();
User::table()->in("country", ["Germany", "France"])->get();
User::table()->notIn("country", ["Germany", "France"])->get();

User::table()->isNull("country")->get();
User::table()->notNull("country")->get();

User::table()->whereDay("createdAt", "23")->get();
User::table()->whereMonth("createdAt", "5")->get();
User::table()->whereYear("createdAt", "2022")->get();
User::table()->whereDate("createdAt", "23-5-2022")->get();
User::table()->whereTime("createdAt", "16:22:43")->get();

// Where Exists
$postsOfAUser = UserPost::table()
    ->whereExists(User::class, fn(Query $q) => $q->whereColumns(UserPost::class, "userId", "=", User::class, "id"))
    ->all()
    
// Helpful
User::table()->count();
User::table()->sum("balance");
User::table()->sub("balance");
User::table()->avg("balance");
User::table()->max("balance");
User::table()->min("balance");

```

## Selection
```php
<?php
User::table()
    // Simple where. Operator: '='
    ->where("name", "Guenter")
    // Where with own opertator. It's also an 'AND' one because we already used where once
    ->where("name", "=", "Guenter")

    ->like("name", "Guent%")

    ->and(function($query){
        $query->where("id", "1");
    })

    ->or(function($query){
        $query->where("id", "1");
    })

    // PHP 7.4+
    ->or(fn ($query) => $query->where("id", "1") )

    // Nesting
    ->or(function($query){
        $query->or(function($query){
            $query->and(function($query){
                $query->or(function($query){
                    $query->where("name", "lol");
                })
            })
        })
    })

    // Orders by id in a descending order
    ->orderBy("id", true)
    // Limit
    ->limit(10)
    // Offset (requires a limit to be set)
    ->offset(0)
    ->get();


User::table()->each(function(User $entry){
    echo $entry->name."\n";
});

```

## Migration
```php
(new Migrator("main"))
    ->setLogging(true)
    ->fromFolder("resources/migrations")
    ->up();

(new Migrator("main"))
    ->setLogging(true)
    ->fromFolder("resources/migrations")
    ->down(/*default val: 1*/);
```

#### resources/migrations/migration_22_0_11_create_users.php
```php
<?php
namespace testinglocal\migrations;

use de\interaapps\ulole\orm\Database;
use de\interaapps\ulole\orm\migration\Blueprint;
use de\interaapps\ulole\orm\migration\Migration;

/**
 * CHANGED: 
 */
class migration_22_0_11_create_users implements Migration {
    public function up(Database $database) {
        return $database->create("users", function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string("name");
            $blueprint->string("password");
            $blueprint->string("description");
            $blueprint->enum("gender", ["FEMALE", "MALE", "OTHER", "DO_NOT_ANSWER"])->default('DO_NOT_ANSWER');
            $blueprint->timestamp("created")->currentTimestamp();
        });
    }

    public function down(Database $database) {
        return $database->drop("users");
    }
}
```

#### resources/migrations/migration_22_0_13_edit_users.php
```php
<?php
namespace testinglocal\migrations;

use de\interaapps\ulole\orm\Database;
use de\interaapps\ulole\orm\migration\Blueprint;
use de\interaapps\ulole\orm\migration\Migration;

/**
 * CHANGED: 
 */
class migration_22_0_13_edit_users implements Migration {
    public function up(Database $database) {
        return $database->edit("users", function (Blueprint $blueprint) {
            $blueprint->string("name")->default("Johnson");
            $blueprint->string("mail");
        });
    }

    public function down(Database $database) {
        return $database->edit("users", function(Blueprint $blueprint){
            $blueprint->string("name")->nullable()->default(null);
            $blueprint->string("mail")->drop();
        });
    }
}
```

## Auto-Migrate
```php
// Automatically migrates all columns by its class structure and attributes
UloleORM::autoMigrate();

// Or for a specific database:
UloleORM::getDatabase("main")->autoMigrate();
```
