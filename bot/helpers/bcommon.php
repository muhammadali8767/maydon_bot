<?php

use GuzzleHttp\Client;

/**
 * @author @CleverUzbek
 */
class BCommon
{

    const DELIVERY_METHOD_TAKEAWAY = 18;
    const DELIVERY_METHOD_DELIVERY = 19;

    const STATUS_RESTARAUNT = 0;
    const STATUS_CATEGORY = 1;
    const STATUS_PRODUCT = 2;
    const STATUS_ADD_CART = 9;
    const STATUS_CART = 10;
    const STATUS_DELIVERY_METHOD = 11;
    const STATUS_DELIVERY_TYPE = 12;
    const STATUS_CLAENDAR = 13;
    const STATUS_TIME = 14;
    const STATUS_PAYMENT = 15;
    const STATUS_SEND_CONTACT = 16;
    const STATUS_COMMENT = 17;

    const PRODUCT_NO_PHOTO_PATH = FULLPATH . "/images/";
    const PRODUCT_PHOTO_URL = BRINGO_URL . "/uploads/product/";

    public static function sendInvoice($token, $params)
    {
        $client = new Client();
        $response = $client->post("https://api.telegram.org/bot" . $token . "/sendInvoice", [
            GuzzleHttp\RequestOptions::JSON => $params
        ]);
        return json_decode($response->getBody(), true);
    }

    public static function answerPreCheckoutQuery($token, $id, $ok, $data = [])
    {
        $client = new Client();
        $response = $client->post("https://api.telegram.org/bot" . $token . "/answerPreCheckoutQuery", [
            GuzzleHttp\RequestOptions::JSON => array_merge([
                'pre_checkout_query_id' => $id,
                'ok' => $ok,
            ], $data)
        ]);
        return json_decode($response, true);
    }


    public static function getManufacturersByLocation($location)
    {
        $lat = $location->getLatitude();
        $lng = $location->getLongitude();

        $url = BRINGO_URL . "/api/delivaryPrices";

        $params = array(
            'type' => 'array',
            'lat' => $lat,
            'lng' => $lng
        );

        $curl = new curl ();
        $resJson = $curl->send('GET', $url, $params);

        $decode = json_decode($resJson, true);
        $result = $decode["result"];

        //make sort by distance
        uasort($result, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public static function getAddress($latitude, $longitude)
    {
        if (!empty($latitude) && !empty($longitude)) {
            //Send request and receive json data by address
            $geocodeFromLatLong = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($latitude) . ',' . trim($longitude) . '&sensor=false&key=AIzaSyBvUKYW759JfZ2IuPsTSkJauAWkBieqRNk');

            $output = json_decode($geocodeFromLatLong);

            $status = $output->status;

            //Get address from json data
            $address = ($status == "OK") ? $output->results[1]->formatted_address : '';

            //Return address of the given latitude and longitude
            if (!empty($address))
                return $address;
        }

        return false;
    }

    public static function makeOrder($products, $obj, $cache)
    {

        $url = BRINGO_URL . "/api/app/order";

        $params = array();

        $manId = $products[0]["manufacturer_id"];
        if (!empty($manId))
            $params["manufacturer_id"] = $manId;

        if (isset($obj->first_name))
            $params["name"] = $obj->first_name;

        if (isset($obj->phone))
            $params["phone"] = $obj->phone;

        if (isset($obj->payment_id))
            $params["payment_id"] = $obj->payment_id;

        if (isset($obj->delivery_id))
            $params["delivery_id"] = $obj->delivery_id;

        if (isset($obj->comment)) {
            $params["comment"] = $obj->comment;
        }
        if (isset($obj->deliveryDate) && $obj->deliveryDate != "0000-00-00") {
            $params["time"] = strtotime($obj->deliveryDate . " " . $obj->deliveryTime);
        }

        if (!empty($products)) {
            $productsStr = "";
            foreach ($products as $product) {
                $productsStr .= $product["product_id"] . "," . $product["quantity"] . ";";
            }
            $params["products"] = $productsStr;
        }

        if ($obj->delivery_id == 15) {
            $params["is_pickup"] = 1;
        } else {
            if (isset($cache->latitude))
                $params["latitude"] = $cache->latitude;

            if (isset($cache->longitude))
                $params["longitude"] = $cache->longitude;
        }

        $params["from_telegram"] = 1;

        $curl = new curl ();
        $resJson = $curl->send('GET', $url, $params);

        return json_decode($resJson, true);
    }

    public static function errorLog($telegram, $msg)
    {
        // for error log
        $telegram->sendMessage([
            'chat_id' => "133895664",  // MrLee
            'text' => json_encode($msg)
        ]);
    }

    public static function removePlusPhone($phone)
    {
        if (substr($phone, 0, 1) == "+")
            $phone = str_replace("+", "", $phone);

        return $phone;
    }

    public static function matchPhone($phone)
    {

        if (preg_match('/^9989\d{8}$/', $phone))
            return true;

        if (preg_match('/^9\d{8}$/', $phone))
            return true;

        return false;
    }

    public static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }

