<?php

ini_set('display_errors', true);

#================================================

define('API_KEY', '8582629749:AAGGxJdWpE3bp9MIqGvi2J9SqpmoJo9Nci8');

$idbot = 8582629749;
$userbot = 'cinematime_uzbot';
$umid = 7617397626;
$owners = array($umid);
$user = "murodov_ads";

define('DB_HOST', 'localhost');
define('DB_USER', 'botirovuz_ozzbek');
define('DB_PASS', 'ozzbek');
define('DB_NAME', 'botirovuz_ozzbek');

$connect = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($connect, 'utf8mb4');

function bot($method,$datas=[]){
	$url = "https://api.telegram.org/bot". API_KEY ."/". $method;
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
	$res = curl_exec($ch);
	if(curl_error($ch)) var_dump(curl_error($ch));
	else return json_decode($res);
}

#================================================

function deleteFolder($path){
if(is_dir($path) === true){
$files = array_diff(scandir($path), array('.', '..'));
foreach ($files as $file)
deleteFolder(realpath($path) . '/' . $file);
return rmdir($path);
}else if (is_file($path) === true)
return unlink($path);
return false;
}

function sendMessage($id, $text, $key = null){
return bot('sendMessage',[
'chat_id'=>$id,
'text'=>$text,
'parse_mode'=>'html',
'disable_web_page_preview'=>true,
'reply_markup'=>$key
]);
}

function editMessageText($cid, $mid, $text, $key = null){
return bot('editMessageText',[
'chat_id'=>$cid,
'message_id'=>$mid,
'text'=>$text,
'parse_mode'=>'html',
'disable_web_page_preview'=>true,
'reply_markup'=>$key
]);
}

function sendVideo($cid, $f_id, $text, $key = null){
return bot('sendVideo',[
'chat_id'=>$cid,
'video'=>$f_id,
'caption'=>$text,
'parse_mode'=>'html',
'protect_content' => true,
'reply_markup'=>$key
]);
}

function sendPhoto($cid, $f_id, $text, $key = null){
return bot('sendPhoto',[
'chat_id'=>$cid,
'photo'=>$f_id,
'caption'=>$text,
'parse_mode'=>'html',
'reply_markup'=>$key
]);
}

function copyMessage($id, $from_chat_id, $message_id){
return bot('copyMessage',[
'chat_id'=>$id,
'from_chat_id'=>$from_chat_id,
'message_id'=>$message_id
]);
}

function forwardMessage($id, $cid, $mid){
return bot('forwardMessage',[
'from_chat_id'=>$id,
'chat_id'=>$cid,
'message_id'=>$mid
]);
}

function deleteMessage($cid,$mid){
return bot('deleteMessage',[
'chat_id'=>$cid,
'message_id'=>$mid
]);
}

function getChatMember($cid, $userid){
return bot('getChatMember',[
'chat_id'=>$cid,
'user_id'=>$userid
]);
}

function replyKeyboard($key){
return json_encode(['keyboard'=>$key, 'resize_keyboard'=>true]);
}

function getName($id){
$getname = bot('getchat',['chat_id'=>$id])->result->first_name;
if(!empty($getname)){
return $getname;
}else{
return bot('getchat',['chat_id'=>$id])->result->title;
}
}

function getAdmin($chat){
$url = "https://api.telegram.org/bot".API_KEY."/getChatAdministrators?chat_id=$chat";
$result = file_get_contents($url);
$result = json_decode ($result);
return $result->ok;
}

function joinchat($id){
$array = array("inline_keyboard");
$kanallar=file_get_contents("admin/kanal.txt");
if($kanallar == null){
return true;
}else{
$ex = explode("\n",$kanallar);
for($i=0;$i<=count($ex) -1;$i++){
$first_line = $ex[$i];
$url=file_get_contents("admin/links/$first_line");
$ism=bot('getChat',['chat_id'=>$first_line])->result->title;
$ret = bot("getChatMember",[
"chat_id"=>$first_line,
"user_id"=>$id,
]);
$stat = $ret->result->status;
if($stat){
if($stat == "left"){
$get = file_get_contents("admin/zayavka/$first_line");
if(mb_stripos($get,$id)!==false){
$stat = "member";
}else{
$stat = "left";
}
}

    
if((($stat=="creator" or $stat=="administrator" or $stat=="member"))){
$array['inline_keyboard']["$i"][0]['text'] = "✅ ". $ism;
$array['inline_keyboard']["$i"][0]['url'] = $url;
}else{
$array['inline_keyboard']["$i"][0]['text'] = "$zayavka ❌ ". $ism;
$array['inline_keyboard']["$i"][0]['url'] = $url;
$uns = true;
}
}else{
return true; 
}
}
$array['inline_keyboard']["$i"][0]['text'] = "✅ Tekshirish";
$array['inline_keyboard']["$i"][0]['callback_data'] = "check";
if($uns == true){
sendMessage($id, "🚀 <b>Botdan to'liq foydalanish uchun quyidagi kanallarimizga obuna bo'ling!</b>", json_encode($array));
return false;
}else{
return true;
}}}

