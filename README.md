# Ulole-ORM `2.0`
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

class User {
    use ORMModel;

    public $id;
    public $name;
    public $password;
    public $description;
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

UloleORM::register(/*table-name*/ "user", /*Model class*/ User::class);

// Inserting into table
$user = new User;
$user->name = "Okay";
$user->save();

/*
    Fetching a single table entry
*/

$user = User::table()
    ->where("id", 2)
    ->get();

echo $user->name;

/*
    Fetching multible entries
*/
$users = User::table()
    ->like("description", "")
    ->all();

foreach ($users as $user) {
    echo $user->name;
}


/*
    Updating
*/
User::table()
    ->where("id", 2)
    ->update();

// Updating entry
$user = User::table()->where("id", "1")->get();
$user->name = "ninel";
$user->save();

/*
    Deleting
*/

User::table()
    ->where("id", 2)
    ->delete();

// Deleting entry
$user = User::table()->where("id", "1")->get();
$user->delete();
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
    ->all();
```
