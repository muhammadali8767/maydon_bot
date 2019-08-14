<?php 
require_once(dirname(__FILE__).'/lib/MySqlDb.php');

class DBCommon
{
	private $DB;
	private $DBHost = '127.0.0.1';
	private $DBPort =  3306;
	private $DBName = 'test';
	private $DBUser = 'root';
	private $DBPassword =  'root';
	private $telegram;

	function __construct($telegram)
	{
		$this->telegram = $telegram;
		$this->DB = new MySqlDb($this->DBHost, $this->DBUser, $this->DBPassword, $this->DBName); 
	}

	public function insertUser()
	{
		$message = $this->telegram->getWebhookUpdates()->getCallbackQuery()->getMessage();		
		$text = $message->getText();
		$user = $message->getFrom();
		$chat = $message->getChat();
		$location = $message->getLocation();
		$contact = $message->getContact();
		
	}

	public function insertArray($table, $insertData)
	{
		return $this->Db->insert($table, $insertData);
	}

	public function updateArray($table, $where, $updateData)
	{
		$Db->where(key($where),value($where));
		return $this->Db->update($table, $updateData);
	}
}

?>