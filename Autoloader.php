<?php

class Autoloader {
   public static function loadclass($classname){
       $classpath =  __DIR__ . "\\" .  str_replace('Simplifying', 'src', $classname) . '.php';
       require_once $classpath;
   }

   public static function register(){
       spl_autoload_register(['Autoloader', 'loadclass']);
   }
}