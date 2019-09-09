<?php

// define ( "BOT_TOKEN", '786772794:AAEptdTryiDDYgXRsyF-Sbfy0JH_JkLqhU4' ); // bankomatchik uz
define ( "BOT_TOKEN", '975816512:AAEtda3oR5IRl7vX6pOc8sRCEqtYcGni3h0' ); // MAYDON_UZ_BOT
define ( "FULLPATH", '' );
define ( "BRINGO_URL", '' );


// Custom Helpers
require_once FULLPATH . '../vendor/autoload.php';
require_once FULLPATH . 'helpers/bcurl.php';
require_once FULLPATH . 'helpers/bcommon.php';
require_once FULLPATH . 'helpers/bkeyboard.php';
require_once FULLPATH . 'helpers/bquerycommon2.php';

// Telegram Helpers
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\User;
use Telegram\Bot\Keyboard\Keyboard;

$telegram = new Api(BOT_TOKEN);
$updates = $telegram->getWebhookUpdates();

$message = null;
$callback = null;

if ($updates->getMessage() != null) {

    $message = $updates->getMessage();

} else if ($updates->getEditedMessage() != null) {

    $message = $updates->getEditedMessage();

} else if ($updates->getCallbackQuery() != null) {

    $callback = $updates->getCallbackQuery();
}


$dbhelper = new BQueryCommon($telegram);
$emoji = BCommon::getEmoji();
$languages = BCommon::getLanguages();

if (!is_null($callback)) {

    $message = $callback->getMessage();
    $chat = $message->getChat();
    $data = $callback->getData();
    $userObj = $dbhelper->getUser($chat->getId());

    $lang = $userObj->language_code;
    $cartCount = intval($userObj->cart_count);

    $data = json_decode($data);

    if (isset($data->p)) {
        if ($data->p == "stadiums") {
            $result = $dbhelper->getListOfStadiums();
            $page = (isset($data->page)) ? $data->page : 0;  
            $params = BKeyboard::stadiums($message, $chat, $result, $page);
        } elseif ($data->p == "fields") {
            $result = $dbhelper->getListOfStadiums();
            // $result = $dbhelper->getStadiumField($data->s_id);
            // $page = (isset($data->page)) ? $data->page : 0;  
            $params = BKeyboard::fields($message, $chat, $result);
        } elseif ($data->p == "field") {
            $result = $dbhelper->getListOfStadiums();
            // $result = $dbhelper->getFieldInfo($data->f_id);
            $params = BKeyboard::fields($message, $chat, $result);
        } 
    } else {

        $callbackQuery = BKeyboard::alert($callback, "please restart bot");

        try {
            $telegram->answerCallbackQuery($callbackQuery);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }    

        $stadiums = $dbhelper->getListOfStadiums();
        $params = BKeyboard::stadiums($message, $chat, $stadiums, $lang);
    }

} elseif (!is_null($message)) {
    $text = $message->getText();
    $user = $message->getFrom();
    $chat = $message->getChat();

    if (strtolower($chat->getType()) === "private") {
        $userObj = $dbhelper->getUser($chat->getId());

        if ($userObj !== null) {
            $lang = $userObj->language_code;
            $pageStatus = $userObj->page_status;            
        }

        if (!empty($text) && !$user->isBot()) {
            if (strtolower($text) === "/start") {
                $stadiums = $dbhelper->getListOfStadiums();
                $params = BKeyboard::stadiums($message, $chat, $stadiums, $lang);
            } elseif (BCommon::matchPhone($text)) {
                $dbhelper->updatePhone($chat->getId(), $text);
                $params = BKeyboard::comment($chat, $lang);
            } elseif (BCommon::startsWith($text, $emoji["confirm"])) {

                /* shu joyda buyurtma qabul qilinishi kerak */

                $stadiums = $dbhelper->getListOfStadiums();
                $params = BKeyboard::stadiums($message, $chat, $stadiums, $lang);
            } elseif (BCommon::startsWith($text, $emoji["cancel"])) {
                $stadiums = $dbhelper->getListOfStadiums();
                $params = BKeyboard::stadiums($message, $chat, $stadiums, $lang);
            } else if (!empty($text)) {
                $params = ['chat_id' => $chat->getId(), 'text' => $languages[$lang]["text_invalid"]];
            }

        }
    }
}

$delete = BKeyboard::deleteMessage($message, $chat);

try {
    $telegram->deleteMessage($delete);
} catch (Exception $e) {
    BCommon::errorLog($telegram, $e->getMessage());
}

// $hide = BKeyboard::hideKeyboard($chat, $lang);

// try {
//     $hideResponse = $telegram->sendMessage($hide);

// } catch (Exception $e) {
//     BCommon::errorLog($telegram, $e->getMessage());
// }

try {
    $telegram->sendMessage($params);
} catch (Exception $e) {
    BCommon::errorLog($telegram, $e->getMessage());
}

header ( 'Content-Type:application/json' );
echo '{"ok":true,"result":true}';
exit ();

?>