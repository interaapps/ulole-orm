<?php
return function ($dir = ".", $mod = "main") {
    if (Phar::running())
        $dir = __DIR__."/".$dir;
    $uppmlock = (object) [];
    if (file_exists("uppm.locks.json")) {
        $uppmlock = json_decode(file_get_contents($dir . "/" . "uppm.locks.json"));
        if (isset($uppmlock->namespace_bindings))
            $uppmlock->namespaceBindings = $uppmlock->namespace_bindings;
    }
    $namespaceBindingsKeys = [];
    if (isset($uppmlock->namespaceBindings)) {
        $namespaceBindingsKeys = array_keys((array) $uppmlock->namespaceBindings);
        usort($namespaceBindingsKeys, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
    }
    $namespaceClassList = [];
    if (file_exists("$dir/modules/autoload_namespaces.php"))
        $namespaceClassList = @include_once("$dir/modules/autoload_namespaces.php");

    spl_autoload_register(function($class) use ($namespaceClassList, $namespaceBindingsKeys, $dir, $mod, $uppmlock) {
        if (isset($namespaceClassList[$class]))
            @include_once $dir."/".$namespaceClassList[$class];
        else if(file_exists("$dir/".str_replace("\\","/",$class).".php"))
            @include_once "$dir/".str_replace("\\","/",$class).".php";
        else if(file_exists("$dir/modules/".str_replace("\\","/",$class).".php"))
            @include_once "$dir/modules/".str_replace("\\","/",$class).".php";
        else if(file_exists("$dir/src/$mod/".str_replace("\\","/",$class).".php"))
            @include_once "$dir/src/$mod/".str_replace("\\","/",$class).".php";
        else if(file_exists("$dir/src/".str_replace("\\","/",$class).".php"))
            @include_once "$dir/src/".str_replace("\\","/",$class).".php";
        else if(isset($namespaceBindingsKeys)) {
            foreach ($namespaceBindingsKeys as $namespaceBinding) {
                $folder = $uppmlock->namespaceBindings->{$namespaceBinding};
                if (str_starts_with($class, $namespaceBinding)) {
                    $splitClass = explode($namespaceBinding."\\", $class, 2);
                    if (isset($splitClass[1]) && $splitClass[1] != "")
                        $class = $splitClass[1];
                    $classFile = $folder.'/'.str_replace("\\","/", $class).".php";
                    if (file_exists($dir."/".$classFile)) {
                        @include_once $dir."/".$classFile;
                        break;
                    }
                }
            }
        }
    });
    
    if (isset($uppmlock->initScripts))
        foreach ($uppmlock->initScripts as $script) {
            @include_once $script;
        }

};