<?php

class MysqlDB {

   protected $_mysql;
   protected $_where = array();
   protected $_query;
   protected $_paramTypeList;


   public function __construct($host, $username, $password, $db) {
      $this->_mysql = new mysqli($host, $username, $password, $db) or die('There was a problem connecting to the database');
   }

   public function resultSetToArray($result_set) {
      $array = array();
      while (($row = $result_set->fetch_assoc()) != false) {
         $array[] = $row;
      }
      return $array;
   }
   public function count_val($table)
   {
      $q = "SELECT count(*) as `c` from $table";
      $result = $this->_mysql->query($q);
      $count = $result->fetch_object()->c;
      return $count;
   }
   public function searching($val)
   {
      $sql = "SELECT * FROM news WHERE text LIKE '%$val%';";
      $retVal = $this->_mysql->query($sql);
      $result = $this->resultSetToArray($retVal);
      return $result;

   }

   public function find($table,$column,$value=NULL) 
   { 
      $result = true;
      $find = $this->get($table,NULL,NULL,$column);
      foreach ($find as $key => $val) {
         if ($val[$table] === $value) {
            $result = true;
          break;
         }
         else{
            $result = false;
         }
      }
      return $result;
      
      }
   public function findOthers($table,$column,$value=NULL) 
   {

      $result = true;
      $find = $this->get($table,NULL,NULL,$column);
      foreach ($find as $key => $val) {
         if ($val['image'] === $value) {
            $result = true;
          break;
         }
         else{
            $result = false;
         }
      }
      return $result;
      
      }

   public function query($query) 
   {
      $this->_query = filter_var($query, FILTER_SANITIZE_STRING);

      $stmt = $this->_prepareQuery();
      $stmt->execute();
      $results = $this->_dynamicBindResults($stmt);
      return $results;
   }

   public function get($tableName,$limit = NULL, $numRows = NULL, $column = NULL,$login = false) 
   {
      if ($login) 
      {
         $this->_query = "SELECT * FROM $tableName ";
      }
      else
      {
         $this->_query = (!$column) ? "SELECT * FROM $tableName  ORDER BY id DESC " : "SELECT $column FROM $tableName ORDER BY id DESC " ;
      }
      $stmt = $this->_buildQuery($numRows,NULL,$limit);
      $stmt->execute();

      $results = $this->_dynamicBindResults($stmt);
      return $results;
   }

   public function insert($tableName, $insertData) 
   {
      $this->_query = "INSERT into $tableName";
      $stmt = $this->_buildQuery(NULL, $insertData);
      $stmt->execute();

      if ($stmt->affected_rows)
         return true;
   }

   public function update($tableName, $tableData) 
   {
      $this->_query = "UPDATE $tableName SET ";

      $stmt = $this->_buildQuery(NULL, $tableData);
      $stmt->execute();

      if ($stmt->affected_rows)
         return true;
   }

   public function delete($tableName) 
   {
      $this->_query = "DELETE FROM $tableName";

      $stmt = $this->_buildQuery();
      $stmt->execute();

      if ($stmt->affected_rows)
         return true;
   }

   public function where($whereProp, $whereValue) 
   {
      $this->_where[$whereProp] = $whereValue;
   }

   protected function _determineType($item) 
   {
      switch (gettype($item)) {
         case 'string':
            return 's';
            break;

         case 'integer':
            return 'i';
            break;

         case 'blob':
            return 'b';
            break;

         case 'double':
            return 'd';
            break;
      }
   }

   protected function _buildQuery($numRows = NULL, $tableData = false,$limit = NULL) 
   {

      $hasTableData = null;
      if (gettype($tableData) === 'array') {
         $hasTableData = true;
      }

      // Did the user call the "where" method?
      if (!empty($this->_where)) {
         $keys = array_keys($this->_where);
         $where_prop = $keys[0];
         $where_value = $this->_where[$where_prop];

         // if update data was passed, filter through
         // and create the SQL query, accordingly.
         if ($hasTableData) {
            $i = 1;
            $pos = strpos($this->_query, 'UPDATE');
            if ( $pos !== false) {
               foreach ($tableData as $prop => $value) {
                  // determines what data type the item is, for binding purposes.
                  $this->_paramTypeList .= $this->_determineType($value);

                  // prepares the reset of the SQL query.
                  if ($i === count($tableData)) {
                     $this->_query .= $prop . " = ? WHERE " . $where_prop . "= " . $where_value;
                  } else {
                     $this->_query .= $prop . ' = ?, ';
                  }

                  $i++;
               }
            }
         } else {
            // no table data was passed. Might be SELECT statement.
            $this->_paramTypeList = $this->_determineType($where_value);
            $this->_query .= " WHERE " . $where_prop . "= ?";
         }
      }

      // Determine if is INSERT query
      if ($hasTableData) {
         $pos = strpos($this->_query, 'INSERT');

         if ($pos !== false) {
            //is insert statement
            $keys = array_keys($tableData);
            $values = array_values($tableData);
            $num = count($keys);

            // wrap values in quotes
            foreach ($values as $key => $val) {
               $values[$key] = "'{$val}'";
               $this->_paramTypeList .= $this->_determineType($val);
            }

            $this->_query .= '(' . implode($keys, ', ') . ')';
            $this->_query .= ' VALUES(';
            while ($num !== 0) {
               ($num !== 1) ? $this->_query .= '?, ' : $this->_query .= '?)';
               $num--;
            }
         }
      }

      // Did the user set a limit
      if (isset($numRows)&&isset($limit)) {
         $this->_query .= " LIMIT ". (int) $limit.", ". (int) $numRows;
      }

      // Prepare query
      $stmt = $this->_prepareQuery();

      // Bind parameters
      if ($hasTableData) {
         $args = array();
         $args[] = $this->_paramTypeList;
         foreach ($tableData as $prop => $val) {
            $args[] = &$tableData[$prop];
         }
         call_user_func_array(array($stmt, 'bind_param'), $args);
      } else {
         if ($this->_where)
            $stmt->bind_param($this->_paramTypeList, $where_value);
      }

      return $stmt;
   }

   protected function _dynamicBindResults($stmt) 
   {
      $parameters = array();
      $results = array();

      $meta = $stmt->result_metadata();

      while ($field = $meta->fetch_field()) {
         $parameters[] = &$row[$field->name];
      }

      call_user_func_array(array($stmt, 'bind_result'), $parameters);

      while ($stmt->fetch()) {
         $x = array();
         foreach ($row as $key => $val) {
            $x[$key] = $val;
         }
         $results[] = $x;
      }
      return $results;
   }

   protected function _prepareQuery() 
   {
      if (!$stmt = $this->_mysql->prepare($this->_query)) {
         trigger_error("Problem preparing query", E_USER_ERROR);
      }
      return $stmt;
   }


   public function __destruct() 
   {
      $this->_mysql->close();
   }

}

