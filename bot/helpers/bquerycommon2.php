<?php

class BQueryCommon
{
    private $dbcon;
    private $telegram;

    const DB_HOST = "127.0.0.1";
    const DB_PORT = "3306";
    const DB_NAME = "maydon_bot";
    const DB_USERNAME = "root";
    const DB_USERPASS = "root";

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

        // #1
    public function getUser($chat_id)
    {
        $sql = "SELECT * FROM `bot_users` WHERE `chat_id` = $chat_id";

        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_object($result);

        return $rows;
    }

    // #2, #4
    public function updateOrInsertUser($user, $lang = "ru")
    {

        $firstname = $user->getFirstName();
        $lastname = $user->getLastName();
        $username = $user->getUsername();

        $sql = "INSERT INTO `bot_users`(`chat_id`, `first_name`, `last_name`, `username`) VALUES ({$user->getId()}, '{$firstname}', '{$lastname}', '{$username}') ON DUPLICATE KEY UPDATE `language_code` = '{$lang}'";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    // #3     
    public function updatePageStatus($chat_id, $status)
    {
        $sql = "UPDATE `bot_users` SET `page_status` = $status WHERE `chat_id` = $chat_id";

        if (!mysqli_query($this->dbcon, $sql))
            BCommon::errorLog($this->telegram, mysqli_error($this->dbcon));
    }

    public function getListOfStadiums()
    {
        $sql = "SELECT * FROM `staduims`";

        $result = mysqli_query($this->dbcon, $sql);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);        

        return $rows;
    }
}