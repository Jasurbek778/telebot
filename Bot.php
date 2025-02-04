<?php
$botToken = "7649697665:AAGjsnxzqhJuMZnXfkRyef5MJ8w8k0zB2kM"; // Enter ur bot token
$website = "https://api.telegram.org/bot".$botToken;
error_reporting(0);
$update = file_get_contents('php://input');
$update = json_decode($update, TRUE);
//$print = print_r($update);
$chatId = $update["message"]["chat"]["id"];
// $gId = $update["message"]["from"]["id"];
// $userId = $update["message"]["from"]["id"];
// $firstname = $update["message"]["from"]["first_name"];
// $username = $update["message"]["from"]["username"];
// $message = $update["message"]["text"];
// $message_id = $update["message"]["message_id"];

//////////=========[Start Command]=========//////////

// if ((strpos($message, "!start") === 0)||(strpos($message, "/start") === 0)){
//     $response = "Hello $firstname!";

//     $keyboard = [
//     'inline_keyboard' => [
//         [
//             ['text' => 'COMMANDS', 'callback_data' => 'someString']
//         ]
//     ]
// ];
// $encodedKeyboard = json_encode($keyboard);
// $parameters = 
//     array(
//         'chat_id' => $chatId, 
//         'text' => $response, 
//         'reply_markup' => $encodedKeyboard
//     );

// send('Dastur yordamchisi sizga yordamga tayyor!', $parameters);
// }

// function send($method, $data)
// {
//     $url = "https://api.telegram.org/bot7649697665:AAGjsnxzqhJuMZnXfkRyef5MJ8w8k0zB2kM/" . $method;

//     if (!$curld = curl_init()) {
//         exit;
//     }
//     curl_setopt($curld, CURLOPT_POST, true);
//     curl_setopt($curld, CURLOPT_POSTFIELDS, $data);
//     curl_setopt($curld, CURLOPT_URL, $url);
//     curl_setopt($curld, CURLOPT_RETURNTRANSFER, true);
//     $output = curl_exec($curld);
//     curl_close($curld);
//     return $output;
// }


$getQuery = array(
    "chat_id" 	=> $chatId,
    "text"  	=> "Новое сообщение из формы",
    "parse_mode" => "html"
);
$ch = curl_init("https://api.telegram.org/bot". $botToken ."/sendMessage?" . http_build_query($getQuery));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$resultQuery = curl_exec($ch);
curl_close($ch);

echo $resultQuery;





// function sendMessage ($chatId, $message){
// $url = $GLOBALS[website]."/sendMessage?chat_id=".$chatId."&text=".$message."&reply_to_message_id=".$message_id."&parse_mode=HTML";
// file_get_contents($url);      
// }

?>