#================================================

date_default_timezone_set('Asia/Tashkent');
$soat = date('H:i');
$sana = date("d.m.Y");

#================================================

$update = json_decode(file_get_contents('php://input'));

$message = $update->message;
$callback = $update->callback_query;

if (isset($message)) {
$cid = $message->chat->id;
$Tc = $message->chat->type;

$text = $message->text;
$mid = $message->message_id;

$from_id = $message->from->id;
$name = $message->from->first_name;
$last = $message->from->last_name;

$photo = $message->photo[count($message->photo) - 1]->file_id;

$video = $message->video;
$file_id = $video->file_id;
$file_name = $video->file_name;
$file_size = $video->file_size;
$size = $file_size/1000;
$dtype = $video->mime_type;

$audio = $message->audio->file_id;
$voice = $message->voice->file_id;
$sticker = $message->sticker->file_id;
$video_note = $message->video_note->file_id;
$animation = $message->animation->file_id;

$caption = $message->caption;
}

if (isset($callback)) {
$data = $callback->data;
$qid = $callback->id;

$cid = $callback->message->chat->id;
$Tc = $callback->message->chat->type;
$mid = $callback->message->message_id;

$from_id = $callback->from->id;
$name = $callback->from->first_name;
$last = $callback->from->last_name;
}


$joinchatid = $update->chat_join_request->chat->id;
$chatjoinname = $update->chat_join_request->chat->title;
$chatjoinuser = $update->chat_join_request->chat->username;
$chatjoinlink = $update->chat_join_request->chat->invite_link;
$qb = $update->chat_join_request->from->id;
$fname = $update->chat_join_request->from->first_name;
$cty = $update->chat_join_request->chat->type;

#=================================================

mkdir("admin");
mkdir("admin/links");
mkdir("admin/zayavka");

$kino_id = file_get_contents("admin/kino.txt");
$kino = bot('getchat',['chat_id'=>$kino_id])->result->username;
$reklama = str_replace(["%kino%","%admin%"],[$kino,$user], file_get_contents("admin/rek.txt"));

#================================================

$admins = explode("\n", file_get_contents("admin/admins.txt"));
if (is_array($admins)) $admin = array_merge($owners, $admins);
else $admin = $owners;

#=================================================

$user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `user_id` WHERE `id` = $cid"));
$user_id = $user['user_id'];
$step = $user['step'];
$ban = $user['ban'];
$lastmsg = $user['lastmsg'];

#=================================================

if ($ban == 1) exit();

if(isset($message)){
if(!$connect){
sendMessage($cid, "⚠️ <b>Ma'lumotlar olishda xatolik!</b>\n\n<i>Iltimos tezroq adminga xabar bering.</i>");
return false;
}
}

mysqli_query($connect,"CREATE TABLE data(
`id` int(20) auto_increment primary key,
`file_name` varchar(256),
`file_id` varchar(256),
`film_name` varchar(256),
`film_date` varchar(256)
)");

mysqli_query($connect,"CREATE TABLE settings(
`id` int(20) auto_increment primary key,
`kino` varchar(256),
`kino2` varchar(256)
)");

mysqli_query($connect,"CREATE TABLE user_id(
`uid` int(20) auto_increment primary key,
`id` varchar(256),
`step` varchar(256),
`ban` varchar(256),
`lastmsg` varchar(256),
`sana` varchar(256)
)");

mysqli_query($connect,"CREATE TABLE texts(
`id` int(20) auto_increment primary key,
`start` varchar(256)
)");

mysqli_query($connect,"INSERT INTO `texts`(`id`, `start`) VALUES ('1','8J+RiyBBc3NhbG9tdSBhbGF5a3VtIHtuYW1lfSAgYm90aW1pemdhIHh1c2gga2VsaWJzaXouCgrinI3wn4+7IEtpbm8ga29kaW5pIHl1Ym9yaW5nLg==')");



