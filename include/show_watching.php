<?php
$dir = str_replace('include', '', dirname(__FILE__));

include_once $dir."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once $dir."class/Database.class.php";
include_once $dir."class/Trackers.class.php";
include_once $dir."class/rain.tpl.class.php";

$contents = array();

// заполнение шаблона
raintpl::configure("root_dir", $dir );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$users = Database::getUserToWatch();
if ( ! empty($users))
{
    foreach ($users as $user){
        $thremes = Database::getThremesFromBuffer($user['id']);
        
        foreach ($thremes as $key=>$threme) {
            $thremes[$key]['url'] = Trackers::generateURL($user['tracker'], $threme['threme_id']);
        }
        
        $tpl = new RainTPL;
        $tpl->assign( "user", $user );
        $tpl->assign( "thremes", $thremes );
        
        $contents[] = $tpl->draw( 'show_watching_user', true );
    }
}
else
{
    $contents[] = "Нет пользователей для мониторинга.";
}

$tpl = new RainTPL;
$tpl->assign( "contents", $contents );

$tpl->draw( 'show_watching' );