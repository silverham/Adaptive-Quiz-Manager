<?php
//DBLogic.php

/*Example to to use it:
 * require_once("DbClass.php");
    //create an instance of the DB class

    $DbClass = new DB();
 * 
 * //retrieve data
            $firstName = "josh";
            $lastName = "Grey";
            $data = array(
                "firstName" => $firstName,
                "lastName" => $lastName,
            );
            $resultLine = $DbClass->select("*", "test", $data);
            
            
            $i = 0;
            foreach ($resultLine as $oneResult) {
                echo "<tr>";
                $result = array_values($oneResult); //convert from assocative array to numeric(normal) array
                echo "<td> <input type=\"radio\" name=\"id\" value=\"$result[0]\" />";
                for ($i2 = 0; $i2 < (count($oneResult)); $i2++){
                echo "<td>";
                    //echo "$i2";
                    echo $result[$i2];                    
                    echo "</td>";
                }
                echo "</tr>"; 
                $i += 1;
            }
            echo "</table>";
                    echo "<br /> <b>Total rows: $i<b>"
 * 
 * 
 */

class DB {
    private static $connection; //private - no access to outsiders
    private static $errorMessage;
    
    function __construct ($TINA = false) {
            $options = array(
                //PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' ",
                PDO::ATTR_EMULATE_PREPARES      => false,
                PDO::MYSQL_ATTR_INIT_COMMAND    => "SET NAMES utf8",
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION
            );
            try {
                if (!$TINA) { //false - default
                    self::$connection = new PDO('mysql:host='.DB_HOST.';dbname='.DB_DB, DB_USERNAME, DB_PASSWORD, $options); //$host,$user,$password,$db
                    $_SESSION["DB_STATUS"] = 1;
                    // "::" is the Scope Resolution Operator aka access class variables
                } else {
                    //setup TINA
                    echo "TINA!!!!!";
                }
            } catch (PDOException $e) {
                if (CONFIG_DEV_ENV == true){
                    $errorMessageSpecific = $e->getMessage();
                }
                $errorMessage = "There was an error connecting to the database.";
                include "404.php";
                exit;
            }
    }
    function isError() {
        if (self::$errorMessage  === NULL){
            return false;
        } else {
            return self::$errorMessage;
        }
    }
 
