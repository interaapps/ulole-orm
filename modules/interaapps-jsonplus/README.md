# JSONPlus `1.0`

## Getting started

### JSONPlus instance
```php
<?php
use de\interaapps\jsonplus\JSONPlus;
use de\interaapps\jsonplus\serializationadapter\impl\JsonSerializationAdapter;
use de\interaapps\jsonplus\serializationadapter\impl\phpjson\PHPJsonSerializationAdapter;

$jsonPlus = JSONPlus::createDefault();
$obj = $jsonPlus->fromJson('{"test": "hello world"}');
echo $obj->test; // -> hello world

// Enabling pretty printing
$jsonPlus->setPrettyPrinting(true);

echo $jsonPlus->toJson($obj); // -> {"obj": "hello world"}

/// Other drivers
// Default if json_decode exists in JSONPlus::createDefault()
$jsonPlus = new JSONPlus(new PHPJsonSerializationAdapter());
// Custom JSON implementation
$jsonPlus = new JSONPlus(new JsonSerializationAdapter());
```

### Model
```php
<?php
use de\interaapps\jsonplus\JSONPlus;
use de\interaapps\jsonplus\JSONModel;
use de\interaapps\jsonplus\attributes\Serialize;
use de\interaapps\jsonplus\attributes\ArrayType;

class Organisation {
    public string $name;
}

enum UserType {
    case ADMIN;
    case MEMBER;
}

class User {
    use JSONModel;
    
    public int $id;
    
    #[Serialize("first_name")]
    public string $firstName;
    
    #[Serialize(hidden: true)]
    public string $password;
    
    public ?Organisation $organisation;
    
    // Set Type for array:
    #[ArrayType(User::class)]
    public array $friends;
    
    public UserType $type = UserType::MEMBER;
}

$json = '{
    "id": 12343,
    "first_name": "Jeff",
    "organisation": {
        "name": "InteraApps"
    },
    "friends": [
        {
            "id": 3245,
            "first_name": "John",
            "friends": []
        }
    ],
    "type": "USER"
}';

// Decoding the JSON
$user = User::fromJson($json);

echo "Hello. My name is ".$user->first_name.", I have the ID ".$user->id
    ."and I'm in the organisation ".$user->organisation->name."\n";

foreach ($user->friends as $friend) {
    echo "One of my friends: ".$friend->name."\n";
}

// Encoding to JSON
echo $user->toJson();

// Decode typed JSON-array
$jsonPlus = JSONPlus::createDefault();
$users = $jsonPlus->fromMappedArrayJson('[...]', User::class);
foreach ($users as $user) {}
```
`Tip`: If you are using PHPStorm or any other intelligent IDE you might add PHP-Docs to some fields.

For Typed Arrays:
```php
/** 
* @var array<User> 
*/
private array $myArray;
```

## Installation
#### UPPM
```
uppm install interaapps/jsonplus
```
#### Composer
```
composer require interaapps/jsonplus
```