if($Tc == "private"){
$result = mysqli_query($connect,"SELECT * FROM `user_id` WHERE `id` = $cid");
$rew = mysqli_fetch_assoc($result);
if($rew){
mysqli_query($connect,"UPDATE user_id SET sana = '$sana | $soat' WHERE id = $cid");
}else{
mysqli_query($connect,"INSERT INTO `user_id`(`id`,`step`,`sana`,`ban`) VALUES ('$cid','0','$sana | $soat','0')");
}
}

if($message){
$result = mysqli_query($connect,"SELECT * FROM `settings`");
$rew = mysqli_fetch_assoc($result);
if($rew){
}else{
mysqli_query($connect,"INSERT INTO `settings`(`kino`,`kino2`) VALUES ('0','0')");
}
}


$joinchatid = $update->chat_join_request->chat->id;
$chatjoinname = $update->chat_join_request->chat->title;
$chatjoinuser = $update->chat_join_request->chat->username;
$chatjoinlink = $update->chat_join_request->chat->invite_link;
$qb= $update->chat_join_request->from->id;
$fname= $update->chat_join_request->from->first_name;
$ty = $update->chat_join_request->chat->type;
if($ty == "channel" or $ty == "supergroup"){

$get = file_get_contents("admin/zayavka/$joinchatid");
if(mb_stripos($get,$qb)==false){
file_put_contents("admin/zayavka/$joinchatid", "$get\n$qb");
}
}

#=================================================

$panel = replyKeyboard([
[['text'=>"📊 Statistika"]],
[['text'=>"➕ Kino Qoshish"],['text'=>"➖ Kino Ochirish"]],
[['text'=>"👨‍💼 Adminlar"],['text'=>"💬 Kanallar"]],
[['text'=>"🔴 Blocklash"],['text'=>"🟢 Blockdan olish"]],
[['text'=>"✍️ Post xabar"],['text'=>"📬 Forward xabar"]],
[['text'=>"🚪 Paneldan chiqish"],['text'=>"📝 Matnlar"]],
]);

$cancel = replyKeyboard([
[['text'=>"◀️ Orqaga"]]
]);

$kanallar_p = replyKeyboard([
[['text'=>"➕ Kanal ulash"],['text'=>"➖ Kanal uzish"]],
[['text'=>"🎬 Kino kanal"],['text'=>"📮 Reklama"]],
[['text'=>"📃 Kanalar Royxati"]],
[['text'=>"◀️ Orqaga"]]
]);


$removeKey = json_encode(['remove_keyboard'=>true]);

#=================================================

if($text == "/start" and joinchat($cid)==true){
$keyBot = json_encode(['inline_keyboard'=>[
]]);
$start = base64_decode($tx);

$setting = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM texts WHERE id = 1"));
$start =str_replace(["{name}","{time}"],["<a href='tg://user?id=$cid'>$name</a>","$sana | $soat"],base64_decode($setting['start']));
sendMessage($cid, $start, $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}

else if ($data == "check"){
deleteMessage($cid, $mid);
$keyBot = json_encode(['inline_keyboard'=>[
]]);
if (joinchat($cid)==true) {
$setting = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM texts WHERE id = 1"));
$start =str_replace(["{name}","{time}"],["<a href='tg://user?id=$cid'>$name</a>","$sana | $soat"],base64_decode($setting['start']));
sendMessage($cid, $start, $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
}
}


$botdel = $update->my_chat_member->new_chat_member;
$botdelid = $update->my_chat_member->from->id;
$userstatus= $botdel->status;

if($botdel){
if($userstatus=="kicked"){
mysqli_query($connect,"UPDATE user_id SET sana = 'tark' WHERE id = $botdelid");
} 
}
#=================================================

if($text == "/dev" and joinchat($cid)==true){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"👨‍💻 Bot dasturchisi",'url'=>"https://t.me/richbolla"]],
[['text'=>"🔁 Boshqa botlar",'url'=>"https://t.me/NamunaBotlar"]],
]]);
sendMessage($cid, "👨‍💻 <b>Botimiz dasturchisi @richbolla</b>\n\n<i>🤖 Sizga ham shu kabi botlar kerak bo‘lsa bizga buyurtma berishingiz mumkin. Sifatli botlar tuzib beramiz.</i>\n\n<b>📊 Na’munalar:</b> @NamunaBotlar", $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}

