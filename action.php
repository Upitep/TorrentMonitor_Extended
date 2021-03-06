<?php
$dir = dirname(__FILE__).'/';
include_once $dir.'config.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Database.class.php';
include_once $dir.'class/Notifier.class.php';
include_once $dir.'class/Errors.class.php';
include_once $dir.'class/Trackers.class.php';
include_once $dir.'class/Update.class.php';
include_once $dir."class/rain.tpl.class.php";

if (isset($_POST['action']))
{
    //Проверяем пароль
    if ($_POST['action'] == 'enter')
    {
        $password = md5($_POST['password']);
        $count = Database::countCredentials($password);
        
        if ($count == 1)
        {
            session_start();
            $_SESSION['TM'] = $password;
            $return['error'] = FALSE;
            $return['msg'] = 'Вход выполнен успешно.';
            if (isset($_POST['remember']) && $_POST['remember'])
                setcookie('hash_pass', $password, time()+3600*24*31);
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Неверный пароль!';
        }
        echo json_encode($return);
    }
    
    //Добавляем тему для мониторинга
    if ($_POST['action'] == 'torrent_add')
    {
        if ($url = parse_url($_POST['url']))
        {
            $tracker = Trackers::getTrackerName( preg_replace('/www\./', '', $url['host']) );
            $threme  = Trackers::getThreme($tracker, $_POST['url']);
            
            if (is_array(Database::getCredentials($tracker)))
            {
                if (Trackers::moduleExist($tracker))
                {
                    if (Trackers::checkRule($tracker, $threme))
                    {
                        if (Database::checkThremExist($tracker, $threme))
                        {
                            if ( ! empty($_POST['name']))
                                $name = $_POST['name'];
                            else
                                $name = Sys::getHeader($_POST['url']);
                            
                            Database::setThreme($tracker, $name, $_POST['path'], $threme);
                            
                            echo 'Тема добавлена для мониторинга.';
                        }
                        else
                        {
                            echo 'Вы уже следите за данной темой на трекере <b>'.$tracker.'</b>.';
                        }
                    }
                    else
                    {
                        echo 'Не верная ссылка.';
                    }
                }
                else
                {
                    echo 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
                }
            }
            else
            {
                echo 'Вы не можете следить за этим сериалом на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
            }
        }
        else
        {
            echo 'Не верная ссылка.';
        }
        return TRUE;
    }
    
    //Добавляем сериал для мониторинга
    if ($_POST['action'] == 'serial_add')
    {
        $tracker = $_POST['tracker'];
        if (is_array(Database::getCredentials($tracker)))
        {
            if (Trackers::moduleExist($tracker))
            {
                if (Trackers::checkRule($tracker, $_POST['name']))
                {
                    if (Database::checkSerialExist($tracker, $_POST['name'], $_POST['hd'])) 
                    {
                        Database::setSerial($tracker, $_POST['name'], $_POST['path'], $_POST['hd']);
                        
                        echo 'Сериал добавлен для мониторинга.';
                    }
                    else
                    {
                        echo 'Вы уже следите за данным сериалом на этом трекере - <b>'.$tracker.'</b>.';
                    }
                }
                else
                {
                    echo 'Название содержит недопустимые символы.';
                }
            }
            else
            {
                echo 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
            }
        }
        else
        {
            echo 'Вы не можете следить за этим сериалом на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
        }
        return TRUE;
    }
    
    //Обновляем отслеживаемый item
    if ($_POST['action'] == 'update')
    {
        $tracker = $_POST['tracker'];
        $reset   = ($_POST['reset'] == 'true') ? 1 : 0;
        
        $trackerType = Trackers::getTrackerType($tracker);
        
        if ($trackerType == 'series')
        {
            if (Trackers::checkRule($tracker ,$_POST['name']))    
            {
                Database::updateSerial($_POST['id'], $_POST['name'], $_POST['path'], $_POST['hd'], $reset);
                
                echo 'Сериал обновлён.';
            }
            else
            {
                echo 'Название содержит недопустимые символы.';
            }
        }
        else if ($trackerType == 'threme')
        {
            $url = parse_url($_POST['url']);
            $tracker = Trackers::getTrackerName( preg_replace('/www\./', '', $url['host']) );
            $threme  = Trackers::getThreme($tracker, $_POST['url']);
            
            $update = ($_POST['update'] == 'true') ? 1 : 0;
            
            if (Trackers::checkRule($tracker, $threme))
            {
                Database::updateThreme($_POST['id'], $_POST['name'], $_POST['path'], $threme, $update, $reset);
                
                echo 'Тема обновлена.';
            }
            else
            {
                echo 'Название содержит недопустимые символы.';
            }
        }
    }
    
    //Добавляем пользователя для мониторинга
    if ($_POST['action'] == 'user_add')
    {
        $tracker = $_POST['tracker'];
        if (is_array(Database::getCredentials($tracker)))
        {
            if (Trackers::moduleExist($tracker))
            {
                if (Database::checkUserExist($tracker, $_POST['name'])) 
                {
                    Database::setUser($tracker, $_POST['name']);
                    
                    echo 'Пользователь добавлен для мониторинга.';
                }
                else
                {
                    echo 'Вы уже следите за данным пользователем на этом трекере - <b>'.$tracker.'</b>.';
                }
            }
            else
            {
                echo 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
            }
        }
        else
        {
            echo 'Вы не можете следить за этим пользователем на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
        }
        return TRUE;
    }
    
    //Удаляем пользователя из мониторинга и все его темы
    if ($_POST['action'] == 'delete_user')
    {
        Database::deletUser($_POST['user_id']);
        
        echo 'Удаляю...';
        
        return TRUE;
    }
    
    //Удаляем тему из буфера
    if ($_POST['action'] == 'delete_from_buffer')
    {
        Database::deleteFromBuffer($_POST['id']);
        
        echo 'Удаляю...';
        
        return TRUE;
    }
    
    //Очищаем весь список тем
    if ($_POST['action'] == 'threme_clear')
    {
        $array = Database::selectAllFromBuffer();
        for($i=0; $i<count($array); $i++)
        {
            Database::deleteFromBuffer($array[$i]['id']);
        }
        return TRUE;
    }
    
    //Перемещаем тему из буфера в мониторинг постоянный
    if ($_POST['action'] == 'transfer_from_buffer')
    {
        Database::transferFromBuffer($_POST['id']);
        
        echo 'Переношу...';
        
        return TRUE;
    }
    
    //Помечаем тему для скачивания
    if ($_POST['action'] == 'threme_add')
    {
        $update = Database::updateThremesToDownload($_POST['id']);
        if ($update)
        {
            $return['error'] = FALSE;
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Пометить тему для закачки.';
        }
        echo json_encode($return);
    }
    
    //Удаляем мониторинг
    if ($_POST['action'] == 'del')
    {
        Database::deletItem($_POST['id']);
        
        echo 'Удаляю...';
        
        return TRUE;
    }
    
    //Обновляем личные данные
    if ($_POST['action'] == 'update_credentials')
    {
        if ( ! isset($_POST['passkey']))
            $_POST['passkey'] = '';
        Database::setCredentials($_POST['id'], $_POST['log'], $_POST['pass'], $_POST['passkey']);
        
        echo 'Данные для трекера обновлены!';
        
        return TRUE;
    }
    
    //Обновляем настройки
    if ($_POST['action'] == 'update_settings')
    {
        Database::updateSettings('serverAddress', Sys::checkPath($_POST['serverAddress']));
        Database::updateSettings('auth', Sys::strBoolToInt($_POST['auth']));
        Database::updateSettings('proxy', Sys::strBoolToInt($_POST['proxy']));
        Database::updateSettings('autoProxy', Sys::strBoolToInt($_POST['autoProxy']));
        Database::updateSettings('proxyAddress', $_POST['proxyAddress']);
        Database::updateSettings('useTorrent', Sys::strBoolToInt($_POST['torrent']));
        Database::updateSettings('torrentClient', $_POST['torrentClient']);
        Database::updateSettings('torrentAddress', $_POST['torrentAddress']);
        Database::updateSettings('torrentLogin', $_POST['torrentLogin']);
        Database::updateSettings('torrentPassword', $_POST['torrentPassword']);
        Database::updateSettings('pathToDownload', Sys::checkPath($_POST['pathToDownload']));
        Database::updateSettings('deleteDistribution', Sys::strBoolToInt($_POST['deleteDistribution']));
        Database::updateSettings('deleteOldFiles', Sys::strBoolToInt($_POST['deleteOldFiles']));
        Database::updateSettings('rss', Sys::strBoolToInt($_POST['rss']));
        Database::updateSettings('debug', Sys::strBoolToInt($_POST['debug']));
        
        echo 'Настройки монитора обновлены.';
        
        return TRUE;
    }
    
    if ($_POST['action'] == 'updateNotifierSettings')
    {
        $notifiersSettings = json_decode($_POST['settings'], true);
        foreach ($notifiersSettings as $key => $settings)
        {
            $notifier = Notifier::Create($settings['notifier'], $settings['group']);
            if ($notifier != NULL)
                $notifier->SetParams($settings['address'], $settings['sendUpdate'], $settings['sendWarning']);
            $notifier = NULL;
        }
        echo "Настройки уведомлений обновлены.";
        return TRUE;
    }
    
    //Меняем пароль
    if ($_POST['action'] == 'change_pass')
    {
        $pass = md5($_POST['pass']);
        $q = Database::updateCredentials($pass);
        if ($q)
        {
            $return['error'] = FALSE;
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Не удалось сменить пароль!';
        }
        echo json_encode($return);
    }
    
    //Добавляем тему на закачку
    if ($_POST['action'] == 'download_thremes')
    {
        if ( ! empty($_POST['checkbox']))
        {
            $arr = $_POST['checkbox'];
            foreach ($arr as $id => $val)
            {
                Database::updateDownloadThreme($id);
            }
            echo count($arr).' тем помечено для закачки.';
            return TRUE;
        }
        Database::updateDownloadThremeNew();
    }
    
    //Помечаем новость как прочитанную
    if ($_POST['action'] == 'markNews')
    {
        Database::markNews($_POST['id']);
        return TRUE;
    }
    
    //Выполняем обновление системы
    if ($_POST['action'] == 'system_update')
    {
        Update::runUpdate();
        return TRUE;
    }
    
    // Получаем список доступных нотификаторов
    if ($_POST['action'] == 'getNotifierList')
    {
        echo json_encode(Sys::getNotifiers());
    }

    if ($_POST['action'] == 'removeNotifierSettings')
    {
        $notifier = Notifier::Create($_POST['notifierClass'], $_POST['group']);
        if ($notifier != NULL)
            Database::removePluginSettings($notifier);
        $notifier = NULL;
    }

    //Возвращаем содержимое страницы index в зависимости от состояния авторизации
    if ($_POST['action'] == 'getIndexPage')
    {
        $result = array();

        // заполнение шаблона
        raintpl::configure("root_dir", $dir );
        raintpl::configure("tpl_dir" , Sys::getTemplateDir() );
         
        if (Sys::checkAuth())
        {
            $errors = Database::getWarningsCount();
            
            $count = 0;
            if ( ! empty($errors))
                for ($i=0; $i<count($errors); $i++)
                    $count += $errors[$i]['count'];
            
            $tpl = new RainTPL;
            $tpl->assign( "update"     , Sys::checkUpdate() );
            $tpl->assign( "version"    , Sys::version() );
            $tpl->assign( "error_count", $count );
        
            $result['content'] = $tpl->draw( 'index_main', true );
            $result['type'] = 'main';
        }
        else
        {
            $tpl = new RainTPL;
            $result['content'] = $tpl->draw( 'index_auth', true );
            $result['type'] = 'auth';
        }
        
        echo json_encode($result);
    }
}

if (isset($_GET['action']))
{
    //Сортировка вывода торрентов
    if ($_GET['action'] == 'order')
    {
        session_start();
        if ($_GET['order'] == 'date')
            setcookie('order', 'date', time()+3600*24*365);
        elseif ($_GET['order'] == 'dateDesc')
            setcookie('order', 'dateDesc', time()+3600*24*365);
        elseif ($_GET['order'] == 'name')
            setcookie('order', '', time()+3600*24*365);
        header('Location: index.php');
    }
}
?>