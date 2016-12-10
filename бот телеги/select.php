<?
require 'classes/Curl.php';
require 'classes/PDO.php';

$curl = new Curl();

// Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT token,block FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);
$token		= $set_bot['token']; // токен бота

$chat = $argv[1];
$message = intval($argv[2]);

$user = DB::$the->query("SELECT ban,id_key,cat FROM `sel_users` WHERE `chat` = {$chat} ");
$user = $user->fetch(PDO::FETCH_ASSOC);




$nulled = DB::$the->query("SELECT id FROM `sel_keys` where `sale` = '0' and `block` = '1' and `block_time` < '".(time()-(60*$set_bot['block']))."' ");
$nulled = $nulled->fetchAll();

if(count($nulled > 0)){


$query = DB::$the->query("SELECT block_user FROM `sel_keys` where `sale` = '0' and `block` = '1' and `block_time` < '".(time()-(60*$set_bot['block']))."' order by `id` ");
while($us = $query->fetch()) {
	
DB::$the->prepare("UPDATE sel_keys SET block=? WHERE block_user=? ")->execute(array("0", $us['block_user'])); 
DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE block_user=? ")->execute(array('0', $us['block_user'])); 
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE block_user=? ")->execute(array('0', $us['block_user']));  

DB::$the->prepare("UPDATE sel_users SET id_key=? WHERE chat=? ")->execute(array('0', $us['block_user'])); 
DB::$the->prepare("UPDATE sel_users SET pay_number=? WHERE chat=? ")->execute(array('0', $us['block_user'])); 

$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $us['block_user'],
	'text' => "🚫 Вы не произвели оплату в течение {$set_bot['block']} минут. Этот ключ выставлен на продажу. 
Для того чтобы купить ключ, выберите товар заново",
	
	)); 
}
}	
	


// Берем информацию о разделе
$row = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `mesto` = '".$message."' and `id_cat` = '".$user['cat']."' ");
$subcat = $row->fetch(PDO::FETCH_ASSOC);

// Берем информацию о категории
$row = DB::$the->query("SELECT name FROM `sel_category` WHERE `id` = '".$subcat['id_cat']."' ");
$cat = $row->fetch(PDO::FETCH_ASSOC);

// Проверяем наличие ключей
$total = DB::$the->query("SELECT id FROM `sel_keys` where `id_subcat` = '".$subcat['id']."' and `sale` = '0' and `block` = '0' ");
$total = $total->fetchAll();

if(count($total) == 0) // Если пусто, вызываем ошибку
{ 

// Отправляем текст
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => '⛔ Данный товар закончился!',
	));
}
else // Иначе выводим результат
{

$clear = DB::$the->query("SELECT block_user FROM `sel_keys` where `block_user` = '".$chat."' ");
$clear = $clear->fetchAll();

if(count($clear) != 0){
DB::$the->prepare("UPDATE sel_keys SET block=? WHERE block_user=? ")->execute(array("0", $chat)); 
DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE block_user=? ")->execute(array('0', $chat));
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE block_user=? ")->execute(array('0', $chat));  
}

// Получаем информацию о ключе 
$key = DB::$the->query("SELECT id,code,id_subcat FROM `sel_keys` where `id_subcat` = '".$subcat['id']."' and `sale` = '0' and `block` = '0' order by rand() limit 1");
$key = $key->fetch(PDO::FETCH_ASSOC);


DB::$the->prepare("UPDATE sel_keys SET block=? WHERE id=? ")->execute(array("1", $key['id'])); 
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE id=? ")->execute(array($chat, $key['id'])); 
DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE id=? ")->execute(array(time(), $key['id'])); 

DB::$the->prepare("UPDATE sel_users SET id_key=? WHERE chat=? ")->execute(array($key['id'], $chat)); 
	
$set_qiwi = DB::$the->query("SELECT number FROM `sel_set_qiwi` WHERE `active` = '1' ");
$set_qiwi = $set_qiwi->fetch(PDO::FETCH_ASSOC);	
	
DB::$the->prepare("UPDATE sel_users SET pay_number=? WHERE chat=? ")->execute(array($set_qiwi['number'], $chat)); 
	
$text = "Вы выбрали:
🔷 {$cat['name']}
🔹 {$subcat['name']}

Переведите {$subcat['amount']} руб на Qiwi +{$set_qiwi['number']}

С комментарием: ".$key['id']."

После того как вы переведете эту сумму с этим комментарием, отправьте боту сообщение: оплата


Для отмены заказа: 0
";


// Отправляем текст
$curl->get('https://api.telegram.org/bot'.$token.'/sendMessage',array(
	'chat_id' => $chat,
	'text' => $text,
	)); 


}	

exit;
?>