if($text == "/help" and joinchat($cid)==true){
$keyBot = json_encode(['inline_keyboard'=>[
]]);
sendMessage($cid, "<b>📊 Botimiz buyruqlari:</b>\n/start - Botni yangilash ♻️\n/rand - Tasodifiy film 🍿\n/dev - Bot dasturchisi 👨‍💻\n/help - Bot buyruqlari 🔁\n\n<b>🤖 Ushbu bot orqali kinolarni osongina qidirib topishingiz va yuklab olishingiz mumkin. Kinoni yuklash uchun kino kodini yuborishingiz kerak. Barcha kino kodlari pastdagi kanalda jamlangan.</b>", $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}

else if(($text == "/panel" or $text == "/a" or $text == "/admin" or $text == "/p" or $text == "◀️ Orqaga") and in_array($cid,$admin)){
sendMessage($cid, "<b>👨🏻‍💻 Boshqaruv paneliga xush kelibsiz.</b>\n\n<i>Nimani o'zgartiramiz?</i>", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'panel' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
unlink("film.txt");
exit();
}

else if ($text == "🚪 Paneldan chiqish" and in_array($cid,$admin)){
sendMessage($cid, "<b>🚪 Panelni tark etdingiz unga /panel yoki /admin xabarini yuborib kirishingiz mumkin.\n\nYangilash /start</b>", $removeKey);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'start' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}

else if ($text == "➕ Kino Qoshish" and in_array($cid,$admin)){
sendMessage($cid, "<b>🎬 Kinoni yuboring:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'movie' WHERE `id` = $cid");
exit();
}

else if(isset($video) and $step == "movie"){
$file_id = $video->file_id;
$file_name = $video->file_name;
$file_size = $video->duration;    
file_put_contents("file.id",$file_id);
file_put_contents("file.name",base64_encode($file_name));
sendMessage($cid, "<b>🎬 Kinoni malumotini yuboring:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'caption' WHERE `id` = $cid");
exit();
}


else if($step == "caption"){
file_put_contents("film.caption",base64_encode($text));
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"🎞️ Kanalga yuborish",'callback_data'=>"channel"]]
]]);
$file_id = file_get_contents("file.id");
sendVideo($cid, $file_id, "<b>$text</b> \n\n<b>$reklama</b>",$keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();

}

else if($data == "channel"){
deleteMessage($cid,$mid);
sendMessage($cid, "<b>📝 Post uchun video yoki rasm yuboring:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'post' WHERE `id` = $cid");
exit();
} 

