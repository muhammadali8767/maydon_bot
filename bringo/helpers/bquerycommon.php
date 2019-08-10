<?php

/**
 * @author @CleverUzbek
 */
class BQueryCommon
{
    private $dbcon;
    private $telegram;

    const DB_HOST = "127.0.0.1";
    const DB_PORT = "3306";
    const DB_NAME = "bringo"; // dev
    const DB_USERNAME = "bringo"; // dev
    const DB_USERPASS = "3acCt&YfLuA[*Bb"; //Hw5u?Rc-h?VeAQU

    public function __construct($telegram)
    {
        $this->dbcon = mysqli_connect(self::DB_HOST, self::DB_USERNAME, self::DB_USERPASS, self::DB_NAME, self::DB_PORT);
        $this->telegram = $telegram;

        if (!$this->dbcon) {
            BCommon::errorLog($this->telegram, 'Не удалось соединиться: ' . mysqli_error());
        }

        mysqli_set_charset($this->dbcon, "utf8mb4");
        // mysqli_set_charset($this->dbcon, "utf8");

        // BCommon::errorLog($this->telegram, "connected...");
    }

    public function updateOrderPayment($id, $data)
    {
        $sql = "UPDATE `bot_payments` SET `raw_data` = '" . $data['raw'] . "', `telegram_payment_charge_id` = '" . $data['tg_payment_id'] . "', `provider_payment_charge_id` = '" . $data['provider_payment_id'] . "', `status` = 1, `updated_at` = '".time()."' WHERE `id` = $id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function getBotPaymentByInvoicePayload($user = 0, $payload = "")
    {
        $sql = "SELECT `id`, `status`, `invoice_message_id` FROM `bot_payments` WHERE `user` = '" . $user . "' AND `invoice_payload` = '" . $payload . "'";

        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_object($result);

        return $rows;
    }

    public function createBotOrderPayment($data)
    {

        $sql = "INSERT INTO `bot_payments`(`user`, `phone`, `created_at`, `updated_at`, `total_amount`, `invoice_payload`, `telegram_payment_charge_id`, `provider_payment_charge_id`, `raw_data`, `status`, `order_identity`, `order_code`, `provider`, `invoice_message_id`) VALUES ('" . $data['user'] . "', '" . $data['phone'] . "', '" . time() . "','" . time() . "','" . $data['total_amount'] . "','" . $data['invoice_payload'] . "','','','','0','" . $data['order_identity'] . "','" . $data['order_code'] . "','" . $data['provider'] . "', '" . $data['message_id'] . "')";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateOrInsertUser($user, $lang = "ru")
    {

        $firstname = $this->clearCharater($user->getFirstName());
        $lastname = $this->clearCharater($user->getLastName());
        $username = $user->getUsername();

        $sql = "INSERT INTO `bot_users`(`chat_id`, `first_name`, `last_name`, `username`) VALUES ({$user->getId()}, '{$firstname}', '{$lastname}', '{$username}') ON DUPLICATE KEY UPDATE `language_code` = '{$lang}'";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateOrInsertCache($user, $location, $json = null)
    {
        $sql = "INSERT INTO `bot_users_caches`(`chat_id`, `latitude`, `longitude`, `json_code`) VALUES ({$user->getId()}, {$location->getLatitude()}, {$location->getLongitude()}, '$json' ) ON DUPLICATE KEY UPDATE `latitude` = {$location->getLatitude()}, `longitude`= {$location->getLongitude()}, `json_code` = '$json'";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function successResult($chat_id, $json)
    {
        $order_code = $json["result"]["order"]["order_code"];
        $data = $json["result"]["order"]["created"];

        $sql = "INSERT INTO `bot_orders`(`chat_id`, `order_code`, `status`, `created_data`) VALUES ($chat_id, $order_code, 1, '$data')";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function cancelResult($chat_id)
    {
        $data = date("y-m-d H:i:s");

        $sql = "INSERT INTO `bot_orders`(`chat_id`, `status`, `created_data`) VALUES ($chat_id, 0, '$data')";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateCurrentPage($pagination, $chat_id)
    {
        $sql = "UPDATE `bot_users_caches` SET `current_page` = $pagination WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateLastMessageId($chat_id, $message_id)
    {
        $sql = "UPDATE `bot_users_caches` SET `last_message_id` = $message_id WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateContact($chat_id, $contact)
    {

        $firstname = $this->clearCharater($contact->getFirstName());
        $lastname = $this->clearCharater($contact->getLastName());
        $phone = BCommon::removePlusPhone($contact->getPhoneNumber());

        $sql = "UPDATE `bot_users` SET `phone` = '$phone', `first_name` = '$firstname', `last_name` = '$lastname' WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateComment($chat_id, $text)
    {
        $text = $this->clearCharater($text);

        $sql = "UPDATE `bot_users` SET `comment` = '$text' WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));

    }

    public function updatePhone($chat_id, $phone)
    {

        if (strlen($phone) == 9)
            $phone = "998" . $phone;

        $sql = "UPDATE `bot_users` SET `phone` = '$phone' WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateOrInsertCart($chat_id, $man_id, $product_id, $quantity)
    {
        $sql = "REPLACE INTO `bot_cart`(`manufacturer_id`, `product_id`, `quantity`, `chat_id`) VALUES ($man_id, $product_id, $quantity, $chat_id)";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateCartCount($chat_id)
    {
        $sql = "UPDATE `bot_users` SET `cart_count` = (SELECT SUM(`quantity`) FROM `bot_cart` WHERE `chat_id` = $chat_id) WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updatePageStatus($chat_id, $status)
    {
        $sql = "UPDATE `bot_users` SET `page_status` = $status WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateOrderInfo($chat_id, $payment_id = 18, $delivery_id = 16)
    {
        $sql = "UPDATE `bot_users` SET `payment_id` = $payment_id, `delivery_id` = $delivery_id WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function getCart($chat_id, $lang = "ru")
    {
        $langId = 1;
        if ($lang == "uz")
            $langId = 10;

        $sql = "SELECT  bc.*, 
                        spt.`name`,
                        sp.`price`
                    FROM `bot_cart` bc 
                        INNER JOIN `StoreProductTranslate` spt 
                            ON spt.`object_id` = bc.`product_id` AND spt.`language_id` = $langId 
                        INNER JOIN  `StoreProduct` sp
                            ON sp.`id` = bc.`product_id`
                    WHERE `chat_id` = $chat_id";

        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        mysqli_free_result($result);

        return $rows;
    }

    public function clearCart($chat_id)
    {
        $sql = "DELETE FROM `bot_cart` WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));

        $this->updateCartCount($chat_id);
    }

    public function deleteCart($chat_id, $id)
    {
        $sql = "SELECT `quantity` FROM `bot_cart` WHERE `id` = $id";

        $result = mysqli_query($this->dbcon, $sql);
        $row = mysqli_fetch_object($result);

        mysqli_free_result($result);

        if (intval($row->quantity) > 1)
            $sql = "UPDATE `bot_cart` SET `quantity` = (quantity - 1) WHERE `id` = $id AND quantity > 1";
        else
            $sql = "DELETE FROM `bot_cart` WHERE `id` = $id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));

        $this->updateCartCount($chat_id);
    }

    public function getManufacturer($chat_id)
    {
        $sql = "SELECT `manufacturer_id` FROM `bot_cart` WHERE `chat_id` = $chat_id LIMIT 1";

        $result = mysqli_query($this->dbcon, $sql);
        $row = mysqli_fetch_object($result);

        $man_id = $row->manufacturer_id;

        $sql = "SELECT `json_code` FROM `bot_users_caches` WHERE `chat_id` = $chat_id LIMIT 1";

        $result = mysqli_query($this->dbcon, $sql);
        $row = mysqli_fetch_object($result);

        return json_decode($row->json_code, true)[$man_id];
    }

    public function getCacheDataByChatId($chat_id)
    {
        $sql = "SELECT `json_code`, `current_page`, `latitude`, `longitude` FROM `bot_users_caches` WHERE `chat_id` = $chat_id";

        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_object($result);

        return $rows;
    }

    public function getLastMessageId($chat_id)
    {
        $sql = "SELECT `last_message_id` FROM `bot_users_caches` WHERE `chat_id` = $chat_id";

        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_object($result);

        return intval($rows->last_message_id);
    }

    public function getUser($chat_id)
    {
        $sql = "SELECT * FROM `bot_users` WHERE `chat_id` = $chat_id";

        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_object($result);

        return $rows;
    }

    public function getCategories($restaurantId, $lang)
    {

        $langId = 1;
        if ($lang == "uz")
            $langId = 10;

        $sql = "SELECT  
                        spc.category AS 'id', 
                        spt.name,
                        (SELECT smt.name FROM StoreManufacturerTranslate smt WHERE smt.object_id = $restaurantId AND smt.language_id = $langId) AS 'man_name'
                    FROM StoreProductCategoryRef spc 
                        JOIN StoreCategory sc 
                            ON sc.id = spc.category 
                        JOIN StoreProduct sp  
                            ON sp.id = spc.product
                        JOIN StoreCategoryTranslate spt
                            ON spt.object_id = spc.category AND spt.language_id = $langId
                        WHERE 
                            sp.manufacturer_id = '$restaurantId' 
                            AND sp.is_active = 1 AND sc.level = 3
                    GROUP BY spc.category
                    ORDER BY sc.lft ASC";

        // run query
        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Free result set
        mysqli_free_result($result);

        return $rows;
    }

    public function getProducts($man_id, $cat_id, $lang = "ru")
    {
        $langId = 1;
        if ($lang == "uz")
            $langId = 10;

        $sql = "SELECT sp.id, spt.name 
                    FROM StoreProductCategoryRef spc 
                        JOIN StoreCategory sc 
                            ON sc.id = spc.category AND sc.id = $cat_id
                        JOIN StoreProduct sp 
                            ON sp.id = spc.product
                        LEFT JOIN StoreProductTranslate spt
                            ON spt.object_id = sp.id AND spt.language_id = $langId
                        WHERE sp.manufacturer_id = $man_id AND sp.is_active = 1
                        GROUP BY spc.product";

        // run query
        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Free result set
        mysqli_free_result($result);

        return $rows;
    }

    public function getProduct($product_id, $lang = "ru")
    {
        $langId = 1;
        if ($lang == "uz")
            $langId = 10;

        $sql = "SELECT  
                        sp.id, 
                        spt.name, 
                        spt.ingredients,
                        sp.sku, 
                        sp.price, 
                        spm.name AS 'photo'
                    FROM StoreProduct sp
                        LEFT JOIN StoreProductTranslate spt
                            ON spt.object_id = sp.id AND spt.language_id = $langId
                        LEFT JOIN StoreProductImage spm
                            ON spm.product_id = sp.id AND spm.is_main = 1
                        WHERE sp.id = $product_id";

        // run query
        $result = mysqli_query($this->dbcon, $sql);
        $row = mysqli_fetch_object($result);

        $path = BCommon::PRODUCT_NO_PHOTO_PATH . "no_photo_250.jpg";

       if (!empty($row->photo))
           $path = BCommon::PRODUCT_PHOTO_URL . $row->photo;

        $row->photo = $path;

        return $row;
    }

    public function getPayments($chat_id, $lang = "ru")
    {
        $langId = 1;
        if ($lang == "uz")
            $langId = 10;

        $sql = "SELECT smp.`payment_id` AS 'id', spmt.`name` 
                    FROM StoreManufacturerPayment smp 
                    JOIN StorePaymentMethodTranslate spmt 
                        ON spmt.`object_id` = smp.`payment_id` AND spmt.`language_id` = $langId 
                    WHERE smp.payment_id IN(18,19,21) AND smp.`manufacturer_id` = (SELECT `manufacturer_id` FROM `bot_cart` WHERE `chat_id` = $chat_id LIMIT 1)";

        // run query
        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Free result set
        mysqli_free_result($result);

        return $rows;
    }

    public function clearCharater($str)
    {
        $str = preg_replace("/'/", "`", $str);
        $str = preg_replace('/"/', "`", $str);

        return $str;
    }

    public function updateDeliveryDate($chat_id, $date)
    {
        $sql = 'UPDATE `bot_users` SET `deliveryDate` = "' . $date . '" WHERE `chat_id` = "' . $chat_id . '"';

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function updateDeliveryTime($chat_id, $time)
    {
        $sql = 'UPDATE `bot_users` SET `deliveryTime` = "' . $time . '" WHERE `chat_id` = "' . $chat_id . '"';

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function getDeliveryDate($chat_id)
    {
        $sql = "SELECT `deliveryDate` FROM `bot_users` WHERE `chat_id` = $chat_id";
        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        // Free result set
        mysqli_free_result($result);

        return $rows[0]['deliveryDate'];
    }

    public function getDeliveryTime($chat_id)
    {
        $sql = "SELECT `deliveryTime` FROM `bot_users` WHERE `chat_id` = $chat_id";
        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        // Free result set
        mysqli_free_result($result);

        return $rows[0]['deliveryTime'];
    }

    public function __destruct()
    {
        // BCommon::errorLog($this->telegram, "db closed...");
        mysqli_close($this->dbcon);
    }

}

?>