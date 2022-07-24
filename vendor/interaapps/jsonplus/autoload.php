<?php
return function ($dir = ".", $mod = "main") {
    $uppmlock = (object) [];
    if (file_exists("uppm.locks.json")) {
        $uppmlock = json_decode(file_get_contents($dir . "/" . "uppm.locks.json"));
        if (isset($uppmlock->namespace_bindings))
            $uppmlock->namespaceBindings = $uppmlock->namespace_bindings;
        if (isset($uppmlock->directnamespaces))
            $uppmlock->directNamespaceBindings = $uppmlock->directnamespaces;
    }

    spl_autoload_register(function($class) use ($dir, $mod, $uppmlock) {
        if (isset($uppmlock->directNamespaceBindings->{$class}))
            @include_once "$dir/".str_replace("\\","/",$uppmlock->directNamespaceBindings->{$class});
        else if(file_exists("$dir/".str_replace("\\","/",$class).".php"))
            @include_once "$dir/".str_replace("\\","/",$class).".php";
        else if(file_exists("$dir/modules/".str_replace("\\","/",$class).".php"))
            @include_once "$dir/modules/".str_replace("\\","/",$class).".php";
        else if(file_exists("$dir/src/".str_replace("\\","/",$class).".php"))
            @include_once "$dir/src/".str_replace("\\","/",$class).".php";
        else if(file_exists("$dir/src/$mod/".str_replace("\\","/",$class).".php"))
            @include_once "$dir/src/$mod/".str_replace("\\","/",$class).".php";
        else if(isset($uppmlock->namespaceBindings)) {
            foreach ($uppmlock->namespaceBindings as $namespaceBinding => $folder){
                if (substr($class, 0, strlen($namespaceBinding)) === $namespaceBinding) {
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

    if (isset($uppmlock->initscripts))
        foreach ($uppmlock->initscripts as $script) {
            @include_once $script;
        }

};