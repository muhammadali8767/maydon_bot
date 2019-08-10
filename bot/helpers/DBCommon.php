<?php 

require(dirname(__FILE__)."/PDO/src/PDO.class.php");


// $DB->query("DROP TABLE IF EXISTS `fruit`;");
// $DB->query("CREATE TABLE IF NOT EXISTS `fruit` (

// $AffectedRows = $DB->query("INSERT INTO `fruit` (`id`, `name`, `color`) VALUES

// var_export($DB->query("SELECT * FROM fruit WHERE name=:name and color=:color",array('name'=>'apple','color'=>'red')));
// var_export($DB->query("SELECT * FROM fruit WHERE name IN (?)",array('apple','banana')));
// var_export($DB->column("SELECT color FROM fruit WHERE name IN (?)",array('apple','banana','watermelon')));
// var_export($DB->row("SELECT * FROM fruit WHERE name=? and color=?",array('apple','red')));

// echo $DB->single("SELECT color FROM fruit WHERE name=? ",array('watermelon'));

// $DB->query("DELETE FROM fruit WHERE id = :id", array("id"=>"1"));
// $DB->query("DELETE FROM fruit WHERE id = ?", array("1")); // Update
// $DB->query("UPDATE fruit SET color = :color WHERE name = :name", array("name"=>"strawberry","color"=>"yellow"));
// $DB->query("UPDATE fruit SET color = ? WHERE name = ?", array("yellow","strawberry"));
// $DB->query("INSERT INTO fruit(id,name,color) VALUES(?,?,?)",array(null,"mango","yellow"));//Parameters must be ordered
// $DB->query("INSERT INTO fruit(id,name,color) VALUES(:id,:name,:color)", array("color"=>"yellow","name"=>"mango","id"=>

// echo $DB->lastInsertId();
// echo $DB->querycount;

class DBCommon
{
	provate $DB;
	provate $DBHost = '127.0.0.1';
	provate $DBPort =  3306;
	provate $DBName = 'test';
	provate $DBUser = 'root';
	provate $DBPassword =  'root';

	function __construct(argument)
	{
		$DB = new Db($this->DBHost, $this->DBPort, $this->DBName, $this->DBUser, $this->DBPassword);		
	}

	public function FunctionName($value='')
	{
		# code...
	}
}

?>