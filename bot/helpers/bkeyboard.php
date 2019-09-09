<?php
use Telegram\Bot\Keyboard\Keyboard;

class BKeyboard
{
    public static function stadiums($message, $chat, $stadiums, $page = 0, $lang = "uz")
    {
        global $emoji, $languages;
        $inlineLayout = [];

        if (!is_null($stadiums)) {

            $line = [];
            $limit = 5;
            $start = $page * $limit;
            $limitedData = array_slice($stadiums, $start, $limit);

            foreach ($limitedData as $data) {

                array_push($line, Keyboard::inlineButton ( [ 
                    'text' => $data["name"],
                    'callback_data' => '{"s_id":'.$data["id"].',"p":"fields"}' 
                ] ));

                array_push($inlineLayout, $line);
                $line = [];
            }

        }

        $pagination = [];

        if ($page > 0) {            
            array_push($pagination, Keyboard::inlineButton ( [ 
                'text' => $emoji["leftPointer"] . $emoji["leftPointer"] . $emoji["leftPointer"],
                'callback_data' => '{"p" : "stadiums", "page" : '. $page + 1 .'}' 
            ] ));
        }

        if ($page < count($stadiums)/5) {
            array_push($pagination, Keyboard::inlineButton ( [ 
                'text' => $emoji["rightPointer"] . $emoji["rightPointer"] . $emoji["rightPointer"],
                'callback_data' => '{"p" : "stadiums", "page":'. $page - 1 .'}' 
            ] ));            
        }

        array_push($inlineLayout, $pagination);


        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [ 
            'inline_keyboard' => $inlineLayout
        ] );

        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => "Kerakli stadionni tanlang",
            'message_id' => $message->getMessageId(),
            'reply_markup' => $reply_markup 
        ];

        return $params;
    }

    public function fields($message, $chat, $fields, $lang = "uz")
    {
        global $emoji, $languages;

        $inlineLayout = [
            [
                Keyboard::inlineButton ( [ 
                    'text' => $emoji["back"] . "Orqaga",
                    'callback_data' => '{"p":"stadiums"}' 
                ] )
            ]
        ];

        if (!is_null($fields)) {

            $line = [];
            foreach ($fields as $data) {
                array_push($line, Keyboard::inlineButton ( [ 
                    'text' => $data["name"],
                    'callback_data' => '{"s":'.$data["id"].',"p":"field"}' 
                ] ));

                array_push($inlineLayout, $line);
                $line = [];
            }

        }

        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [ 
            'inline_keyboard' => $inlineLayout
        ] );

        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => "Kerakli maydonni tanlang",
            'message_id' => $message->getMessageId(),
            'reply_markup' => $reply_markup 
        ];

        return $params;
    }

    public function field($message, $chat, $field, $lang = "uz")
    {
        global $emoji, $languages;

        $inlineLayout = [
            [
                Keyboard::inlineButton ( [ 
                    'text' => "Orqaga",
                    'callback_data' => '{"p":"fields"}' 
                ] )
            ],
            [
                Keyboard::inlineButton ( [ 
                    'text' => "Buyurtma qilish",
                    'callback_data' => '{"p":"stadiums"}' 
                ] )
            ]
        ];

        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [ 
            'inline_keyboard' => $inlineLayout
        ] );

        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => "Kerakli maydonni tanlang",
            'message_id' => $message->getMessageId(),
            'reply_markup' => $reply_markup 
        ];

        return $params;
    }

    public static function alert($callback, $msg, $type = false)
    {

        return [
            'callback_query_id' => $callback->getId(),
            'text' => $msg,
            'show_alert' => $type,
        ];
    }

    public static function success($chat, $lang = "uz", $order_code)
    {
        global $languages, $emoji;

        $buttonInlineLayout = [];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~ 

        $text .= $emoji['partyPopper'] . $languages[$lang]["msg_confirm_success"] . $emoji['partyPopper'];
        $text .= "\n" . $emoji['key'] . $languages[$lang]["msg_order_number"] . $order_code;
        $text .= "\n\n" . $emoji['downHand'] . $languages[$lang]["msg_new_order_again"];

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $languages[$lang]["new_order"],
            'callback_data' => '{"p":'. BCommon::STATUS_RESTARAUNT .', "back" : true}'
        ]));
        
        array_push($buttonInlineLayout, $buttons);
        $buttons = [];

        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [
            'inline_keyboard' => $buttonInlineLayout
        ]); 
        
        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => $text,
            'parse_mode' => "HTML",
            'reply_markup' => $reply_markup
        ];

        return $params;
    }

    public static function hideKeyboard($chat, $lang = "uz")
    {
        $reply_markup = Keyboard::make ( [ 
            'hide_keyboard' => true,
            'selective'     => false,
        ] );

        $params = [
            'chat_id' => $chat->getId(), 
            'text' => "Ваш местоположения",
            'reply_markup' => $reply_markup
        ];

        return $params;
    }

    public static function deleteMessage($message, $chat)
    {
        $params = [
            'message_id' => $message->getMessageId(),
            'chat_id' => $chat->getId(), 
        ];        

        return $params;
    }

    public static function backToHome($chat, $man_id, $lang = "uz")
    {
        global $languages;

        $buttonInlineLayout = [];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~

        $text .= " <b>" . $languages[$lang]["msg_back_to_home"] . "</b>\n\n";

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $languages[$lang]["stay"],
            'callback_data' => '{"p":'. BCommon::STATUS_CATEGORY .', "back" : true, "m" :'. $man_id .'}'
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => $languages[$lang]["back_to_home"],
            'callback_data' => '{"p":'. BCommon::STATUS_RESTARAUNT .', "back" : true, "backToHome" : true}'
        ]));

        array_push($buttonInlineLayout, $buttons);
        $buttons = [];

        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [
            'inline_keyboard' => $buttonInlineLayout
        ]); 
        
        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => $text,
            'parse_mode' => "HTML",
            'reply_markup' => $reply_markup
        ];

        return $params;
    }

    public static function calendar($chat, $year, $month, $pickup, $lang = "uz", $message_id = 0)
    {
        global $emoji, $languages;

        $year = (int)($year);
        $month = (int)($month);

        if ($month > 11) {
            $year = $year+1;
            $month = 1;
        } elseif ($month < 1) {
            $year = $year-1;
            $month = 12;
        }

        $running_day = date('w',mktime(0,0,0,$month,(int)date("d"),$year));
        $days_in_month = date('t',mktime(0,0,0,$month,(int)date("d"),$year));
        $week_days = 1;

        $buttonInlineLayout = [];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~

        $text = $languages[$lang]['select_date'];

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~
        $title = $languages[$lang]['month'][(int)($month)-1] . " " . $year;
        $buttons = [];
        array_push($buttons, Keyboard::inlineButton([
            'text' => $title,
            'callback_data' => '{"noData" : 1}'
        ]));

        array_push($buttonInlineLayout, $buttons);
        $buttons = [];

        foreach ($languages[$lang]['week'] as $day) {
            array_push($buttons, Keyboard::inlineButton([
                'text' => $day,
                'callback_data' => '{"noData" : 1}'
            ]));
        }

        array_push($buttonInlineLayout, $buttons);
        $buttons = [];

        for ($i=1; $i < $running_day; $i++) {
            array_push($buttons, Keyboard::inlineButton([
                'text' => " ",
                'callback_data' => '{"noData" : 1}'
            ]));

            $week_days++;
        }

        for ($i=1; $i <= $days_in_month; $i++) {
            $day = $i;
            if (strtotime($year."-".$month."-".$i) < strtotime(date("Y-m-d")))
                $day = "🔐";

            if ($week_days%7==0) {
                array_push($buttons, Keyboard::inlineButton([
                    'text' => $day,
                    'callback_data' => '{"p" : ' . BCommon::STATUS_TIME . ', "pickup" : ' . $pickup . ', "y" : ' . $year . ', "m" : ' . $month . ', "d" : ' . $i . '}'
                ]));
                if ($i != $days_in_month) {
                    array_push($buttonInlineLayout, $buttons);
                    $buttons = [];
                }
            } else {
                array_push($buttons, Keyboard::inlineButton([
                    'text' => $day,
                    'callback_data' => '{"p" : ' . BCommon::STATUS_TIME . ', "pickup" : ' . $pickup . ', "y" : ' . $year . ', "m" : ' . $month . ', "d" : ' . $i . '}'
                ]));
            }
            $week_days++;
        }

        if ($week_days%7 > 1)

            for ($i=$week_days%7; $i < 8; $i++) {
                array_push($buttons, Keyboard::inlineButton([
                    'text' => " ",
                    'callback_data' => '{"noData" : 1}'
                ]));
            }

        elseif ($week_days%7 == 0) {

            array_push($buttons, Keyboard::inlineButton([
                'text' => " ",
                'callback_data' => '{"noData" : 1}'
            ]));

        }
        $prev = "<";
        if (strtotime($year."-".$month) == strtotime(date("Y-m"))) {
            $prev = "🔐";
        }
        array_push($buttonInlineLayout, $buttons);
        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $prev,
            'callback_data' => '{"prev" : 1, "pickup" : ' . $pickup . ', "y" : ' . $year . ', "m" : ' . ($month-1) . '}'
        ]));
        array_push($buttons, Keyboard::inlineButton([
            'text' => ">",
            'callback_data' => '{"next" : 1, "pickup" : ' . $pickup . ', "y" : ' . $year . ', "m" : ' . ($month+1) . '}'
        ]));

        array_push($buttonInlineLayout, $buttons);
        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . " " . $languages[$lang]["back"],
            'callback_data' => '{"p":'. BCommon::STATUS_DELIVERY_TYPE .',"pickup" : ' . $pickup . '}'
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => "На сегодня ➡️",
            'callback_data' => '{"p" : ' . BCommon::STATUS_TIME . ', "pickup" : ' . $pickup . ', "y" : ' . (int)date("Y") . ', "m" : ' . (int)date("m") . ', "d" : ' . (int)date("d") . '}'
        ]));

        array_push($buttonInlineLayout, $buttons);
        $buttons = [];

        $reply_markup = Keyboard::make ( [
            'inline_keyboard' => $buttonInlineLayout
        ]);

        $params = [
            'chat_id' => $chat->getId(),
            'text' => $text,
            'parse_mode' => "HTML",
            'reply_markup' => $reply_markup
        ];

        if ($message_id > 0)
            $params = [
                'chat_id' => $chat->getId(),
                'message_id' => $message_id,
                'text' => "edited",
                'parse_mode' => "HTML",
                'reply_markup' => $reply_markup
            ];

        return $params;
    }

    public static function time($chat, $start, $end, $pickup, $man, $lang = "uz")
    {
        global $emoji, $languages;

        $buttonInlineLayout = [];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~

        $text = $languages[$lang]['select_time'];
        $text2 = "\n" . "Время работы: " . substr($man['work_start'], 0, 5) . " - " . substr($man['work_finish'], 0, 5);

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~

        $buttons = [];
        for ($i = $start; $i < $end; $i+=0.5) {
            $time = BCommon::floatToTime($i);
            $hi = BCommon::floatToTime($i, "array");
            array_push($buttons, Keyboard::inlineButton([
                'text' => $time,
                'callback_data' => '{"p" : ' . BCommon::STATUS_PAYMENT . ', "pickup" : ' . $pickup . ', "urgently" : 0, "h" : ' . $hi[0] . ', "i" : "' . $hi[1] . '"}'
            ]));
        }
        if (empty($buttons))
            $text = $emoji['exclamation_mark'] . $languages[$lang]['msg_cafe_closed'];
        else
            $buttonInlineLayout = array_chunk($buttons, 4);
        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"p":'. BCommon::STATUS_CLAENDAR .', "pickup" : ' . $pickup . ', "y" : ' . date("Y") . ' , "m" : ' . (int)date("m") . '}'
        ]));

        array_push($buttonInlineLayout, $buttons);
        $buttons = [];

        $reply_markup = Keyboard::make ( [
            'inline_keyboard' => $buttonInlineLayout
        ]);

        $params = [
            'chat_id' => $chat->getId(),
            'text' => $text . $text2,
            'parse_mode' => "HTML",
            'reply_markup' => $reply_markup
        ];

        return $params;
    }
}

?>