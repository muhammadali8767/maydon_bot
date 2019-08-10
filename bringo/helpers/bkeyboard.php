<?php

use Telegram\Bot\Keyboard\Keyboard;

/**
* @author @CleverUzbek
*/
class BKeyboard
{

    public static function languages($chat, $user)
    {
        global $emoji, $languages;

        $text = "Assalomu alaykum!\nЗдравствуйте, *" . str_replace ( "*", "x", $user->getFirstName() ) . "!*";
        $text .= "\n" . $emoji ['uz'] . " Tilni tanlang";
        $text .= "\n" . $emoji ['ru'] . " Выберите язык";

        $buttonLayout = [
            [
                Keyboard::button([
                    'text' => $emoji["uz"]. " " .$languages["uz"]["language"]. " " .$emoji["uz"]
                ]),
            ],
            [
                Keyboard::button([
                    'text' => $emoji["ru"]. " " .$languages["ru"]["language"]. " " .$emoji["ru"]
                ])
            ]
        ];

        $keyboard = Keyboard::make ( [
            'keyboard' => $buttonLayout,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        $params = [
            'chat_id' => $chat->getId(),
            'text' => $text,
            'parse_mode' => "Markdown",
            'reply_markup' => $keyboard
        ];

        return $params;
    }

    public static function location($chat, $lang = "ru")
    {
        global $emoji, $languages;

        $keyboardLayot = [
            [
                Keyboard::button ( [
                    'text' => $emoji["location"] . $languages[$lang]["send_location"],
                    'request_location' => true
                ] )
            ]
        ];

        $keyboard = Keyboard::make ( [
            'keyboard' => $keyboardLayot,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ] );

        $params = [
            'text' => $languages[$lang]["msg_send_location"],
            'chat_id' => $chat->getId(),
            'parse_mode' => "Markdown",
            'reply_markup' => $keyboard
        ];

        return $params;
    }

    public static function deliveryTerms($chat, $lang = "ru")
    {
        global $emoji, $languages;

        $text = $emoji ['delivery'] . $languages[$lang]["text_delivery_terms"];
        
        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => $text,
            'parse_mode' => "Markdown" 
        ];

        return $params;
    }

    public static function manufacturers($message, $chat, $cache, $lang = "ru")
    {
        global $emoji, $languages;

        $decode = json_decode($cache->json_code, true);

        $inlineLayout = [
            // [
            //     Keyboard::inlineButton ( [ 
            //         'text' => $emoji['deliveryTerms']. " " .$languages[$lang]["msg_delivery_terms"],
            //         'callback_data' => '{"delivery_terms" : true}'
            //     ] )
            // ],
            [
                Keyboard::inlineButton ( [ 
                    'text' => $emoji["location"] . $languages[$lang]["send_new_location"],
                    'callback_data' => '{"location" : true}'
                ] )
            ]
        ];

        if (!is_null($decode)) {

            $line = [];
            $limit = 5;
            $start = $cache->current_page * $limit;
            $limitedData = array_slice($decode, $start, $limit);

            foreach ($limitedData as $data) {

                array_push($line, Keyboard::inlineButton ( [ 
                    'text' => $data["name"],
                    'callback_data' => '{"m":'.$data["manId"].',"s":'. BCommon::STATUS_RESTARAUNT .'}' 
                ] ));

                array_push($inlineLayout, $line);
                $line = [];
            }

        }

        $pagination = [];

        if ($cache->current_page > 0) {
            
            array_push($pagination, Keyboard::inlineButton ( [ 
                'text' => $emoji["leftPointer"] . $emoji["leftPointer"] . $emoji["leftPointer"],
                'callback_data' => '{"prev" : true, "page" : '. $cache->current_page .'}' 
            ] ));

        }

        if ($cache->current_page < 4) {

            array_push($pagination, Keyboard::inlineButton ( [ 
                'text' => $emoji["rightPointer"] . $emoji["rightPointer"] . $emoji["rightPointer"],
                'callback_data' => '{"next":true,"page":'. $cache->current_page .'}' 
            ] ));
            
        }

        array_push($inlineLayout, $pagination);


        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [ 
            'inline_keyboard' => $inlineLayout
        ] );

        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => $languages[$lang]["msg_manufacturers"]." ".$emoji["downHand"],
            'message_id' => $message->getMessageId(),
            'reply_markup' => $reply_markup 
        ];