else if($step == "post"){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"✅ Yuborish",'callback_data'=>"sms"]]
]]);
if($video){
$file_id = $video->file_id;
file_put_contents("post.video",$file_id);
file_put_contents("post.type","video");
sendVideo($cid, $file_id,"<b>✅ Qabul qilindi.</b>",$keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
}elseif ($photo){
file_put_contents("post.photo",$photo);
file_put_contents("post.type","photo");
sendPhoto($cid, $photo,"<b>✅ Qabul qilindi.</b>",$keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
}else{
sendMessage($cid, "<b>⚠️ Hatolik yuzberdi video yoki rasm yuboring!</b>",null);
}
exit();
}

else if($data == "sms"){
$film_id = file_get_contents("file.id");
$file_name = file_get_contents("file.name");
$film_caption = file_get_contents("film.caption");
$code = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM `settings` WHERE `id` = '1'"))['kino'];
$code = $code+1;
$save = mysqli_query($connect,"INSERT INTO data (`id`,`file_name`,`file_id`,`film_name`,`film_date`) VALUES ('$code','$file_name','$film_id','$film_caption','$sana')");
mysqli_query($connect,"UPDATE settings SET kino = '$code' WHERE id = 1"); 
if($save){
$type = file_get_contents("post.type");

if($type == "video"){
$video = file_get_contents("post.video");
$mes=sendVideo("@$kino",$video,"🎬 <b>#Premyera

 Ushbu kinoni toʻliq holda @$userbot orqali yuklab oling

🔢 Kino kodi: $code

🎞️ Kino kodlari: @$kino
🤖 Yuklab olish: @$userbot </b>",null)->result->message_id;

if($mes){
deleteMessage($cid,$mid);
sendMessage($cid,"✅ <b>@$kino kanaliga yuborildi! \n\n🔢 Kino kodi: <code>$code</code>\n\n👀 <a href='https://t.me/$kino/$mes'>Ko‘rish</a></b>",$panel);
unlink("file.id");
unlink("file.name");
unlink("film.caption");
unlink("post.type");
unlink("post.video");
unlink("post.photo");
}else{
sendMessage($cid, "<b>⚠️ Kanalga post yuborishda hatolik yuzberdi!</b>",null);
}

}elseif ($type == "photo"){
$photo = file_get_contents("post.photo");
$mes=sendPhoto("@$kino",$photo,"🎬<b>#Premyera

Ushbu kinoni toʻliq holda @$userbot orqali yuklab oling

🔢 Kino kodi: $code

🎞️ Kino kodlari: @$kino
🤖 Yuklab olish: @$userbot</b> ,",null)->result->message_id;

if($mes){
deleteMessage($cid,$mid);
sendMessage($cid,"✅ <b>@$kino kanaliga yuborildi! \n\n🎬 Kino kodi: <code>$code</code>\n\n👀 <a href='https://t.me/$kino/$mes'>Ko‘rish</a></b>",$panel);
unlink("file.id");
unlink("file.name");
unlink("film.caption");
unlink("post.type");
unlink("post.video");
unlink("post.photo");
}else{
sendMessage($cid, "<b>⚠️ Kanalga post yuborishda hatolik yuzberdi!</b>",null);
}
}

}else{
sendMessage($cid, "<b>⚠️ Kinoni bazaga saqlashda hatolik yuzberdi!</b>",null);
}

mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}



if(mb_stripos($text,"/set ")!==false){
$ex = explode(" ",$text)[1];
mysqli_query($connect,"UPDATE `settings` SET `kino` = '$ex' WHERE `id` = '1'");
}
if(mb_stripos($text,"/set2 ")!==false){
$ex = explode(" ",$text)[1];
mysqli_query($connect,"UPDATE `settings` SET `kino2` = '$ex' WHERE `id` = '1'");
}

else if ($text == "➖ Kino Ochirish" and in_array($cid,$admin)){
sendMessage($cid, "<b>🗑️ Kino o'chirish uchun menga kino kodini yuboring:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'deleteMovie' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'movie-remove' WHERE `id` = $cid");
exit();
}

else if(($step == "movie-remove" and $text != "🗑️ Kino o'chirish") and in_array($cid,$admin)){
$res = mysqli_query($connect, "SELECT * FROM `data` WHERE `id` = '$text'");
$row = mysqli_fetch_assoc($res);
$uz = mysqli_query($connect, "SELECT * FROM `settings` WHERE `id` = '1");
$bek = mysqli_fetch_assoc($res);
$ex = $bek['kino2'];
$del = $ex+1;
if($row){
mysqli_query($connect, "DELETE FROM `data` WHERE `id` = $text"); 
sendMessage($cid, "🗑️ $text <b>raqamli kino olib tashlandi!</b>");
mysqli_query($connect,"UPDATE `settings` SET `kino2` = '$del' WHERE `id` = '1'");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}else{
sendMessage($cid, "📛 $text <b>mavjud emas!</b>\n\n🔄 Qayta urinib ko'ring:");
exit();
}
}

else if ($text == "🎬 Kino kanal" and in_array($cid,$admin)){
sendMessage($cid, "<b>💡 Kino kanal havolasini yuboring!\n\nNa'muna: @NamunaBotlar</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'movie_chan' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'movie_chan' WHERE `id` = $cid");
exit();
}

else if (($step == "movie_chan" and $text != "🎬 Kino kanal") and in_array($cid,$admin)) {
$nn_id = bot('getchat',['chat_id'=>$text])->result->id;
sendMessage($cid, "<b>✅ $text (".str_replace('-100','',$nn_id).") ga o‘zgartirildi.</b>", $panel);
file_put_contents("admin/kino.txt", $nn_id);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
}

else if ($text == "📮 Reklama" and in_array($cid,$admin)){
sendMessage($cid, "<b>📮 Reklama yuboring!\n\nNa'muna:</b> <pre>@%kino% kanali uchun maxsus joylandi!</pre>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'ads_set' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'ads_set' WHERE `id` = $cid");
exit();
}

else if (($step == "ads_set" and $text != "📮 Reklama") and in_array($cid,$admin)) {
sendMessage($cid, "<b>✅ $text ga o'zgartirildi.</b>", $panel);
file_put_contents("admin/rek.txt", $text);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
}

else if ($text == "💬 Kanallar" and in_array($cid,$admin)){
sendMessage($cid, "<b>🔰 Kanallar bo'limi:\n🆔 Admin: $cid</b>", $kanallar_p);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'channels' WHERE `id` = $cid");
exit();
}

else if ($text == "➕ Kanal ulash" and in_array($cid,$admin)){
sendMessage($cid, "<b>Majbur obuna ulamoqchi bo'lgan kanaldan (forward) shaklida habar olib yuboring.</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'channelsAdd' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'channel-add' WHERE `id` = $cid");
exit();
}

else if (($step == "channel-add" and $text != "➕ Kanal ulash") and in_array($cid,$admin)){
$channel_id=$update->message->forward_from_chat->id;
$channel_name=bot('getChat',['chat_id'=>$channel_id])->result->title;
$get = bot('getChat',['chat_id'=>$knnak]);
if($channel_id){
if(getAdmin($channel_id)!= true){
sendMessage($cid, "<b>⚠️ Bot ushbu kanalda admin emas</b>", $cancel);
}else{
sendMessage($cid, "<b>✅ $channel_name - qabul qilindi, endi havola kiriting!</b>", $cancel);
$kanal = file_get_contents("admin/kanal.txt");
if($kanal==null){
file_put_contents("admin/kanal.txt",$channel_id);
}else{
file_put_contents("admin/kanal.txt","$kanal\n$channel_id");
}
file_put_contents("admin/channel.id",$channel_id);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'url' WHERE `id` = $cid");
}
}else{
sendMessage($cid, "<b>Majbur obuna ulamoqchi bo'lgan kanaldan (forward) shaklida habar olib yuboring.</b>", $cancel);
}
exit();
}

if($step == "url" and $text){
$channel_id = file_get_contents("admin/channel.id");
file_put_contents("admin/links/$channel_id",$text);
unlink("admin/channel.id");
sendMessage($cid, "<b>✅ Qabul qilindi!</b>", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
}


else if ($text == "➖ Kanal uzish" and in_array($cid,$admin)){
sendMessage($cid, "<b>✅ Kanallar uzildi.</b>");
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'deleteChan' WHERE `id` = $cid");
deleteFolder("admin/links/");
deleteFolder("admin/zayavka/");
unlink("admin/kanal.txt");
exit();
}

else if ($text == "📃 Kanalar Royxati" and in_array($cid,$admin)){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>🟩 Majburish a'zolik kanallari:</b>\n\n". file_get_contents("admin/kanal.txt"),
'parse_mode'=>'html',
'reply_markup'=>$cancel
]);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'channels' WHERE `id` = $cid");
exit();
}


else if ($text == "🔴 Blocklash" and in_array($cid,$admin)){
sendMessage($cid, "<b>Foydalanuvchi ID raqamini kiriting:</b>\n\n<i>M-n: $cid</i>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'addblock' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'blocklash' WHERE `id` = $cid");
exit();
}

else if (($step == "blocklash" and $text != "🔔 Blocklash") and in_array($cid,$admin)){
sendMessage($cid, "<b>✅ $text blocklandi!</b>", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `ban` = 1 WHERE `id` = $text");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}

else if ($text == "🟢 Blockdan olish" and in_array($cid,$admin)){
sendMessage($cid, "<b>Foydalanuvchi ID raqamini kiriting:</b>\n\n<i>M-n: $cid</i>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'deleteBlock' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'blockdanolish' WHERE `id` = $cid");
exit();
}

else if (($step == "blockdanolish" and $text != "🔕 Blockdan olish") and in_array($cid,$admin)){
sendMessage($cid, "<b>✅ $text blockdan olindi!</b>", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `ban` = 0 WHERE `id` = $text");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}

else if($text == "✍️ Post xabar" and in_array($cid,$admin)){
sendMessage($cid, "<b>Xabaringizni yuboring:</b>",$cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'post_msg' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'post_send' WHERE `id` = $cid");
exit();
}

else if (($step == "post_send" and $text != "✍️ Post xabar") and in_array($cid,$admin)){
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
$msg = sendMessage($cid, "✅ <b>Xabar yuborish boshlandi!</b>", $panel)->result->message_id;
$yuborildi = 0;
$yuborilmadi = 0;
$result = mysqli_query($connect, "SELECT * FROM `user_id`");
while($row = mysqli_fetch_assoc($result)){
$id = $row['id'];
$ok = copyMessage($id, $cid, $mid)->ok;
if ($ok == true) $yuborildi++;
else $yuborilmadi++;
if(!$ok){
mysqli_query($connect,"UPDATE user_id SET sana = 'tark' WHERE id = $id");
}
editMessageText($cid, $msg, "✅ <b>Yuborildi:</b> {$yuborildi}taga\n❌ <b>Yuborilmadi:</b> {$yuborilmadi}taga");
}
deleteMessage($cid, $msg);
sendMessage($cid, "💡 <b>Xabar yuborish tugatildi.\n\n</b>✅ <b>Yuborildi:</b> {$yuborildi}taga\n❌ <b>Yuborilmadi:</b> {$yuborilmadi}taga\n\n<b>⏰ Soat: $soat | 📆 Sana: $sana</b>", $panel);
mysqli_query($connect,"UPDATE user_id SET sana = 'tark' WHERE id = $botdelid");
}

else if($text == "📬 Forward xabar" and in_array($cid,$admin)){
sendMessage($cid, "<b>Xabaringizni yuboring:</b>",$cancel);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'post_msg' WHERE `id` = $cid");
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'forward_send' WHERE `id` = $cid");
exit();
}

else if (($step == "forward_send" and $text != "📬 Forward xabar") and in_array($cid,$admin)){
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
$msg = sendMessage($cid, "✅ <b>Xabar yuborish boshlandi!</b>", $panel)->result->message_id;
$result = mysqli_query($connect, "SELECT * FROM `user_id`");
$yuborildi = 0;
$yuborilmadi = 0;
while($row = mysqli_fetch_assoc($result)){
$id = $row['id'];
$ok = forwardMessage($cid, $id, $mid)->ok;
if ($ok == true) $yuborildi++;
else $yuborilmadi++;
editMessageText($cid, $msg, "✅ <b>Yuborildi:</b> {$yuborildi}taga\n❌ <b>Yuborilmadi:</b> {$yuborilmadi}taga");
}
if(!$ok){
mysqli_query($connect,"UPDATE user_id SET sana = 'tark' WHERE id = $id");
}
deleteMessage($cid, $msg);
sendMessage($cid, "💡 <b>Xabar yuborish tugatildi.\n\n</b>✅ <b>Yuborildi:</b> {$yuborildi}taga\n❌ <b>Yuborilmadi:</b> {$yuborilmadi}taga\n\n<b>⏰ Soat: $soat | 📆 Sana: $sana</b>", $panel);
}

else if($text == "📊 Statistika" and in_array($cid,$admin)){
$res = mysqli_query($connect, "SELECT * FROM `user_id`");
$us = mysqli_num_rows($res);
$resp = mysqli_query($connect, "SELECT * FROM `user_id` WHERE `sana` = 'tark'");
$tark = mysqli_num_rows($resp);
$active = $us - $tark;
$res = mysqli_query($connect, "SELECT * FROM `data`");
$kin = mysqli_num_rows($res);
$ping = sys_getloadavg()[2];
$cod = mysqli_query($connect,"SELECT * FROM `settings` WHERE `id` = '1'");
$roow = mysqli_fetch_assoc($cod);
$code = $roow['kino'];
$deleted = $roow['kino2'];
sendMessage($cid, "
💡 <b>O'rtacha yuklanish:</b> <code>$ping</code>

• <b>Jami a’zolar:</b> $us ta
• <b>Tark etgan a’zolar:</b> $tark ta
• <b>Faol a’zolar:</b> $active ta
—————————————
• <b>Faol kinolar:</b> $kin ta
• <b>O‘chirilgan kinolar:</b> $deleted ta
• <b>Barcha kinolar:</b> $code ta");
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'stat' WHERE `id` = $cid");
exit();
}

else if(($text == "👨‍💼 Adminlar" or $data == "admins") and in_array($cid,$admin)){
if(isset($data)) deleteMessage($cid, $mid);
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"➕ Yangi admin qo'shish",'callback_data'=>"add-admin"]],
[['text'=>"📑 Ro'yxat",'callback_data'=>"list-admin"],['text'=>"🗑 O'chirish",'callback_data'=>"remove"]],
]]);
sendMessage($cid, "👇🏻 <b>Quyidagilardan birini tanlang:</b>", $keyBot);
mysqli_query($connect, "UPDATE `user_id` SET `lastmsg` = 'admins' WHERE `id` = $cid");
exit();
}

else if($data == "list-admin"){
$admins = file_get_contents("admin/admins.txt");
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"admins"]],
]]);
editMessageText($cid, $mid, "<b>👮 Adminlar ro'yxati:</b>\n\n$admins", $keyBot);
}

