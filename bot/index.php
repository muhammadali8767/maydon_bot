<?php

define ( "BOT_TOKEN", '786772794:AAEptdTryiDDYgXRsyF-Sbfy0JH_JkLqhU4' );

// Custom Helpers
require_once '../vendor/autoload.php';
require_once 'helpers/bcurl.php';
require_once 'helpers/bcommon.php';
require_once 'helpers/bkeyboard.php';
// require_once 'helpers/bquerycommon.php';

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


// $dbhelper = new BQueryCommon($telegram);
$emoji = BCommon::getEmoji();
$languages = BCommon::getLanguages();
if (!is_null($callback)) {

    $message = $callback->getMessage();
    $chat = $message->getChat();
    $data = $callback->getData();
    // $userObj = $dbhelper->getUser($chat->getId());

    // $lang = $userObj->language_code;
    // $cartCount = intval($userObj->cart_count);

    $data = json_decode($data);

    $params = BKeyboard::alert($callback, "Callback query!");

    try {
        $telegram->answerCallbackQuery($params);
    } catch (Exception $e) {
        BCommon::errorLog($telegram, $e->getMessage());
    }
} else if (!is_null($message)) {
    $text = $message->getText();
    $user = $message->getFrom();
    $chat = $message->getChat();
    $location = $message->getLocation();
    $contact = $message->getContact();

    // if user is private so answer to him
    if (strtolower($chat->getType()) === "private") {

        // $userObj = $dbhelper->getUser($chat->getId());
        // if ($userObj !== null) {
        //     $lang = $userObj->language_code;
        //     $pageStatus = $userObj->page_status;
        // }

        // when send location
        if (!is_null($location)) {

            $params = BKeyboard::hideKeyboard($chat);

            try {
                $response = $telegram->sendMessage($params);

            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            if ($response !== null) {

                $params = BKeyboard::deleteMessage($response, $chat);

                try {
                    $telegram->deleteMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            }

            // check
            if ($pageStatus != BCommon::STATUS_DELIVERY_METHOD) {

                $dbhelper->clearCart($chat->getId());

                $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT);

            }

            $resJson = BCommon::getManufacturersByLocation($location);

            // insert or update json data and location
            // $resJson = preg_replace("/'/", "`", $resJson);
            // $resJson = preg_replace('/"/', "`", $resJson);
            if (!is_null($resJson))
                $dbhelper->updateOrInsertCache($user, $location, $resJson);

            // bosh sahifani ochishi kerak ekan
            $dbhelper->updateCurrentPage(0, $chat->getId());

            $cache = $dbhelper->getCacheDataByChatId($chat->getId());

            // check
            if ($pageStatus != BCommon::STATUS_DELIVERY_METHOD) {

                $params = BKeyboard::manufacturers($message, $chat, $cache);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } else {

                $products = $dbhelper->getCart($chat->getId());

                $params = BKeyboard::deliveryMethod($chat, $cache, $products);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            }

        } else if (!is_null($contact)) {

            // $dbhelper->updateContact($chat->getId(), $contact);
            // $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_COMMENT);

            $params = BKeyboard::comment($chat, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        }

        // if text command exists so answer to him
        if (!empty($text) && !$user->isBot()) {

            if (strtolower($text) === "/start") {

                $params = BKeyboard::languages($chat, $user);

                // $dbhelper->updateOrInsertUser($user);
                // $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } elseif (BCommon::startsWith($text, $emoji["uz"]) || BCommon::startsWith($text, $emoji["ru"])) {

                $lang = "uz";
                if (BCommon::startsWith($text, $emoji["ru"]))
                    $lang = "ru";

                // $dbhelper->updateOrInsertUser($user, $lang);

                $params = BKeyboard::location($chat, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } elseif (BCommon::matchPhone($text)) {

                // $dbhelper->updatePhone($chat->getId(), $text);
                // $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_COMMENT);

                $params = BKeyboard::comment($chat, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } else if (!empty($text)) {

                $params = [
                    'chat_id' => $chat->getId(),
                    'text' => $languages[$lang]["text_invalid"]
                ];

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, "");
                }

            }

        }

    }

    unset($dbhelper);

}

header ( 'Content-Type:application/json' );
echo '{"ok":true,"result":true}';
exit ();

?>
