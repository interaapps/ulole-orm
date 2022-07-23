<?php
// Init

use de\interaapps\jsonplus\attributes\ArrayType;
use de\interaapps\jsonplus\attributes\Serialize;
use de\interaapps\jsonplus\JSONModel;
use de\interaapps\jsonplus\JSONPlus;
use de\interaapps\jsonplus\serializationadapter\impl\JsonSerializationAdapter;
use de\interaapps\jsonplus\serializationadapter\impl\phpjson\PHPJsonSerializationAdapter;

chdir(".");;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
(require_once './autoload.php')();

// Testing
echo "Testing:\n";

class Test2 {
    #[Serialize("shush")]
    public string $sheesh;
}



enum MyEnum {
    case HEY;
    case WORLD;
}

class Test {
    use JSONModel;
    #[Serialize("name_")]
    public string $name = "NOT INITIALIZED";
    public bool $test;
    public int $feef;
    public array $aeef;
    public object $aeef2;
    public Test2 $test2;
    public $aaaa;
    public $aa;

    public ?MyEnum $myEnum = null;

    public function __construct(){
    }

    public function setName(string $name): Test {
        $this->name = $name;
        return $this;
    }
}

const JSON = '{
    "name_":"Wo\"\nrld!\\\ /",
    "aeef23": {},
    "aeef2": {"test": true},
    "test": false,
    "feef": 21,
    "aeef": ["1","2","3"],
    "test2": {"shush": "yay"},
    "aaaa": null,
    "aa": false
}';

echo Test::fromJson(JSON)->toJson();
$json = new JSONPlus(new JsonSerializationAdapter());
$var = $json->fromJson(JSON, Test::class);
echo $var->myEnum;
echo $var->name."\n";
echo $json->toJson($var);


class Test3 {
    use JSONModel;

    /**
     * @var array<Test2>
     */
    #[ArrayType(Test2::class)]
    public array $myArray;
}
$arr = $json->fromMappedArrayJson('[
    {
        "shush": "yipi"
    },
    {
        "shush": "yipu"
    }
]', Test2::class);



foreach ($arr as $val) {
    echo $val->sheesh;
}