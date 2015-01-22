<?php

spl_autoload_register(function ($class_name) {
    require_once CLASSES . '/' . str_replace('\\', '/', $class_name) . '.php';
});