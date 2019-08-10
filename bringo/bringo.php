<?php
$check_host = ($_SERVER['HTTP_HOST'] == 'bot.bringo.uz');

define("BOT_PAYMENT_PAYME_TOKEN", "387026696:LIVE:57e4cfa7b4734c4b7b75be6d");
define("BOT_CLICK_PAYME_TOKEN", "333605228:LIVE:3259_ED86F246F734646D674DD346E5BB88F9D2EFBABF");

define ( "BOT_TOKEN", ($check_host) ? '190275106:AAHqUxD-9PFVcleH8yrV4xKtlm6Q6tPWf5A' : '490146007:AAGvlzSDzCWo-SfPjxVgZLwprVW3k0OGfFE' );
define ( "FULLPATH", ($check_host) ? '/opt/www/telegrambot' : '/opt/www/tbot.bringo.uz' );
define ( "BRINGO_URL", ($check_host) ? 'https://bringo.uz' : 'dev.bringo.uz' );


// Custom Helpers
require_once FULLPATH . '/vendor/autoload.php';
require_once FULLPATH . '/bringo/helpers/bcurl.php';
require_once FULLPATH . '/bringo/helpers/bcommon.php';
require_once FULLPATH . '/bringo/helpers/bkeyboard.php';
require_once FULLPATH . '/bringo/helpers/bquerycommon.php';

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


//~~~~~~~~~~~~~~~~~~~~~~~~ PAYMENT CODE ~~~~~~~~~~~~~~~~~~~~~~~~ 

$requestBody = json_decode(file_get_contents('php://input'), true);

if (isset($requestBody['pre_checkout_query'])) {
    $checkout_id = $requestBody['pre_checkout_query']['id'];
    $checkout_payload = $requestBody['pre_checkout_query']['invoice_payload'];
    $chat_id = $requestBody['pre_checkout_query']['from']['id'];


    $payment_data = $dbhelper->getBotPaymentByInvoicePayload($chat_id, $checkout_payload);
    $userObj = $dbhelper->getUser($chat_id);

    $lang = $userObj->language_code;

    if (intval($payment_data->status) === 0) {
        BCommon::answerPreCheckoutQuery(BOT_TOKEN, $checkout_id, true);
    } else {
        BCommon::answerPreCheckoutQuery(BOT_TOKEN, $checkout_id, false, ['error_message' => $languages[$lang]['order_already_paid']]);
    }
}

if (isset($requestBody['message']['successful_payment'])) {
    $chat = $message->getChat();
    $payment_data = $dbhelper->getBotPaymentByInvoicePayload($chat->getId(), $requestBody['message']['successful_payment']['invoice_payload']);

    if ($payment_data) {

        $dbhelper->updateOrderPayment($payment_data->id, array(
            'raw' => json_encode($requestBody),
            'tg_payment_id' => $requestBody['message']['successful_payment']['telegram_payment_charge_id'],
            'provider_payment_id' => $requestBody['message']['successful_payment']['provider_payment_charge_id'],
        ));

    } else {
        BCommon::errorLog($telegram, ["message" => "Payment qabul qilinmadi. ", "data" => $requestBody]);
    }
}

//~~~~~~~~~~~~~~~~~~~~~~~~ END PAYMENT ~~~~~~~~~~~~~~~~~~~~~~~~ 


