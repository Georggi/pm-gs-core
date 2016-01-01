<?php
$string = "%Test.i.i%xD";
preg_match("/\%(\w\.|\w)+/", $string, $m1);
var_export($m1);
echo "\n\n";
preg_match_all("/\%(\w\.|\w)+/", $string, $m2);
var_export($m2);
echo "\n\n";
preg_replace_callback("/\%(\w\.|\w)+/", function(array $matches){
    if(strpos(".", $matches[0]) === false){
        echo $matches[0] . " 1\n";
        #var_export($matches[0] . " 2");
    }
}, $string);