<?php
$dir = dirname(__FILE__).'/' ;

session_start();

include_once $dir."class/System.class.php";
include_once $dir."class/rain.tpl.class.php";

// заполнение шаблона
raintpl::configure("root_dir", $dir );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;
$tpl->assign( "title"  , 'Мониторинг torrent трекеров' );
$tpl->draw( "index" );

?>