    public static function numberFormat($price)
    {
        return number_format($price, 0, ',', ' ');
    }

    public static function timeToFloat($time, $add = 0)
    {
        $time = explode(":", $time);
        $h = (int)$time[0];
        $m = (int)$time[1];

        if ($m == 0)
            $return = $h;
        elseif ($m > 0 && $m <= 30)
            $return = $h + 0.5;
        elseif ($m > 30)
            $return = $h + 1;

        return $return + $add;
    }

    public static function floatToTime($float, $type = "string")
    {
        $array = explode(".", $float);
        if (!isset($array[1]))
            $array[1] = "00";
        else
            $array[1] = "30";
        if ($type == "array")
            return $array;
        else
            return $array[0].":".$array[1];
    }

    public static function getEmoji()
    {
        return array(
            'smile' => json_decode('"\uD83D\uDE03"'), // Улыбочка.
            'question' => json_decode('"\u2753"'),
            'product' => json_decode('"\uD83D\uDD39"'),
            'back' => json_decode('"\u2B05"'),
            'remove' => json_decode('"\u274C"'),
            'clear' => json_decode('"\uD83D\uDD8C"'),
            'lookup' => json_decode('"\uD83D\uDCE5"'),
            'location' => json_decode('"\uD83D\uDCCD"'),
            'delivery' => json_decode('"\uD83D\uDE9A"'),
            'takeAway' => json_decode('"\uD83D\uDEB6"'),
            'card' => json_decode('"\uD83D\uDCB3"'),
            'cash' => json_decode('"\uD83D\uDCB5"'),
            'product' => json_decode('"\uD83C\uDF74"'),
            'order' => json_decode('"\u2733\uFE0F"'),
            'confirm' => json_decode('"\u2705"'),
            'cancel' => json_decode('"\uD83D\uDEAB"'),
            'category' => json_decode('"\uD83D\uDD38"'),
            'numeric' => json_decode('"\uD83D\uDD22"'),
            'phone' => json_decode('"\u260E\uFE0F"'),
            'mobile' => json_decode('"\uD83D\uDCF1"'),
            'uz' => json_decode('"\uD83C\uDDFA\uD83C\uDDFF"'),
            'ru' => json_decode('"\uD83C\uDDF7\uD83C\uDDFA"'),
            'menu' => json_decode('"\u2B05\uFE0F"'),
            'choise' => json_decode('"\u2935\uFE0F"'),
            'restart' => json_decode('"\uD83D\uDD01"'),
            'address' => json_decode('"\uD83C\uDFE0"'),
            'deliveryTerms' => json_decode('"\uD83D\uDE96"'),
            'error' => json_decode('"\u2757\uFE0F"'),
            'warn' => json_decode('"\u26A0\uFE0F"'),
            'orderCreated' => json_decode('"\uD83D\uDCDD"'),
            'okHand' => json_decode('"\uD83D\uDC4C"'),
            'downHand' => json_decode('"\uD83D\uDC47"'),
            'rightPointer' => json_decode('"\u25B6"'),
            'leftPointer' => json_decode('"\u25C0"'),
            'castle' => json_decode('"\uD83C\uDFE0"'),
            'car' => json_decode('"\uD83D\uDE9A"'),
            'pushpin' => json_decode('"\uD83D\uDCCC"'),
            'urgant' => json_decode('"\u26A1"'),
            'time' => json_decode('"\ud83d\udd50 "'),
            'raisedHand' => json_decode('"\u270B"'),
            'pencil' => json_decode('"\u270F"'),
            'partyPopper' => json_decode('"\uD83C\uDF89"'),
            'key' => json_decode('"\uD83D\uDD11"'),
            'exclamation_mark' => json_decode('"\u2757\ufe0f"'),
        );
    }

