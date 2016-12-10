<?
require 'classes/Curl.php';
require 'classes/PDO.php';

$curl = new Curl();


$json = file_get_contents('php://input'); // Получаем запрос от пользователя
$action = json_decode($json, true); // Расшифровываем JSON

// Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT * FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);

$message	= $action['message']['text']; // текст сообщения от пользователя
$chat		= $action['message']['chat']['id']; // ID чата
$username	= $action['message']['from']['username']; // username пользователя
$first_name	= $action['message']['from']['first_name']; // имя пользователя
$last_name	= $action['message']['from']['last_name']; // фамилия пользователя
$token		= $set_bot['token']; // токен бота



// Если бот отключен, прерываем все!
if($set_bot['on_off'] == "off") exit;

// Проверяем наличие пользователя в БД
$vsego = DB::$the->query("SELECT chat FROM `sel_users` WHERE `chat` = {$chat} ");
$vsego = $vsego->fetchAll();

// Если отсутствует, записываем его
if(count($vsego) == 0){ 

// Записываем в БД
$params = array('username' => $username, 'first_name' => $first_name, 'last_name' => $last_name, 
'chat' => $chat, 'time' => time() );  
 
$q = DB::$the->prepare("INSERT INTO `sel_users` (username, first_name, last_name, chat, time) 
VALUES (:username, :first_name, :last_name, :chat, :time)");  
$q->execute($params);	
}

// Получаем всю информацию о пользователе
$user = DB::$the->query("SELECT ban,cat FROM `sel_users` WHERE `chat` = {$chat} ");
$user = $user->fetch(PDO::FETCH_ASSOC);

// Если юзер забанен, отключаем для него все!
if($user['ban'] == "1") exit;

// Если сделан запрос оплата 
if ($message == "оплата" or $message == "Оплата") {
$chat = escapeshellarg($chat);	
exec('bash -c "exec nohup setsid wget -q -O - '.$set_bot['url'].'/verification.php?chat='.$chat.' > /dev/null 2>&1 &"');
exit;
}

if($user['cat'] == 0){		
// Проверяем наличие категории
$mesto_cat = DB::$the->query("SELECT mesto FROM `sel_category` WHERE `mesto` = '".$message."' ");
$mesto_cat = $mesto_cat->fetchAll();

if (count($mesto_cat) != 0) 
{
$chat = escapeshellarg($chat);	
$message = escapeshellarg($message);	
exec('bash -c "exec nohup setsid php ./select_cat.php '.$chat.' '.$message.' > /dev/null 2>&1 &"');
exit;
}
}

// Проверяем наличие товара
$mesto = DB::$the->query("SELECT mesto FROM `sel_subcategory` WHERE `mesto` = '".$message."' and `id_cat` = '".$user['cat']."' ");
$mesto = $mesto->fetchAll();

if (count($mesto) != 0) 
{	
$chat = escapeshellarg($chat);	
$message = escapeshellarg($message);	
exec('bash -c "exec nohup setsid php ./select.php '.$chat.' '.$message.' > /dev/null 2>&1 &"');
exit;
}


// Если проверяют список покупок
if ($message == "заказы" or $message == "Заказы") {	
$chat = escapeshellarg($chat);	
exec('bash -c "exec nohup setsid php ./orders.php '.$chat.' > /dev/null 2>&1 &"');
exit;
}

// Команда помощь
if ($message == "помощь" or $message == "Помощь") {	


$text = "СПИСОК КОМАНД

[Цифры] - используются для выбора товара

Оплата - для проверки оплаты

Заказы - список всех ваших заказов

0 и 00 - отмена заказа

Помощь - вызов списка команд
";

// Отправляем все это пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $text,
	)); 
exit;
}

if ($message == "0" or $message == "00") {	

DB::$the->prepare("UPDATE sel_users SET cat=? WHERE chat=? ")->execute(array("0", $chat)); 	

DB::$the->prepare("UPDATE sel_keys SET block=? WHERE block_user=? ")->execute(array("0", $chat)); 
DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE block_user=? ")->execute(array('0', $chat)); 
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE block_user=? ")->execute(array('0', $chat));  

DB::$the->prepare("UPDATE sel_users SET id_key=? WHERE chat=? ")->execute(array('0', $chat)); 
DB::$the->prepare("UPDATE sel_users SET pay_number=? WHERE chat=? ")->execute(array('pay_number', $chat)); 

$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => "🚫 Заказ отменен!",
	)); 
	
exit;
}

$text = $set_bot['hello']."\n\n";


$query = DB::$the->query("SELECT id,name,mesto FROM `sel_category` order by `mesto` ");
while($cat = $query->fetch()) {
	
$text .= "🔷 ".$cat['name']." (отправьте ".$cat['mesto'].")\n\n"; // ЭТО НАЗВАНИЕ КАТЕГОРИЙ

}

$text .= "\n".$set_bot['footer'];

// Отправляем все это пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $text,
	)); 

?>