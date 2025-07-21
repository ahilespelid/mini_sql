<?php 
// Установка времени жизни сессии (2 часа = 7200 секунд)
session_start();
$session_lifetime = 7200;
// Поиск существующего файла конфигурации
$config_files = [__DIR__ . '/config.php', __DIR__ . '/admin/config.php'];
$config_file  = null;
foreach($config_files as $file){if(file_exists($file) && is_readable($file)){$config_file = $file; break;}}
if(!$config_file){die('Файл config.php или admin/config.php не найден или недоступен.');}

// Парсинг config.php
$config_content = file_get_contents($config_file);
$db_params = [];
// Гибкие регулярные выражения для извлечения параметров
preg_match("/define\s*\(\s*['\"]DB_DRIVER['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);/",    $config_content, $match_driver);
preg_match("/define\s*\(\s*['\"]DB_HOSTNAME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);/",  $config_content, $match_host);
preg_match("/define\s*\(\s*['\"]DB_USERNAME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);/",  $config_content, $match_user);
preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)?['\"]\s*\);/", $config_content, $match_password);
preg_match("/define\s*\(\s*['\"]DB_DATABASE['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);/",  $config_content, $match_database);
preg_match("/define\s*\(\s*['\"]DB_PORT['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);/",      $config_content, $match_port);
preg_match("/define\s*\(\s*['\"]DB_PREFIX['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);/",    $config_content, $match_prefix);
$db_params['driver']   = $match_driver[1] ?? 'mysqli';
$db_params['host']     = $match_host[1] ?? 'localhost';
$db_params['user']     = $match_user[1] ?? '';
$db_params['password'] = $match_password[1] ?? '';
$db_params['database'] = $match_database[1] ?? '';
$db_params['port']     = $match_port[1] ?? '3306';
$db_params['prefix']   = $match_prefix[1] ?? 'oc_';
// Проверка, что обязательные параметры найдены
if(empty($db_params['user']) || empty($db_params['database'])){die('Не удалось извлечь параметры подключения. Проверьте config_parse_error.txt.');}

// Проверка, авторизован ли пользователь
$authorized = false;
if(isset($_SESSION['auth_time']) && (time() - $_SESSION['auth_time'] < $session_lifetime)){$authorized = true;}
// Обработка формы пароля
if(isset($_POST['password']) && $_POST['password'] === 'secret123'){$_SESSION['auth_time'] = time(); $authorized = true;}
// Если пользователь не авторизован, показываем форму ввода пароля
if(!$authorized){echo '<form method="post"><input type="password" name="password" placeholder="Введите пароль" required><br><input type="submit" value="Авторизоваться"></form>';exit;}

// Подключение к базе данных
$mysqli = new mysqli($db_params['host'], $db_params['user'], $db_params['password'], $db_params['database'], $db_params['port']);
if($mysqli->connect_error){die('Ошибка подключения. Проверьте db_error.txt.');}
$default_sql = $_POST['sql_query'] ?? 'SELECT * FROM `istazd2s_new`.oc_seo_url;';

// Форма для SQL-запроса
echo '<form method="post"><textarea name="sql_query" placeholder="Введите SQL-запрос" rows="4" cols="50">'.$default_sql.'</textarea><br><input type="submit" value="Выполнить запрос"></form>';

// Выполнение SQL-запроса    
if (isset($_POST['sql_query'])) {
    $query = $_POST['sql_query'];
    $result = $mysqli->query($query);
    // Вывод результата
    if(is_bool($result)){
        echo ($result) ? 'Запрос выполнен успешно. Затронуто строк: ' . $mysqli->affected_rows : 'Ошибка запроса: '.$mysqli->error;
    }else{while($row = $result->fetch_assoc()){$rows[] = $row;} echo '<pre>'; print_r($rows); echo '</pre>';}
}
// Закрытие соединения
$mysqli->close();
?>