        return $params;
    }

    public static function categories($chat, $rows, $man_id, $page = 0, $cart = 0, $cache, $lang = "ru")
    {
        global $emoji, $languages;

        $decode = json_decode($cache->json_code, true);

        $limit = 5;
        $startIndex = intval($page) * $limit; 
        $countRows = count($rows);

        $items = array_slice($rows, $startIndex, $limit);
        $buttons = [];
        $buttonInlineLayout = [];

        $cartStr = ($cart != 0) ? " (". $cart .")" : "";

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~

        $som = "so'm";
        if ($lang == "ru")
            $som = "сум";        

        $name = $emoji["castle"] . " " . $decode[$man_id]["name"];
        $price = "\n" . $emoji["car"] . " " . BCommon::numberFormat($decode[$man_id]["price"]);

        $text = "<b>$name $price $som" . "</b>\n\n";
        $text .= $languages[$lang]["msg_categories"]." ".$emoji["downHand"];

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTON ~~~~~~~~~~~~~~~~~~ 

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"s" : '. BCommon::STATUS_RESTARAUNT .', "back" : true, "m" : '. $man_id.'}'
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['lookup'] . $languages[$lang]["cart"] . $cartStr,
            'callback_data' => '{"s" : '. BCommon::STATUS_CART .', "cart" : true}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        // ~~~~~~~~~~~~~~~~~~ ADD CATEGORIES ~~~~~~~~~~~~~~~~~~
        
        $buttons = [];
        for ($row = 0; $row < count($items); $row ++) {

            array_push($buttons, Keyboard::inlineButton([
                'text' => $emoji["category"] . $items[$row]["name"],
                'callback_data' => '{"m":'. $man_id .',"c":'. $items[$row]["id"] .',"s":'. BCommon::STATUS_CATEGORY .'}'
            ]));

            array_push($buttonInlineLayout, $buttons);
            $buttons = [];
        }


        // ~~~~~~~~~~~~~~~~~~ ADD PAGINATION ~~~~~~~~~~~~~~~~~~

        $pagination = [];

        if ($page > 0)
            array_push($pagination, Keyboard::inlineButton ( [ 
                'text' => $emoji["leftPointer"] . $emoji["leftPointer"] . $emoji["leftPointer"],
                'callback_data' => '{"m":'. $man_id .',"prev":true,"cpage":'. $page .'}' 
            ] ));

        if ($countRows - (intval($page) * $limit + $limit) > 0)
            array_push($pagination, Keyboard::inlineButton ( [ 
                'text' => $emoji["rightPointer"] . $emoji["rightPointer"] . $emoji["rightPointer"],
                'callback_data' => '{"m":'. $man_id .',"next":true,"cpage":'. $page .'}'
            ] ));

        array_push($buttonInlineLayout, $pagination);


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

    public static function products($chat, $rows, $man_id, $cat_id, $page = 0, $cart = 0, $cache, $lang = "ru")
    {
        global $emoji, $languages;

        $limit = 5;
        $startIndex = intval($page) * $limit; 
        $countRows = count($rows);

        $items = array_slice($rows, $startIndex, $limit);
        $buttons = [];
        $buttonInlineLayout = [];

        $cartStr = ($cart != 0) ? " (". $cart .")" : "";

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~

        $decode = json_decode($cache->json_code, true);

        $som = "so'm";
        if ($lang == "ru")
            $som = "сум";        

        $name = $emoji["castle"] . " " . $decode[$man_id]["name"];
        $price = "\n" . $emoji["car"] . " " . BCommon::numberFormat($decode[$man_id]["price"]);

        $text = "<b>$name  $price $som" . "</b>\n\n";
        $text .= $languages[$lang]["msg_products"]." ".$emoji["downHand"];

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTON ~~~~~~~~~~~~~~~~~~ 

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"s":'. BCommon::STATUS_CATEGORY .',"back":true,"m":'. $man_id .'}'
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['lookup'] . $languages[$lang]["cart"] . $cartStr,
            'callback_data' => '{"s" : '. BCommon::STATUS_CART .', "cart" : true}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        // ~~~~~~~~~~~~~~~~~~ ADD PRODUCTS ~~~~~~~~~~~~~~~~~~ 
        
        $buttons = [];
        for ($row = 0; $row < count($items); $row ++) {

            array_push($buttons, Keyboard::inlineButton([
                'text' => $emoji["product"] . $items[$row]["name"],
                'callback_data' => '{"m":'. $man_id .',"c":'. $cat_id .',"p":'. $items[$row]["id"] .',"s":'. BCommon::STATUS_PRODUCT .'}'
            ]));

            array_push($buttonInlineLayout, $buttons);
            $buttons = [];

        }

        $pagination = [];
        
        if ($page > 0)
            array_push($pagination, Keyboard::inlineButton ( [ 
                'text' => $emoji["leftPointer"] . $emoji["leftPointer"] . $emoji["leftPointer"],
                'callback_data' => '{"m":'. $man_id .',"c":'. $cat_id .',"prev":true,"ppage":'. $page .'}'
            ] ));

        if ($countRows - (intval($page) * $limit + $limit) > 0)
            array_push($pagination, Keyboard::inlineButton ( [ 
                'text' => $emoji["rightPointer"] . $emoji["rightPointer"] . $emoji["rightPointer"],
                'callback_data' => '{"m":'. $man_id .',"c":'. $cat_id .',"next":true,"ppage":'. $page .'}' 
            ] ));

        array_push($buttonInlineLayout, $pagination);


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

    public static function product($chat, $product, $man_id, $cat_id, $quantity = 1, $lang = "ru", $message_id = 0)
    {
        global $emoji, $languages;

        $buttonInlineLayout = [];

        $som = "so'm";
        if ($lang == "ru")
            $som = "сум";

        $caption = "<b>".$product->name . "</b>\n";
        $caption .= "<i>". $product->ingredients . "</i>\n";
        $caption .= "<b>". BCommon::numberFormat($product->price) . " " . $som . "</b>";

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton ( [ 
            'text' => "-",
            'callback_data' => '{"mi":1,"q":'.$quantity.',"m":'. $man_id .',"p":'. $product->id .',"c":'. $cat_id .'}'
        ] ));

        array_push($buttons, Keyboard::inlineButton ( [ 
            'text' => $quantity,
            'callback_data' => '{"res":0}'
        ] ));

        array_push($buttons, Keyboard::inlineButton ( [ 
            'text' => "+",
            'callback_data' => '{"pl":1,"q":'.$quantity.',"m":'. $man_id .',"p":'. $product->id .',"c":'. $cat_id .'}'
        ] ));

        array_push($buttonInlineLayout, $buttons);


        // ~~~~~~~~~~~~~~~~~~ ADD BOTTOM BUTTONS ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];
        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"s":'. BCommon::STATUS_PRODUCT .',"back":true,"m":'. $man_id .',"c":'. $cat_id .'}'
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['lookup'] . $languages[$lang]["add_to_cart"],
            'callback_data' => '{"s":'. BCommon::STATUS_ADD_CART .',"add":true,"m":'. $man_id .',"c":'. $cat_id .',"p":'. $product->id .',"q":'. $quantity .'}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [
            'inline_keyboard' => $buttonInlineLayout
        ]);
        
        $params = [ 
            'chat_id' => $chat->getId (),
            'photo' => $product->photo,
            'caption' => $caption,
            'parse_mode' => "HTML",
            'reply_markup' => $reply_markup
        ];

        if ($message_id > 0)
            $params = [ 
                'chat_id' => $chat->getId (),
                'message_id' => $message_id,
                'reply_markup' => $reply_markup
            ];

        return $params;
    }

    public static function cart($chat, $products, $cache, $lang = "ru")
    {
        global $emoji, $languages;

        $buttonInlineLayout = [];
        $man_id = $products[0]["manufacturer_id"];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~ 

        $decode = json_decode($cache->json_code, true);

        $som = "so'm";
        if ($lang == "ru")
            $som = "сум";

        $name = $emoji["castle"] . " " . $decode[$man_id]["name"];
        $price = "\n" . $emoji["car"] . " " . BCommon::numberFormat($decode[$man_id]["price"]);

        $text = "<b>$name  $price $som" . "</b>\n\n";

        $text .= $emoji["pushpin"] . " <b>".$languages[$lang]["msg_we_cart"] . ":</b>\n\n";

        $totalPrice = 0;
        $price = 0;
        foreach ($products as $index => $product) {
            $price = $product["quantity"] * $product["price"];
            $text .= ($index + 1) .". <b>". $product["name"] . "</b>:\n";
            $text .= "    <i>". $product["quantity"] ." x ". BCommon::numberFormat($product["price"]) ." ". $som ." = ". BCommon::numberFormat($price) ." ". $som ."</i>\n";
            $totalPrice += $price;
        }
        $text .= "\n<b>".$languages[$lang]["msg_total_price"] . ": ". BCommon::numberFormat($totalPrice) . " " . $som . "</b>";

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"s":'. BCommon::STATUS_RESTARAUNT .',"m":'. $man_id .'}'
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['order'] . $languages[$lang]["make_order"],
            'callback_data' => '{"s":'. BCommon::STATUS_DELIVERY_METHOD .'}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        // ~~~~~~~~~~~~~~~~~~ ADD CART PRODUCT ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];

        foreach ($products as $index => $product) {

            array_push($buttons, Keyboard::inlineButton ( [ 
                'text' => ($index + 1) . ". ". $product["name"]. " (" .$product["quantity"]. ") ". $emoji["remove"],
                'callback_data' => '{"id":'. $product["id"] .',"s":'. BCommon::STATUS_CART .',"d":1}'
            ] ));

            array_push($buttonInlineLayout, $buttons);
            
            $buttons = [];
        }

        // ~~~~~~~~~~~~~~~~~~ ADD CLEAR BUTTON ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['clear'] . $languages[$lang]["clear_cart"],
            'callback_data' => '{"s":'. BCommon::STATUS_RESTARAUNT .',"back":true,"m":'. $man_id .'}'
        ]));

        array_push($buttonInlineLayout, $buttons);


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

    public static function deliveryMethod($chat, $cache, $products, $lang = "ru")
    {
        global $emoji, $languages;

        $buttonInlineLayout = [];

        $man_id = $products[0]["manufacturer_id"];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~ 

        $decode = json_decode($cache->json_code, true);

        $som = "so'm";
        if ($lang == "ru")
            $som = "сум";        

        $name = $emoji["castle"] . " " . $decode[$man_id]["name"];
        $price = "\n" . $emoji["car"] . " " . BCommon::numberFormat($decode[$man_id]["price"]);

        $text = "<b>$name  $price $som" . "</b>\n\n";

        $text .= $emoji["pushpin"] . " <b>".$languages[$lang]["msg_we_cart"] . ":</b>\n\n";

        $totalPrice = 0;
        $price = 0;
        foreach ($products as $index => $product) {
            $price = $product["quantity"] * $product["price"];
            $text .= ($index + 1) .". <b>". $product["name"] . "</b>:\n";
            $text .= "    <i>". $product["quantity"] ." x ". BCommon::numberFormat($product["price"]) ." ". $som ." = ". BCommon::numberFormat($price) ." ". $som ."</i>\n";
            $totalPrice += $price;
        }
        $text .= "\n<b>".$languages[$lang]["msg_total_price"] . ": ". BCommon::numberFormat($totalPrice) . " " . $som . "</b>";

        $address = BCommon::getAddress($cache->latitude, $cache->longitude);

        $text .= "\n\n". $emoji ['location'] . " <i>" .$address. "</i>";
        $text .= "\n\n". $languages[$lang]['msg_select_delivery_type']." ".$emoji["downHand"];

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"s":'. BCommon::STATUS_CART .',"cart":true}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        // ~~~~~~~~~~~~~~~~~~ ADD BOTTOM BUTTON ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji["car"] . " " . $languages[$lang]["by_courier"],
            'callback_data' => '{"s":'. BCommon::STATUS_DELIVERY_TYPE .',"pickup" : 0}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['takeAway'] . $languages[$lang]["pickup"],
            'callback_data' => '{"s":'. BCommon::STATUS_DELIVERY_TYPE .',"pickup" : 1}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        $buttons = [];

        array_push($buttonInlineLayout, $buttons);

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['location'] . $languages[$lang]["send_new_location"],
            'callback_data' => '{"s":'. BCommon::STATUS_DELIVERY_METHOD .',"location":true}'
        ]));

        array_push($buttonInlineLayout, $buttons);

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

    public static function deliveryType($chat, $cache, $products, $pickup, $lang = "ru")
    {
        global $emoji, $languages;

        $buttonInlineLayout = [];

        $man_id = $products[0]["manufacturer_id"];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~ 

        $decode = json_decode($cache->json_code, true);

        $som = "so'm";
        if ($lang == "ru")
            $som = "сум";

        $name = $emoji["castle"] . " " . $decode[$man_id]["name"];
        $price = "\n" . $emoji["car"] . " " . BCommon::numberFormat($decode[$man_id]["price"]);

        $text = "<b>$name  $price $som" . "</b>\n\n";

        $text .= $emoji["pushpin"] . " <b>".$languages[$lang]["msg_we_cart"] . ":</b>\n\n";

        $totalPrice = 0;
        $price = 0;
        foreach ($products as $index => $product) {
            $price = $product["quantity"] * $product["price"];
            $text .= ($index + 1) .". <b>". $product["name"] . "</b>:\n";
            $text .= "    <i>". $product["quantity"] ." x ". BCommon::numberFormat($product["price"]) ." ". $som ." = ". BCommon::numberFormat($price) ." ". $som ."</i>\n";
            $totalPrice += $price;
        }
        $text .= "\n<b>".$languages[$lang]["msg_total_price"] . ": ". BCommon::numberFormat($totalPrice) . " " . $som . "</b>";

        $address = BCommon::getAddress($cache->latitude, $cache->longitude);

        $text .= "\n\n". $emoji ['location'] . " <i>" .$address. "</i>";
        $text .= "\n\n". $languages[$lang]['msg_select_delivery_time']." ".$emoji["downHand"];

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"s":'. BCommon::STATUS_DELIVERY_METHOD .',"cart":true}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        // ~~~~~~~~~~~~~~~~~~ ADD BOTTOM BUTTON ~~~~~~~~~~~~~~~~~~

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['urgant'] . $languages[$lang]["urgant"],
            'callback_data' => '{"s":'. BCommon::STATUS_PAYMENT .', "pickup" : ' . $pickup . ', "urgently" : 1}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['time'] . $languages[$lang]["in_time"],
            'callback_data' => '{"s":'. BCommon::STATUS_CLAENDAR .', "pickup" : ' . $pickup . ', "y" : ' . date("Y") . ' , "m" : ' . (int)date("m") . '}'
        ]));

        array_push($buttonInlineLayout, $buttons);

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['location'] . $languages[$lang]["send_new_location"],
            'callback_data' => '{"s":'. BCommon::STATUS_DELIVERY_METHOD .',"location":true}'
        ]));

        array_push($buttonInlineLayout, $buttons);

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

    public static function payments($chat, $payments, $delivery_id, $cache, $products, $date, $time, $lang = "ru")
    {
        global $emoji, $languages;

        $buttonInlineLayout = [];

        $man_id = $products[0]["manufacturer_id"];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~

        $decode = json_decode($cache->json_code, true);

        $deliveryPrice = $delivery_id == 15 ? "" : $decode[$man_id]["price"];

        $som = "so'm";
        if ($lang == "ru")
            $som = "сум";

        $name = $emoji["castle"] . " " . $decode[$man_id]["name"];
        $price = "\n" . (($delivery_id == 16 || $delivery_id == 14) ?  $emoji["car"] . " " . BCommon::numberFormat($decode[$man_id]["price"]) . " " .  $som . "\n" : "" );

        $text = "<b> $name $price </b>\n";

        $text .= $emoji["pushpin"] . " <b>".$languages[$lang]["msg_we_cart"] . ":</b>\n\n";

        $totalPrice = 0;
        $price = 0;
        foreach ($products as $index => $product) {
            $price = $product["quantity"] * $product["price"];
            $text .= ($index + 1) .". <b>". $product["name"] . "</b>:\n";
            $text .= "    <i>". $product["quantity"] ." x ". BCommon::numberFormat($product["price"]) ." ". $som ." = ". BCommon::numberFormat($price) ." ". $som ."</i>\n";
            $totalPrice += $price;
        }
        $text .= "\n<b>".$languages[$lang]["msg_total_price"] . ": ". BCommon::numberFormat($totalPrice) . " " . $som . "</b>";
        if (!empty($deliveryPrice)) {
            $text .= "\n<b>".$languages[$lang]["msg_delivery_price"] . ":</b> <i>". BCommon::numberFormat($deliveryPrice) . " " . $som ."</i>";

            $text .= "\n<b>".$languages[$lang]["msg_total_price_delivery"] . ":</b> <i>". BCommon::numberFormat($totalPrice + $deliveryPrice) . " " . $som ."</i>";
        }
        if ($date != "0000-00-00") {
            $text .= "\n\n<b>".$languages[$lang]["confirm_delivery_date"] . ": ".  $date . "</b>";
            $text .= "\n<b>".$languages[$lang]["confirm_delivery_time"] . ": ". substr($time, 0, 5) . "</b>";
        }

        $address = BCommon::getAddress($cache->latitude, $cache->longitude);

        $text .= "\n\n". $emoji ['location'] . " <i>" .$address. "</i>";


        $text .= "\n\n" . $languages[$lang]["msg_payment_method"]." ".$emoji["downHand"];

        // ~~~~~~~~~~~~~~~~~~ ADD CART PRODUCT ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];

        foreach ($payments as $index => $payment) {

            $cash = $emoji["card"];
            if ($index == 0) {
                $cash = $emoji["cash"];

                array_push($buttons, Keyboard::inlineButton ( [
                    'text' => $cash . $payment["name"],
                    'callback_data' => '{"id":'. $payment["id"] .',"s":'. BCommon::STATUS_SEND_CONTACT .',"delivery_id":'. $delivery_id .'}'
                ] ));
                array_push($buttonInlineLayout, $buttons);
                $buttons = [];
            } else {

                array_push($buttons, Keyboard::inlineButton ( [
                    'text' => $cash . $payment["name"],
                    'callback_data' => '{"id":'. $payment["id"] .',"s":'. BCommon::STATUS_SEND_CONTACT .',"delivery_id":'. $delivery_id .'}'
                ] ));

                if ($index % 2 != 1) {
                    array_push($buttonInlineLayout, $buttons);
                    $buttons = [];
                }
            }

        }

        if (count($payments) % 2 != 1)
            array_push($buttonInlineLayout, $buttons);

        // ~~~~~~~~~~~~~~~~~~ ADD BACK BUTTON ~~~~~~~~~~~~~~~~~~ 

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"s":'. BCommon::STATUS_DELIVERY_METHOD .'}'
        ]));

        array_push($buttonInlineLayout, $buttons);

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

    public static function contact($chat, $lang = "ru")
    {
        global $emoji, $languages;

        $keyboardLayot = [];
        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['mobile'] . $languages[$lang]["send_contact"],
            'request_contact' => true
        ]));

        array_push($keyboardLayot, $buttons);

        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [
            'keyboard' => $keyboardLayot,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]); 

        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => $emoji["mobile"].$languages[$lang]["msg_contact"]." ".$emoji["downHand"],
            'reply_markup' => $reply_markup 
        ];

        return $params;
    }

    public static function confirm($chat, $obj, $products, $payments, $cache, $lang = "ru")
    {
        global $emoji, $languages;

        $decode = json_decode($cache->json_code, true);

        $keyboardLayot = [];

        $manId = $products[0]["manufacturer_id"];

        $deliveryPrice = $obj->delivery_id == 15 ? "" : $decode[$manId]["price"];

        // ~~~~~~~~~~~~~~~ MESSAGE ~~~~~~~~~~~~~~~

        $price = 0;
        $totalPrice = 0;
        $som = "so'm";
        if ($lang == "ru")
            $som = "сум";

        $paymentName = "Naqt";
        foreach ($payments as $payment) {
            if ($payment["id"] == $obj->payment_id)
                $paymentName = $payment["name"];
        }

        $text = "<b>". $languages[$lang]["msg_we_cart"] .":</b>";
        $text .= "\n<b>". $languages[$lang]["confirm_name"] .":</b> <i>" . $obj->first_name . " ".$obj->last_name ."</i>";
        $text .= "\n<b>". $languages[$lang]["confirm_phone"] .":</b> <i>" . $obj->phone ."</i>";
        $text .= "\n<b>". $languages[$lang]["confirm_delivery_method"] .":</b> <i>". $paymentName ."</i>";
        $text .= ($obj->delivery_id == 14) ? "\n<b>" . $languages[$lang]['confirm_delivery_date'] . ":</b> <i>". $obj->deliveryDate . "</i>" : "" ;
        $text .= ($obj->delivery_id == 14) ? "\n<b>" . $languages[$lang]['confirm_delivery_time'] . ":</b> <i>". substr($obj->deliveryTime, 0, 5) ."</i>" : "" ;
        $text .= "\n<i>". $decode[$manId]["name"] ."</i>\n\n";

        foreach ($products as $index => $product) {
            $price = $product["quantity"] * $product["price"];
            $text .= ($index + 1) . ". <b>". $product["name"] . "</b>:\n";
            $text .= "    <i>". $product["quantity"] ." x ". BCommon::numberFormat($product["price"]) ." ". $som ." = ". BCommon::numberFormat($price) ." ". $som ."</i>\n";
            $totalPrice += $price;
        }

        $text .= "\n<b>".$languages[$lang]["msg_total_price"] . ":</b> <i>". BCommon::numberFormat($totalPrice) . " " . $som ."</i>";

        if (!empty($deliveryPrice)) {
            $text .= "\n<b>".$languages[$lang]["msg_delivery_price"] . ":</b> <i>". BCommon::numberFormat($deliveryPrice) . " " . $som ."</i>";

            $text .= "\n<b>".$languages[$lang]["msg_total_price_delivery"] . ":</b> <i>". BCommon::numberFormat($totalPrice + $deliveryPrice) . " " . $som ."</i>";
        }

        $text .= "\n\n". $languages[$lang]["msg_confirm"]." ".$emoji["downHand"];
        // ~~~~~~~~~~~~~~~ BUTTONS ~~~~~~~~~~~~~~~

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['cancel'] . $languages[$lang]["cancel_order"],
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['confirm'] . $languages[$lang]["confirm_order"],
        ]));

        array_push($keyboardLayot, $buttons);

        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [
            'keyboard' => $keyboardLayot,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]); 

        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup 
        ];

        return $params;
    }

    public static function comment($chat, $lang = "ru")
    {
        global $emoji, $languages;

        $keyboardLayot = [];
        $buttons = [];

        // ~~~~~~~~~~~~~~~ TEXT MESSAGE ~~~~~~~~~~~~~~~        

        $text = $emoji ['pencil'] . " " . $languages[$lang]["text_comment"] ." ".$emoji["downHand"];

        // ~~~~~~~~~~~~~~~ BUTTONS ~~~~~~~~~~~~~~~        

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['raisedHand'] . $languages[$lang]["msg_comment"],
        ]));

        array_push($keyboardLayot, $buttons);

        // ~~~~~~~~~~~~~~~ PARAMS ~~~~~~~~~~~~~~~

        $reply_markup = Keyboard::make ( [
            'keyboard' => $keyboardLayot,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]); 

        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => $text,
            'parse_mode' => "Markdown",
            'reply_markup' => $reply_markup 
        ];

        return $params;
    }

    public static function currentLocation($chat, $lang = "ru")
    {
        global $emoji, $languages;

        $text = $emoji ['location'] . $languages[$lang]["text_current_geolocation"] ." ".$emoji["downHand"];

        $params = [ 
            'chat_id' => $chat->getId (),
            'text' => $text,
            'parse_mode' => "Markdown" 
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

    public static function success($chat, $lang = "ru", $order_code)
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
            'callback_data' => '{"s":'. BCommon::STATUS_RESTARAUNT .', "back" : true}'
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

    public static function hideKeyboard($chat, $lang = "ru")
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

    // public static function hideKeyboard($telegram, $chat, $message_id)
    // {

    //     $reply_markup = $telegram->replyKeyboardHide();

    //     $params = [
    //         'chat_id' => $chat->getId(), 
    //         'message_id' => $message_id,
    //         // 'inline_message_id' => $message_id,
    //         'reply_markup' => $reply_markup
    //     ];

    //     return $params;
    // }

    public static function backToHome($chat, $man_id, $lang = "ru")
    {
        global $languages;

        $buttonInlineLayout = [];

        // ~~~~~~~~~~~~~~~~~~ MAKE TEXT MESSAGE ~~~~~~~~~~~~~~~~~~

        $text .= " <b>" . $languages[$lang]["msg_back_to_home"] . "</b>\n\n";

        // ~~~~~~~~~~~~~~~~~~ ADD TOP BUTTONS ~~~~~~~~~~~~~~~~~~

        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $languages[$lang]["stay"],
            'callback_data' => '{"s":'. BCommon::STATUS_CATEGORY .', "back" : true, "m" :'. $man_id .'}'
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => $languages[$lang]["back_to_home"],
            'callback_data' => '{"s":'. BCommon::STATUS_RESTARAUNT .', "back" : true, "backToHome" : true}'
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

    public static function calendar($chat, $year, $month, $pickup, $lang = "ru", $message_id = 0)
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
                    'callback_data' => '{"s" : ' . BCommon::STATUS_TIME . ', "pickup" : ' . $pickup . ', "y" : ' . $year . ', "m" : ' . $month . ', "d" : ' . $i . '}'
                ]));
                if ($i != $days_in_month) {
                    array_push($buttonInlineLayout, $buttons);
                    $buttons = [];
                }
            } else {
                array_push($buttons, Keyboard::inlineButton([
                    'text' => $day,
                    'callback_data' => '{"s" : ' . BCommon::STATUS_TIME . ', "pickup" : ' . $pickup . ', "y" : ' . $year . ', "m" : ' . $month . ', "d" : ' . $i . '}'
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
            'callback_data' => '{"s":'. BCommon::STATUS_DELIVERY_TYPE .',"pickup" : ' . $pickup . '}'
        ]));

        array_push($buttons, Keyboard::inlineButton([
            'text' => "На сегодня ➡️",
            'callback_data' => '{"s" : ' . BCommon::STATUS_TIME . ', "pickup" : ' . $pickup . ', "y" : ' . (int)date("Y") . ', "m" : ' . (int)date("m") . ', "d" : ' . (int)date("d") . '}'
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

    public static function time($chat, $start, $end, $pickup, $man, $lang = "ru")
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
                'callback_data' => '{"s" : ' . BCommon::STATUS_PAYMENT . ', "pickup" : ' . $pickup . ', "urgently" : 0, "h" : ' . $hi[0] . ', "i" : "' . $hi[1] . '"}'
            ]));
        }
        if (empty($buttons))
            $text = $emoji['exclamation_mark'] . $languages[$lang]['msg_cafe_closed'];
        else
            $buttonInlineLayout = array_chunk($buttons, 4);
        $buttons = [];

        array_push($buttons, Keyboard::inlineButton([
            'text' => $emoji['back'] . $languages[$lang]["back"],
            'callback_data' => '{"s":'. BCommon::STATUS_CLAENDAR .', "pickup" : ' . $pickup . ', "y" : ' . date("Y") . ' , "m" : ' . (int)date("m") . '}'
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