<?
require 'classes/Curl.php';
require 'classes/PDO.php';

$curl = new Curl();

// Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT token,block FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);
$token		= $set_bot['token']; // токен бота

$chat = trim($argv[1]);
$message = intval($argv[2]);

$name_cat = DB::$the->query("SELECT name FROM `sel_category` WHERE `id` = '".$message."' ");
$name_cat = $name_cat->fetch(PDO::FETCH_ASSOC);

// Проверяем наличие ключей
$total = DB::$the->query("SELECT id FROM `sel_keys` where `id_cat` = '".$message."' and `sale` = '0' and `block` = '0' ");
$total = $total->fetchAll();

if(count($total) == 0) // Если пусто, вызываем ошибку
{ 
DB::$the->prepare("UPDATE sel_users SET cat=? WHERE chat=? ")->execute(array("0", $chat)); 	
// Отправляем текст
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => '⛔ Данный товар закончился!',
	));
exit;	
}

DB::$the->prepare("UPDATE sel_users SET cat=? WHERE chat=? ")->execute(array($message, $chat));

$text .= "Вы выбрали: ".$name_cat['name']."\n\n";

$query = DB::$the->query("SELECT id,name,mesto FROM `sel_subcategory` WHERE `id_cat` = '".$message."' order by `mesto` ");
while($cat = $query->fetch()) {
	
$text .= "🔹 ".$cat['name']." (отправьте ".$cat['mesto'].")\n\n"; // ЭТО ВЫВОД ПОДКАТЕГОРИЙ

}

$text .= "\n".$set_bot['footer'];

// Отправляем все это пользователю
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $text,
	)); 	

exit;
?>