else if($data == "add-admin"){
deleteMessage($cid, $mid);
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'add-admin' WHERE `id` = $cid");
}

else if($step == "add-admin" and $cid == $umidjon){
if(is_numeric($text)=="true"){
if($text != $umidjon){
file_put_contents("admin/admins.txt", "\n$text", 8);
sendMessage($umidjon, "✅ <b>$text endi bot admini.</b>", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}else{
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>");
exit();
}
}else{
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>");
exit();
}
}

else if($data == "remove"){
deleteMessage($cid, $mid);
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>", $cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'remove-admin' WHERE `id` = $cid");
exit();
}

else if($step == "remove-admin" and $cid == $umidjon){
if(is_numeric($text)=="true"){
if($text != $umidjon){
$files = file_get_contents("admin/admins.txt");
$file = str_replace("{$text}", '', $files);
file_put_contents("admin/admins.txt",$file);
sendMessage($umidjon, "✅ <b>$text endi botda admin emas.</b>", $panel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}else{
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>");
exit();
}
}else{
sendMessage($cid, "<b>Kerakli iD raqamni kiriting:</b>");
exit();
}
}


if((isset($text) and $lastmsg == "start") and $text != "/start"){
$son = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `data`"));
if(mb_stripos($text,"/start ")!==false){
$text = explode(" ",$text)[1];
}
if($text == "/rand"){
$text = rand(1,$son);
}
if(joinchat($cid)==true ){
if(is_numeric($text) == true ){
if(in_array($cid,$admin)){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"🎞️ Kanalga yuborish (Admin uchun)",'callback_data'=>"channel=$text"]],
[['text'=>"↗️ Do'stlarga ulashish",'url'=>"https://t.me/share/url/?url=https://t.me/$userbot?start=$text"]],
[['text'=>"🔎 Boshqa kodlar",'url'=>"https://t.me/$kino"]],
]]);
}else{
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"↗️ Do'stlarga ulashish",'url'=>"https://t.me/share/url/?url=https://t.me/$userbot?start=$text"]],
[['text'=>"🔎 Boshqa kodlar",'url'=>"https://t.me/$kino"]],
]]);
}
$res = mysqli_query($connect, "SELECT * FROM `data` WHERE `id` = '$text'");
$row = mysqli_fetch_assoc($res);
$fname = base64_decode($row['film_name']);
$f_id = $row['file_id'];
$date = $row['film_date'];
if(!$row){
sendMessage($cid, "📛 $text <b>kodli kino mavjud emas!</b>");
exit();
}else{
sendVideo($cid, $f_id, "<b>$fname</b> \n\n$reklama",$keyBot);
exit();
}
}else{
sendMessage($cid, "<b>📛 Faqat raqamlardan foydalaning!</b>");
exit();
}
}
}/*else {
sendMessage($cid, "<b>☹︎ Sizni tushuna olib bo'lmadi!\n\nBotni yangilang: /start</b>");
}*/