    public static function getLanguages()
    {
        return [

            "ru" => [
                'order_not_found' => 'Заказ не найден',
                'order_already_paid' => 'Заказ уже оплачен',
                'order_payment_title' => 'Оплата',
                'order_payment_desc' => 'Оплата для заказа ',
                "back" => "Назад",
                "cart" => "Корзина",
                "home" => "Все кафе и рестораны",
                "pickup" => "Самовывоз",
                "by_courier" => "Курьером",
                "urgant" => "Срочный",
                "in_time" => "На времия",
                "back_pointer" => "Назад",
                "next_pointer" => "Вперед",
                "location" => "Отправка локацию",
                "language" => "Русский",
                "clear_cart" => "Очистить",
                "make_order" => "Оформить заказ",
                "add_to_cart" => "Добавить",
                "confirm_order" => "Подтверждаю",
                "confirm_name" => "Имя",
                "confirm_phone" => "Номер телефона",
                "confirm_delivery_method" => "Способ оплаты",
                "confirm_manufacturer" => "Кафе или ресторан",
                "confirm_delivery_date" => "Дата",
                "confirm_delivery_time" => "Времия",
                "cancel_order" => "Отменить",
                "send_location" => "Отправить локацию",
                "send_contact" => "Отправить мой номер телефона",
                "send_new_location" => "Отправить другой адрес для доставки",
                "new_order" => "Новый заказ",
                "back_to_home" => "Да",
                "stay" => "Нет",

                // messages
                "msg_send_location" => "Отправьте местоположение для получения списка ближайших кафе и ресторанов",
                "msg_delivery_terms" => "Условия доставки",
                "msg_manufacturers" => "Выберите кафе или ресторан",
                "msg_categories" => "Выберите категорию",
                "msg_products" => "Выберите продукт",
                "msg_empty_cart" => "Корзина пуста",
                "msg_add_cart" => "Товар добавлен в корзину",
                "msg_clear_cart" => "Корзина очищена",
                "msg_we_cart" => "Ваша корзина",
                "msg_total_price" => "Итого",
                "msg_delivery_price" => "Доставка",
                "msg_total_price_delivery" => "Итого с доставкой",
                "msg_payment_method" => "Выберите способ оплаты",
                "msg_contact" => "Отправьте или введите ваш номер телефона (9989xxx...)",
                "msg_confirm" => "Подвтердите свой заказ ?",
                "msg_comment" => "Без комментариев",
                "msg_confirm_success" => "Ваш заказ принят !",
                "msg_order_number" => "Номер заказа: ",
                "msg_new_order" => "Нажмите кнопку Новый заказ, чтобы занова заказать",
                "msg_back_to_home" => "Вы уверены что хотите выйти назад? \nВаша корзина будет очищена!",
                "msg_new_order_again" => "Для оформления нового заказа, нажмите кнопку ниже",

                "msg_calendar_incoeerct" => "Неправильный выбор, пожалуйста, попробуйте снова.",
                "msg_calendar_old_date" => "Пожалуйста, выберите дату правильно.",
                "msg_date_saved" => "Дата успешно сохранена.",
                "msg_time_saved" => "Время успешно сохранена.",
                "msg_cafe_closed" => "<b>Кафе закрыто. Закажите на следующий день.</b>",
                "msg_select_delivery_type" => "Выберите тип доставки.",
                "msg_select_delivery_time" => "Выберите время доставки.",

                // text
                "text_delivery_terms" => "*Цена доставки*: до 3 км - 8,000 сум, каждый доп. км - 1,000 сум",
                "text_current_geolocation" => "Текущая геолокация для доставкa",
                "text_invalid" => "Недопустимый текст",
                "text_address" => "Проверьте пожалуйста правильность введённого адреса",
                "text_comment" => "Комментарий к заказу",

                // Calendar

                "select_date" => "Выберите дату заказа",
                "select_time" => "Выберите времию заказа",

                // Month
                "month" => ["Январ","Феврал","Март","Апрел","Май","Июн","Июл","Август","Сентябр","Октябр","Ноябр","Декабр"],

                // Weeks
                "week" => ["Пн","Вт","Ср","Чт","Пт","Сб","Вс"],
            ],

            "uz" => [
                'order_not_found' => 'Buyurtma topilmadi',
                'order_already_paid' => 'Buyutma summasi allaqachon to\'langan',
                'order_payment_title' => 'Buyurtma uchun to\'lov',
                'order_payment_desc' => 'Buyurtma uchun to\'lov ',
                "back" => "Orqaga",
                "cart" => "Savat",
                "home" => "Kafe va restaranlar",
                "pickup" => "O'zim olvolaman",
                "by_courier" => "Kurer orqali",
                "urgant" => "Tezkor",
                "in_time" => "Vaqtga",
                "back_pointer" => "Orqaga",
                "next_pointer" => "Oldinga",
                "location" => "Geomanzilni jo'natish",
                "clear_cart" => "Tozalash",
                "make_order" => "Zakaz berish",
                "language" => "O'zbekcha",
                "add_to_cart" => "Qo'shish",
                "confirm_order" => "Tasdiqlayman",
                "confirm_name" => "Ism",
                "confirm_phone" => "Telefon",
                "confirm_delivery_method" => "To'lov",
                "confirm_manufacturer" => "Kafe yoki restaran",
                "confirm_delivery_date" => "Sana",
                "confirm_delivery_time" => "Vaqt",
                "cancel_order" => "Bekor qilish",
                "send_location" => "Geomanzilni tanlash",
                "send_contact" => "Mening nomerimni jo'natish",
                "send_new_location" => "Yangi geomanzilni jo'natish",
                "new_order" => "Yangi Buyurtma",
                "back_to_home" => "Ha",
                "stay" => "Yo'q",

                // messages
                "msg_send_location" => "Eng yaqin kafe va restoranlarni ro'yxatini olish uchun geomanziingizni yuboring",
                "msg_delivery_terms" => "Etkazib berish shartlari",
                "msg_manufacturers" => "Kafe yoki Restaranni tanlang",
                "msg_categories" => "Kategoriyani tanlang",
                "msg_products" => "Ovqatlardan tanlang",
                "msg_empty_cart" => "Savarcha bo'sh",
                "msg_add_cart" => "Ovqat savatchga qo'shildi",
                "msg_clear_cart" => "Savatcha tozalandi",
                "msg_we_cart" => "Buyurtma",
                "msg_total_price" => "Summa",
                "msg_delivery_price" => "Yetkazish",
                "msg_total_price_delivery" => "Jami",
                "msg_payment_method" => "Yetkazib berish to'lov turini tanlang",
                "msg_contact" => "Telfon nomeringizni jo'nating yoki yozing (9989xxx...)",
                "msg_confirm" => "Iltimos tasdiqlang ?",
                "msg_comment" => "Izohsiz",
                "msg_confirm_success" => "Buyurtmangiz qabul qilindi !",
                "msg_order_number" => "Buyurtma raqami: ",
                "msg_new_order" => "Yangi buyurtma berish uchun Yangi buyurtma tugmasini bosing",
                "msg_back_to_home" => "Вы уверены что хотите выйти назад? \nВаша корзина будет очищена!",
                "msg_new_order_again" => "Yangi buyurtma berish uchun, quyidagi tugmani bosing",
                "msg_calendar_incoeerct" => "Noto'g'ri tanlov, qayta urinib ko'ring ...",
                "msg_calendar_old_date" => "Iltimos, sanani to'g'ri tanlang.",
                "msg_date_saved" => "Sana muvaffaqiyatli saqlandi.",
                "msg_time_saved" => "Vaqt muvaffaqiyatli saqlandi.",
                "msg_cafe_closed" => "<b>Kafe yopildi. Keyingi kunga buyurtma qiling.</b>",
                "msg_select_delivery_type" => "Yetkazib berish turini tanlang.",
                "msg_select_delivery_time" => "Yetkazib berish vaqtini tanlang.",

                // text
                "text_delivery_terms" => "*Etkazib berish narxi*: 3 km gacha 8,000 so'm, +1 km 1,000 so'm",
                "text_current_geolocation" => "Zakaz olib borilishi kerak bo'lgan geomanzil",
                "text_invalid" => "Noto'g'ri text",
                "text_address" => "Iltimos jo'natgan manzilingizni tekshiring",
                "text_comment" => "Buyurtmaga izoh",

                // Calendar

                "select_date" => "Buyurtma kunini tanlang",
                "select_time" => "Buyurtma vaqtini tanlang",

                // Month
                "month" => ["Yanvar","Fevral","Mart","Aprel","May","Iyun","Iyul","Avgust","Sentabr","Oktabr","Noyabr","Dekabr"],

                // Weeks
                "week" => ["Du","Se","Ch","Pa","Ju","Sh","Ya"],
            ]
        ];
    }

}

?>