if (!is_null($callback)) {

    $message = $callback->getMessage();
    $chat = $message->getChat();
    $data = $callback->getData();
    $userObj = $dbhelper->getUser($chat->getId());

    $lang = $userObj->language_code;
    $cartCount = intval($userObj->cart_count);

    $data = json_decode($data);

    if (isset($data->page)) {

        // check if prev btn is clicked
        $currentPage = intval($data->page);

        if (isset($data->prev) && $currentPage > 0)
            $currentPage -= 1;

        // check if next btn is clicked
        if (isset($data->next)) {
            $currentPage += 1;
        }

        $dbhelper->updateCurrentPage($currentPage, $chat->getId());

        $cache = $dbhelper->getCacheDataByChatId($chat->getId());

        $params = BKeyboard::manufacturers($message, $chat, $cache, $lang);

        try {
            $telegram->editMessageReplyMarkup($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

    } else if (isset($data->delivery_terms)) {

        $params = BKeyboard::deliveryTerms($chat, $lang);

        try {
            $telegram->sendMessage($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

    } else if (isset($data->location)) {

        $params = BKeyboard::deleteMessage($message, $chat);

        try {
            $telegram->deleteMessage($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

        $params = BKeyboard::location($chat, $lang);

        try {
            $telegram->sendMessage($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

    } else if (isset($data->s)) {


        if (($data->s == BCommon::STATUS_RESTARAUNT && !isset($data->back)) || ($data->s == BCommon::STATUS_CATEGORY && isset($data->back))) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $items = $dbhelper->getCategories($data->m, $lang);

            $cache = $dbhelper->getCacheDataByChatId($chat->getId());

            $params = BKeyboard::categories($chat, $items, $data->m, 0, $cartCount, $cache, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif ($data->s == BCommon::STATUS_RESTARAUNT && isset($data->back) && !isset($data->backToHome)) {

            $cart = $dbhelper->getCart($chat->getId());
            $params = BKeyboard::deleteMessage($message, $chat);
            
            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }
            
            if ($cart) {
                $params = BKeyboard::backToHome($chat, $data->m, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } else {
                $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT);

                // bosh sahifani ochishi kerak ekan
                $dbhelper->updateCurrentPage(0, $chat->getId());

                $cache = $dbhelper->getCacheDataByChatId($chat->getId());

                $params = BKeyboard::manufacturers($message, $chat, $cache, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }
            }

        } elseif ($data->s == BCommon::STATUS_RESTARAUNT && isset($data->back) && isset($data->backToHome)) {

            $cart = $dbhelper->getCart($chat->getId());
            $cache = $dbhelper->getCacheDataByChatId($chat->getId());
            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            if ($cart) {
                $params = BKeyboard::alert($callback, $languages[$lang]["msg_clear_cart"]);

                try {
                    $telegram->answerCallbackQuery($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            }

            $dbhelper->clearCart($chat->getId());
            $dbhelper->updateCurrentPage(0, $chat->getId());
            $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT);

            $params = BKeyboard::manufacturers($message, $chat, $cache, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif (($data->s == BCommon::STATUS_CATEGORY && !isset($data->back)) || ($data->s == BCommon::STATUS_PRODUCT && isset($data->back))) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $products = $dbhelper->getProducts($data->m, $data->c, $lang);

            $cache = $dbhelper->getCacheDataByChatId($chat->getId());

            $params = BKeyboard::products($chat, $products, $data->m, $data->c, 0, $cartCount, $cache, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif ($data->s == BCommon::STATUS_PRODUCT && !isset($data->back)) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }


            $product = $dbhelper->getProduct($data->p, $lang);

            $params = BKeyboard::product($chat, $product, $data->m, $data->c, 1, $lang);

            try {
                $telegram->sendPhoto($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif ($data->s == BCommon::STATUS_ADD_CART && isset($data->add)) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $params = BKeyboard::alert($callback, $languages[$lang]["msg_add_cart"]);

            try {
                $telegram->answerCallbackQuery($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $dbhelper->updateOrInsertCart($chat->getId(), $data->m, $data->p, $data->q);

            $dbhelper->updateCartCount($chat->getId());

            $cartCount += intval($data->q);

            $items = $dbhelper->getCategories($data->m, $lang);

            $cache = $dbhelper->getCacheDataByChatId($chat->getId());

            $params = BKeyboard::categories($chat, $items, $data->m, 0, $cartCount, $cache, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif (($data->s == BCommon::STATUS_CART && isset($data->cart)) || ($data->s == BCommon::STATUS_CART && isset($data->d))) {

            if (intval($cartCount) > 0) {

                $params = BKeyboard::deleteMessage($message, $chat);

                try {
                    $telegram->deleteMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

                if (isset($data->d))
                    $dbhelper->deleteCart($chat->getId(), $data->id);

                $products = $dbhelper->getCart($chat->getId());

                if (!empty($products)) {

                    $cache = $dbhelper->getCacheDataByChatId($chat->getId());

                    $params = BKeyboard::cart($chat, $products, $cache, $lang);

                    try {
                        $telegram->sendMessage($params);
                    } catch (Exception $e) {
                        BCommon::errorLog($telegram, $e->getMessage());
                    }

                } else {

                    $params = BKeyboard::alert($callback, $languages[$lang]["msg_clear_cart"], true);

                    try {
                        $telegram->answerCallbackQuery($params);
                    } catch (Exception $e) {
                        BCommon::errorLog($telegram, $e->getMessage());
                    }

                    $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT);

                    // bosh sahifani ochishi kerak ekan
                    $dbhelper->updateCurrentPage(0, $chat->getId());

                    $cache = $dbhelper->getCacheDataByChatId($chat->getId());

                    $params = BKeyboard::manufacturers($message, $chat, $cache, $lang);

                    try {
                        $telegram->sendMessage($params);
                    } catch (Exception $e) {
                        BCommon::errorLog($telegram, $e->getMessage());
                    }

                }

            } else {

                $params = BKeyboard::alert($callback, $languages[$lang]["msg_empty_cart"]);

                try {
                    $telegram->answerCallbackQuery($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            }

        } elseif ($data->s == BCommon::STATUS_DELIVERY_METHOD) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $dbhelper->updatePageStatus($chat->getId(), intval($data->s));

            $cache = $dbhelper->getCacheDataByChatId($chat->getId());

            $products = $dbhelper->getCart($chat->getId());

            $params = BKeyboard::deliveryMethod($chat, $cache, $products, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif ($data->s == BCommon::STATUS_DELIVERY_TYPE) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $dbhelper->updatePageStatus($chat->getId(), intval($data->s));

            $cache = $dbhelper->getCacheDataByChatId($chat->getId());

            $products = $dbhelper->getCart($chat->getId());

            $params = BKeyboard::deliveryType($chat, $cache, $products, $data->pickup, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif ($data->s == BCommon::STATUS_PAYMENT) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            if (isset($data->h)) {
                $dbhelper->updateDeliveryTime($chat->getId(), $data->h.":".$data->i.":00");
                $params = BKeyboard::alert($callback, $languages[$lang]["msg_time_saved"]);

                try {
                    $telegram->answerCallbackQuery($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }
            }
            if ($data->pickup) {
                if ($data->urgently) {
                    $delivery_id = 15;
                    $dbhelper->updateDeliveryDate($chat->getId(), $date = "0000-00-00");
                    $dbhelper->updateDeliveryTime($chat->getId(), $time = "00:00:00");
                } else {
                    $delivery_id = 15;
                    $date = $dbhelper->getDeliveryDate($chat->getId());
                    $time = $dbhelper->getDeliveryTime($chat->getId());
                }
            } else {
                if ($data->urgently) {
                    $delivery_id = 16;
                    $dbhelper->updateDeliveryDate($chat->getId(), $date = "0000-00-00");
                    $dbhelper->updateDeliveryTime($chat->getId(), $time = "00:00:00");
                } else {
                    $delivery_id =14;
                    $date = $dbhelper->getDeliveryDate($chat->getId());
                    $time = $dbhelper->getDeliveryTime($chat->getId());
                }
            }

            $payments = $dbhelper->getPayments($chat->getId(), $lang);
            $userObj = $dbhelper->getUser($chat_id);
            $cache = $dbhelper->getCacheDataByChatId($chat->getId());

            $products = $dbhelper->getCart($chat->getId());

            $params = BKeyboard::payments($chat, $payments, $delivery_id, $cache, $products, $date, $time, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif ($data->s == BCommon::STATUS_CLAENDAR) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $payments = $dbhelper->getPayments($chat->getId(), $lang);

            $cache = $dbhelper->getCacheDataByChatId($chat->getId());

            $products = $dbhelper->getCart($chat->getId());

            $params = BKeyboard::calendar($chat, $data->y, $data->m, $data->pickup, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        } elseif ($data->s == BCommon::STATUS_TIME) {

            if (strtotime($data->y.'-'.$data->m.'-'.$data->d) < strtotime(date("Y-m-d"))) {
                $params = BKeyboard::alert($callback, $languages[$lang]["msg_calendar_old_date"]);

                try {
                    $telegram->answerCallbackQuery($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }
                die();
            }
            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $man = $dbhelper->getManufacturer($chat->getId());

            if (strtotime($data->y."-".$data->m."-".$data->d) == strtotime(date("Y-m-d"))) {
                $work_start = BCommon::timeToFloat(date("H:i"), 1.5);
            } else {
                $work_start = BCommon::timeToFloat($man['work_start']);
            }
            $work_finish = BCommon::timeToFloat($man['work_finish']);

            if (isset($data->y)) {
                $dbhelper->updateDeliveryDate($chat->getId(), $data->y."-".$data->m."-".$data->d);
                $params = BKeyboard::alert($callback, $languages[$lang]["msg_date_saved"]);

                try {
                    $telegram->answerCallbackQuery($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }
            }

            $payments = $dbhelper->getPayments($chat->getId(), $lang);

            $products = $dbhelper->getCart($chat->getId());

            /*if (!$work_finish || !$work_start) {
                $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT)
                $params = BKeyboard::location($chat, $lang);
                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }
            } else {*/
                $params = BKeyboard::time($chat, $work_start, $work_finish, $data->pickup, $man, $lang);
                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }
            //}

        } elseif ($data->s == BCommon::STATUS_SEND_CONTACT) {

            $params = BKeyboard::deleteMessage($message, $chat);

            try {
                $telegram->deleteMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

            $dbhelper->updateOrderInfo($chat->getId(), $data->id, $data->delivery_id);

            $params = BKeyboard::contact($chat, $lang);

            try {
                $telegram->sendMessage($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }

        }

    } else if (isset($data->cpage)) {

        $params = BKeyboard::deleteMessage($message, $chat);

        try {
            $telegram->deleteMessage($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

        $categories = $dbhelper->getCategories($data->m, $lang);

        $cache = $dbhelper->getCacheDataByChatId($chat->getId());

        $currentPage = intval($data->cpage);

        if (isset($data->next))
            $currentPage += 1;

        if (isset($data->prev) && $currentPage > 0)
            $currentPage -= 1;

        $params = BKeyboard::categories($chat, $categories, $data->m, $currentPage, $cartCount, $cache, $lang);

        try {
            $telegram->sendMessage($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

    } else if (isset($data->ppage)) {

        $params = BKeyboard::deleteMessage($message, $chat);

        try {
            $telegram->deleteMessage($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

        $products = $dbhelper->getProducts($data->m, $data->c, $lang);

        $currentPage = intval($data->ppage);

        if (isset($data->next))
            $currentPage += 1;

        if (isset($data->prev) && $currentPage > 0)
            $currentPage -= 1;

        $cache = $dbhelper->getCacheDataByChatId($chat->getId());

        $params = BKeyboard::products($chat, $products, $data->m, $data->c, $currentPage, $cartCount, $cache, $lang);

        try {
            $telegram->sendMessage($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

    } else if (isset($data->pl) || isset($data->mi)) {

        $limit = 1000;
        $quantity = intval($data->q);

        if ($quantity < $limit && isset($data->pl))
            $quantity += 1;

        if ($quantity > 1 && isset($data->mi))
            $quantity -= 1;

        $product = $dbhelper->getProduct($data->p, $lang);

        $params = BKeyboard::product($chat, $product, $data->m, $data->c, $quantity, $lang, $message->getMessageId());

        try {
            $telegram->editMessageReplyMarkup($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }

    } else if (isset($data->prev) || isset($data->next)) {
        if ($data->m > 11) {
            $data->y = $data->y+1;
            $data->m = 1;
        } elseif ($data->m < 1) {
            $data->y = $data->y-1;
            $data->m = 12;
        }

        if (strtotime($data->y.'-'.$data->m) < strtotime(date("Y-m"))) {
            $params = BKeyboard::alert($callback, $languages[$lang]["msg_calendar_old_date"]);
            BCommon::errorLog($telegram, $data->y.'-'.$data->m);
            try {
                $telegram->answerCallbackQuery($params);
            } catch (Exception $e) {
                BCommon::errorLog($telegram, $e->getMessage());
            }
            die();
        }

        $params = BKeyboard::calendar($chat, $data->y, $data->m, $data->pickup, $lang, $message->getMessageId());

        try {
            $telegram->editMessageReplyMarkup($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $message);
        }

    } else if (isset($data->noData)) {

        $params = BKeyboard::alert($callback, $languages[$lang]["msg_calendar_incoeerct"]);

        try {
            $telegram->answerCallbackQuery($params);
        } catch (Exception $e) {
            BCommon::errorLog($telegram, $e->getMessage());
        }
    }


} elseif (!is_null($message)) {

    $text = $message->getText();
    $user = $message->getFrom();
    $chat = $message->getChat();
    $location = $message->getLocation();
    $contact = $message->getContact();

    // if user is private so answer to him
    if (strtolower($chat->getType()) === "private") {

        $userObj = $dbhelper->getUser($chat->getId());

        if ($userObj !== null) {

            $lang = $userObj->language_code;
            $pageStatus = $userObj->page_status;
            
        }

        // when send location
        if (!is_null($location)) {

            $params = BKeyboard::hideKeyboard($chat, $lang);

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

                $params = BKeyboard::manufacturers($message, $chat, $cache, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } else {

                $products = $dbhelper->getCart($chat->getId());

                $params = BKeyboard::deliveryMethod($chat, $cache, $products, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            }

        } else if (!is_null($contact)) {

            $dbhelper->updateContact($chat->getId(), $contact);

            $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_COMMENT);

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

                $dbhelper->updateOrInsertUser($user);

                $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } elseif (BCommon::startsWith($text, $emoji["uz"]) || BCommon::startsWith($text, $emoji["ru"])) {

                $lang = "uz";
                if (BCommon::startsWith($text, $emoji["ru"]))
                    $lang = "ru";

                $dbhelper->updateOrInsertUser($user, $lang);

                $params = BKeyboard::location($chat, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } elseif (BCommon::matchPhone($text)) {

                $dbhelper->updatePhone($chat->getId(), $text);

                $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_COMMENT);

                $params = BKeyboard::comment($chat, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } elseif (BCommon::startsWith($text, $emoji["confirm"])) {

                $params = BKeyboard::hideKeyboard($chat, $lang);

                try {
                    $response = $telegram->sendMessage($params);

                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

                $params = BKeyboard::deleteMessage($response, $chat);

                try {
                    $telegram->deleteMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

                $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT);

                $userObj = $dbhelper->getUser($chat->getId());

                $products = $dbhelper->getCart($chat->getId());

                // bosh sahifani ochishi kerak ekan
                $dbhelper->updateCurrentPage(0, $chat->getId());

                $cache = $dbhelper->getCacheDataByChatId($chat->getId());

                $res = BCommon::makeOrder($products, $userObj, $cache);

                if ($res["status"]) {

                    // $messageId = $dbhelper->getLastMessageId($chat->getId());

                    // BCommon::errorLog($telegram, $messageId);

                    // $params = BKeyboard::hideKeyboard($telegram, $chat, $messageId);

                    // try {
                    //     $telegram->editMessageReplyMarkup($params);
                    // } catch (Exception $e) {
                    //     BCommon::errorLog($telegram, $e->getMessage());
                    // }

                    $params = BKeyboard::hideKeyboard($chat, $lang);

                    try {
                        $response = $telegram->sendMessage($params);

                    } catch (Exception $e) {
                        BCommon::errorLog($telegram, $e->getMessage());
                    }

                    $params = BKeyboard::deleteMessage($response, $chat);

                    try {
                        $telegram->deleteMessage($params);
                    } catch (Exception $e) {
                        BCommon::errorLog($telegram, $e->getMessage());
                    }

                    //~~~~~~~~~~~~~~~~~~~~~~~~ PAYMENT CODE ~~~~~~~~~~~~~~~~~~~~~~~~ 

                    $checkSendSuccess = true;
                    $order_code = $res['result']['order']['order_code'];
                    $params = BKeyboard::success($chat, $lang, $order_code);

                    try {

                        $order = $res['result']['order'];
                        $order_summa = $order['total_without_discount'] + $order['delivery_price'];
                        $order_payment_id = $order['payment_id'];
                        $order_identity = $order['order_indentity'];
                        $order_code = $order['order_code'];

                        $dbhelper->successResult($chat->getId(), $res);

                        if ($order_payment_id == 21 || $order_payment_id == 19) {
                            $invoice = [];
                            $invoice['chat_id'] = $chat->getId();
                            $invoice['title'] = $languages[$lang]["order_payment_title"];
                            $invoice['description'] = $languages[$lang]["order_payment_desc"] . " #".$order_code;
                            $invoice['is_flexible'] = false;

                            switch ($order_payment_id) {
                                case "21":
                                    {
                                        $invoice_payload = 'payme:' . $order_identity . ':' . $order_code;
                                        $provider = "payme";
                                        $invoice['payload'] = $invoice_payload;
                                        $invoice['photo_url'] = "https://cdn.paycom.uz/documentation_assets/payme_02.png";
                                        $invoice['photo_width'] = 550;
                                        $invoice['photo_height'] = 550;
                                        $invoice['provider_token'] = BOT_PAYMENT_PAYME_TOKEN;
                                    }
                                    break;
                                case "19":
                                    {
                                        $invoice_payload = 'click:' . $order_identity . ':' . $order_code;
                                        $provider = "click";
                                        $invoice['payload'] = $invoice_payload;
                                        $invoice['photo_url'] = "http://tom.uz/uploads/partner-logo/TT/TT/Tu/1439525718.png";
                                        $invoice['photo_width'] = 408;
                                        $invoice['photo_height'] = 153;
                                        $invoice['provider_token'] = BOT_CLICK_PAYME_TOKEN;
                                    }
                                    break;
                            }

                            $invoice['currency'] = 'UZS';
                            $products = $dbhelper->getCart($chat->getId());
                            $prices = [];
                            foreach ($products as $product){
                                $price = $product["quantity"] * $product["price"];
                                $prices[] = [
                                    'label' => $product['name'],
                                    'amount' => (int)((int)$price."00")
                                ];
                            }

                            $prices[] = ['label' => $languages[$lang]['msg_delivery_price'], 'amount' => (int)((int)$order['delivery_price'] . "00")];
                            $invoice['prices'] = $prices;
                            $invoice['start_parameter'] = "bringooplata";

                            $send_invoice = BCommon::sendInvoice(BOT_TOKEN, $invoice);

                            try {

                                $checkSendSuccess = false;

                                $response = $telegram->sendMessage($params);

                            } catch (Exception $e) {
                                BCommon::errorLog($telegram, $e->getMessage());
                            }

                            if ($send_invoice['ok']) {
                                $dbhelper->createBotOrderPayment(array(
                                    'user' => $chat->getId(),
                                    'phone' => $userObj->phone,
                                    'total_amount' => $order_summa,
                                    'invoice_payload' => $invoice_payload,
                                    'order_identity' => $order_identity,
                                    'order_code' => $order_code,
                                    'provider' => $provider,
                                    'message_id' => $send_invoice['result']['message_id']
                                ));
                            } else {
                                BCommon::errorLog($telegram, $send_invoice);
                            }
                        }

                    //~~~~~~~~~~~~~~~~~~~~~~~~ END PAYMENT ~~~~~~~~~~~~~~~~~~~~~~~~ 

                    } catch (Exception $e) {
                        BCommon::errorLog($telegram, $e->getMessage());
                    }

                    $dbhelper->clearCart($chat->getId());

                    if ($checkSendSuccess) {
                        try {
                            $telegram->sendMessage($params);
                        } catch (Exception $e) {
                            BCommon::errorLog($telegram, $e->getMessage());
                        }
                    }

                } else {

                    BCommon::errorLog($telegram, $res);

                }

            } elseif (BCommon::startsWith($text, $emoji["cancel"])) {

                $params = BKeyboard::hideKeyboard($chat, $lang);

                try {
                    $response = $telegram->sendMessage($params);

                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

                $params = BKeyboard::deleteMessage($response, $chat);

                try {
                    $telegram->deleteMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

                $dbhelper->updatePageStatus($chat->getId(), BCommon::STATUS_RESTARAUNT);

                $dbhelper->clearCart($chat->getId());

                $dbhelper->cancelResult($chat->getId());

                // bosh sahifani ochishi kerak ekan
                $dbhelper->updateCurrentPage(0, $chat->getId());

                $cache = $dbhelper->getCacheDataByChatId($chat->getId());

                $params = BKeyboard::manufacturers($message, $chat, $cache, $lang);

                try {
                    $telegram->sendMessage($params);
                } catch (Exception $e) {
                    BCommon::errorLog($telegram, $e->getMessage());
                }

            } elseif (BCommon::startsWith($text, $emoji["raisedHand"])) {

                if ($pageStatus == BCommon::STATUS_COMMENT) {

                    $dbhelper->updateComment($chat->getId(), $text);

                    $userObj = $dbhelper->getUser($chat->getId());

                    $payments = $dbhelper->getPayments($chat->getId(), $lang);

                    $products = $dbhelper->getCart($chat->getId());

                    $cache = $dbhelper->getCacheDataByChatId($chat->getId());

                    $params = BKeyboard::confirm($chat, $userObj, $products, $payments, $cache, $lang);

                    try {
                        $response = $telegram->sendMessage($params);

                        // $messageId = $response->getMessageId();
                        // if (isset($messageId))
                        //     $dbhelper->updateLastMessageId($chat->getId(), $messageId);

                    } catch (Exception $e) {
                        BCommon::errorLog($telegram, $e->getMessage());
                    }

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