if($text == "📝 Matnlar" and in_array($cid,$admin)){
$keyBot = json_encode(['inline_keyboard'=>[
[['text'=>"1",'callback_data'=>"text=start"]],
]]);
sendMessage($cid, "<b>📑 Matnlar:</b>

1. /start - uchun matn.",$keyBot);
}


if(mb_stripos($data,"text=")!==false){
$a = explode("=",$data)[1];
$text = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM texts WHERE id = 1"))[$a];
$fname = base64_decode($text);
deleteMessage($cid,$mid);
if($a=="start"){
$te = "<pre>{name}</pre> - Foydalanuvchi ismi";
}
sendMessage($cid,$te);
sendMessage($cid,"<code>".base64_decode($text)."</code>");
sendMessage($cid,"<b>Yangi matn kiriting.</b>",$cancel);
mysqli_query($connect, "UPDATE `user_id` SET `step` = 'text=$a' WHERE `id` = $cid");
exit();
}

if(mb_stripos($step,"text=")!==false){
$a = explode("=",$step)[1];
sendMessage($cid,"<b>✅ Qabul qilindi.</b>",$panel);
$tx = base64_encode($text);
mysqli_query($connect, "UPDATE `texts` SET `$a` = '$tx' WHERE `id` = 1");
mysqli_query($connect, "UPDATE `user_id` SET `step` = '0' WHERE `id` = $cid");
exit();
}



?>
