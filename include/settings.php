<?php
$dir = str_replace('include', '', dirname(__FILE__));

include_once $dir."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once $dir."class/Database.class.php";
include_once $dir."class/rain.tpl.class.php";

// заполнение шаблона
raintpl::configure("root_dir", $dir );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;

$settings = Database::getAllSetting();
foreach ($settings as $row)
    foreach ($row as $key=>$val)
        $tpl->assign( $key, $val );

$notifiersList = array();

foreach (Database::getActivePluginsByType(Notifier::$type) as $plugin)
{
    $notifier = Notifier::Create($plugin['name'], $plugin['group']);
    if ($notifier == null)
        continue;

    $needSendUpdate = "";
    $needSendWarning = "";
    if ($notifier->SendUpdate() == TRUE)
        $needSendUpdate = 'checked';
    if ($notifier->SendWarning() == TRUE)
        $needSendWarning = 'checked';

    $notifiersList[] = array('notifier' => $notifier,
                             'needSendUpdate' => $needSendUpdate,
                             'needSendWarning' => $needSendWarning);
}

$tpl->assign( 'notifiersList', $notifiersList );
$tpl->assign( 'notifiers', Sys::getNotifiers() );

$tpl->draw( 'settings' );
?>