    //Select rows from the database.
    //returns a full row or rows from $table using $where as the where clause.
    //return value is an associative array with column names as keys.
    public function select($columns, $table, $dataArray, $singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the Select function on DB class");
        }
        $where = "";
        foreach ($dataArray as $column => $value) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$column = :$column";
        }
        
        $stmt = self::$connection->prepare("SELECT $columns FROM $table WHERE " . $where . ";") or die('Problem preparing query');
        $stmt->execute($dataArray);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    
    public function selectOrder($columns, $table, $dataArray, $sortColumn, $singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the Select function on DB class");
        }
        $where = "";
        foreach ($dataArray as $column => $value) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$column = :$column";
        }
        
        $stmt = self::$connection->prepare("SELECT $columns FROM $table WHERE $where ORDER BY $sortColumn;") or die('Problem preparing query');
        $stmt->execute($dataArray);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    
    public function selectWithColumns($column, $table, $dataArray, $whereColumn, $singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the selectWithColumns( function on DB class");
        }
        $where = "";
        foreach ($dataArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = :$columnTemp";
        }
        foreach ($whereColumn as $columnTemp => $valueTemp) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $valueTemp";
        }
        $stmt = self::$connection->prepare("SELECT $column FROM $table WHERE " . $where . ";") or die('Problem preparing query');
        $stmt->execute($dataArray);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    
    public function selectWithColumnsOrder($column, $table, $dataArray, $whereColumn, $sortColumn, $singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the selectWithColumns( function on DB class");
        }
        $where = "";
        //the parms to be binded
        foreach ($dataArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = :" . preg_replace('/\\./', '_', $columnTemp); //replace dot with underscore for table.column
            $bindings[] = array( 'binding' => preg_replace('/\\./', '_', $columnTemp), 'value' => $valueTemp );
        }
        //the columns
        foreach ($whereColumn as $columnTemp => $valueTemp) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $valueTemp";
        }
        $sql = "SELECT $column FROM $table WHERE $where ORDER BY $sortColumn;";
        $stmt = self::$connection->prepare("SELECT $column FROM $table WHERE $where ORDER BY $sortColumn") or die('Problem preparing query');
        foreach ($bindings as $bind) {
            $stmt->bindValue(':' . $bind['binding'], $bind['value']);
        }
        //$stmt->debugDumpParams();
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    
    public function selectWithColumnsOr($column, $table, $dataArray, $whereColumn, $dataArrayOr, $whereColumnOr, $singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the selectWithColumns( function on DB class");
        }
        $where = "";
        foreach ($dataArray as $columnTemp => $value) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = :$columnTemp";
        }
        foreach ($whereColumn as $columnTemp => $value) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $value";
        }
        $dataArrayMerged = array_merge($dataArray, $dataArrayOr);
        $where .= " OR (";
        $firstrun = true;
        foreach ($dataArrayOr as $columnTemp => $value) {      //$value not used - it's in $data
            $where .= ($firstrun == true) ? "" : " AND ";
            $firstrun = false;
            $where .= "$columnTemp = :$columnTemp";
        }
        foreach ($whereColumnOr as $columnTemp => $value) {      //where colown OR
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $value";
        }
        $where .= ")";
        $stmt = self::$connection->prepare("SELECT $column FROM $table WHERE " . $where . ";") or die('Problem preparing query');
        $stmt->execute($dataArrayMerged);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    
    public function selectDistinctWithColumnsOr($column, $table, $dataArray, $whereColumn, $dataArrayOr, $whereColumnOr, $singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the selectWithColumns( function on DB class");
        }
        $where = "";
        foreach ($dataArray as $columnTemp => $value) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = :$columnTemp";
        }
        foreach ($whereColumn as $columnTemp => $value) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $value";
        }
        $dataArrayMerged = array_merge($dataArray, $dataArrayOr);
        $where .= " OR (";
        $firstrun = true;
        foreach ($dataArrayOr as $columnTemp => $value) {      //$value not used - it's in $data
            $where .= ($firstrun == true) ? "" : " AND ";
            $firstrun = false;
            $where .= "$columnTemp = :$columnTemp";
        }
        foreach ($whereColumnOr as $columnTemp => $value) {      //where colown OR
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $value";
        }
        $where .= ")";
        $stmt = self::$connection->prepare("SELECT DISTINCT $column FROM $table WHERE " . $where . ";") or die('Problem preparing query');
        $stmt->execute($dataArrayMerged);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    
    public function selectAll($table) {
        if (!is_string ($table)) {
            die("A string was not passed to the SelectAll function on DB class");
        }
        $stmt = self::$connection->prepare("SELECT * FROM $table;") or die('Problem preparing query');
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    //Inserts a new row into the database.
    //takes an array of data, where the keys in the array are the column names
    //and the values are the data that will be inserted into those columns.
    //$table is the name of the table.
    public function insert($dataArray, $table) {
        $values = "";
        $columns = "";
        foreach ($dataArray as $column => $value) { //$value not used, it's in execute
            $columns .= ($columns == "") ? "" : ", ";
            $columns .= $column;
            $values .= ($values == "") ? "" : ", ";
            $values .= ":$column";
        }
        
        $stmt = self::$connection->prepare("insert into $table ($columns) values ($values);") or die('Problem preparing query');
        $stmt->execute($dataArray); //send the values separately
        return $results = self::$connection->lastInsertID();
    }
 
        public function delete($col, $data, $table) {
            $dataArray = array($data);
            $stmt = self::$connection->prepare("delete from $table where $col = ?;") or die('Problem preparing query');
        $stmt->execute($dataArray);  //send the values separately
 
        return $results = self::$connection->lastInsertID(); //return the ID of the user in the database.
    }
    
    public function selectDistinct($column, $table, $dataArray, $whereColumn, $singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the selectQuiz( function on DB class");
        }
        $where = "";
        foreach ($dataArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " OR ";             //Or here not AND, allows select quiz criteria to execute properly
            $where .= "$columnTemp = :$columnTemp";
        }
        foreach ($whereColumn as $columnTemp => $valueTemp) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $valueTemp";
        }
        $stmt = self::$connection->prepare("SELECT DISTINCT $column FROM $table WHERE " . $where . ";") or die('Problem preparing query');
        $stmt->execute($dataArray);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }

//Used to retrieve results from required tables
    public function selectFullOuterJoin($column, $table, $whereData, $joinTable, $tableArray, $joinTable2, $tableArray2,$singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the selectFullOuterJoin( function on DB class");
        }
        $where = "";
        $joinWhere = "";
        $joinWhere2 = "";
        foreach ($whereData as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = :$columnTemp";
        }
        foreach ($tableArray as $columnTemp => $valueTemp) {      //build coloumn where query
            $joinWhere .= ($joinWhere == "") ? "" : " AND ";
            $joinWhere .= "$columnTemp = $valueTemp";
        }
        foreach ($tableArray2 as $columnTemp => $valueTemp) {      //build coloumn where query
            $joinWhere2 .= ($joinWhere2 == "") ? "" : " AND ";
            $joinWhere2 .= "$columnTemp = $valueTemp";
        }
        $sql = "SELECT $column FROM $table " . 
                "LEFT JOIN $joinTable ON $joinWhere " . 
                "LEFT JOIN $joinTable2 ON $joinWhere2 WHERE $where;";
        $stmt = self::$connection->prepare($sql) or die('Problem preparing query');
        
        $stmt->execute($whereData);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    
    public function selectWithFourColumns($column, $table, $dataArray, $whereColumn, $whereColumn2,$whereColumn3,$singleRow=True) {
        if (!is_string ($table)) {
            die("A string was not passed to the selectWithColumns( function on DB class");
        }
        $where = "";
        foreach ($dataArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = :$columnTemp";
        }
        foreach ($whereColumn as $columnTemp => $valueTemp) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $valueTemp";
        }
        foreach ($whereColumn2 as $columnTemp => $valueTemp) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $valueTemp";
        }
        foreach ($whereColumn3 as $columnTemp => $valueTemp) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $valueTemp";
        }
        $stmt = self::$connection->prepare("SELECT $column FROM $table WHERE " . $where . ";") or die('Problem preparing query');
        $stmt->execute($dataArray);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            //$results = array_values($results[0]);   //return normal array instead
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    private function cleanTheOutput($output){
        return htmlentities($output);
    }
}
?>