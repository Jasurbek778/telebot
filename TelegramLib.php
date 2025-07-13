<?php

define('BOT_TOKEN', '7665059273:AAHmcBiMvca1iOAQi4ci2yL9k_G8PjjHFvs');

function sendMessage($chat_id, $text, $params = []) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $params['chat_id'] = $chat_id;
    $params['text'] = $text;
    if (!isset($params['parse_mode'])) {
        $params['parse_mode'] = 'Markdown';
    }
    if (isset($params['reply_markup'])) {
        $params['reply_markup'] = json_encode($params['reply_markup']);
    }
    $query = http_build_query($params);
    $response = @file_get_contents("$url?$query");
    return $response !== false;
}

/**
 * Deletes a message from a Telegram chat.
 *
 * @param int|string $chat_id Chat ID
 * @param int $message_id Message ID to delete
 * @return bool Success status
 */
function deleteMessage($chat_id, $message_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteMessage";
    $params = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];
    $query = http_build_query($params);
    $response = @file_get_contents("$url?$query");
    return $response !== false;
}

function answerCallbackQuery($callback_query_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery";
    $params = [
        'callback_query_id' => $callback_query_id
    ];
    $query = http_build_query($params);
    $response = @file_get_contents("$url?$query");
    return $response !== false;
}

function setWebhook($url) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook";
    $params = ['url' => $url];
    $query = http_build_query($params);
    $response = @file_get_contents("$url?$query");
    return $response !== false;
}


function inlineKeyboard($rows) {
    return [
        'inline_keyboard' => $rows
    ];
}

function inlineButton($text, $callback_data) {
    return [
        'text' => $text,
        'callback_data' => $callback_data
    ];
}

?>
