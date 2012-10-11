<?php
spl_autoload_register(function($c){
    $cwd = getcwd();
    $p = str_replace('\\','/',$c);
    foreach(array('/lib/','/vendor/') as $q){
        if(is_file($f=($cwd.$q.$p.'.php'))){
            require($f);
            return true;
        }
    }
    return false;
},true,false);

set_error_handler(function($n,$s,$f,$l){throw new \ErrorException($s,0,$n,$f,$l);});
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
if(extension_loaded('mbstring')){
    if('neutral' == mb_language()) mb_language('Japanese');
    mb_internal_encoding('UTF-8');
}
ini_set('display_errors','On');
ini_set('html_errors','Off');
