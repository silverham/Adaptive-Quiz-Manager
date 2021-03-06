<?php
/*DBLogic.php
 * Provides easy DB access with DB protection mechanisim
 */

class dbLogic {
    private static $connection; //private - no access to outsiders
    
    function __construct ($TINA = false) {
            $options = array(
                PDO::ATTR_EMULATE_PREPARES      => false,
                PDO::MYSQL_ATTR_INIT_COMMAND    => "SET NAMES utf8",
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION
            );
            try {
                if (!$TINA) { //false - default
                    //$host,$user,$password,$db
                    self::$connection = new PDO('mysql:host='.DB_HOST.';dbname='.DB_DB, DB_USERNAME, DB_PASSWORD, $options); 
                    // "::" is the Scope Resolution Operator aka access class variables (and static functions)
                } else {
                    //setup TINA
                    echo "TINA!!!!!";
                }
            } catch (PDOException $e) {
                if (CONFIG_DEV_ENV == true){
                /* @var $errorMessageSpecific type */
                $errorMessageSpecific = $e->getMessage();
                } else {
                    $errorMessageSpecific = "";
                }
                $errorMessage = "There was an error connecting to the database.";
                configLogic::loadErrorPage($errorMessage, $errorMessageSpecific);
                exit;
            }
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues;"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $vlaue
     * @param array $whereNullArray the column in the where that should be NULL - form array($col,col2, col3 etc)
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectAndWhereIsNull($columns, $tables, array $whereValuesArray, array $whereNullArray, $singleRow=True) {
        assert(is_string($columns));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereIsNullColumnsSQL($whereNullArray, $where);
        $sql = "SELECT $columns FROM $tables WHERE $where;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues AND $column;"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $vlaue
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function select($columns, $tables, array $whereValuesArray, $singleRow=True) {
        assert(is_string($columns));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $sql = "SELECT $columns FROM $tables WHERE $where;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues ORDER BY $sortColumn"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param string $sortColumn The name of the column to sort by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectOrder($columns, $tables, array $whereValuesArray, $sortColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($sortColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $sql = "SELECT $columns FROM $tables WHERE $where ORDER BY $sortColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues AND $whereColumns"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn
     * @param boolean $singleRow return one row or many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectWithColumns($columns, $tables, array $whereValuesArray, array $whereColumnsArray, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $sql = "SELECT $columns FROM $tables WHERE $where;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues AND $whereColumns GROUP BY $sortColumn"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn
     * @param string $groupColumn The name of the column to group by
     * @param boolean $singleRow return one row or many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectWithColumnsGroupBy($columns, $tables, array $whereValuesArray, array $whereColumnsArray, 
            $groupColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($groupColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $sql = "SELECT $columns FROM $tables WHERE $where GROUP BY $groupColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues & $whereColumns ORDER BY $sortColumn"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $vlaue
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn
     * @param string $sortColumn The name of the column to sort by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectWithColumnsOrder($columns, $tables, array $whereValuesArray, array $whereColumnsArray, 
            $sortColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($sortColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $sql = "SELECT $columns FROM $tables WHERE $where ORDER BY $sortColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues & $whereColumns OR ($whereValues & $whereColumns) ORDER BY $sortColumn"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $vlaue
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn
     * @param array $whereValuesArray2  The input for the where clause (after the OR). form $column => $vlaue
     * @param array $whereColumnsArray2 The where matching tables(after the OR) to be selected by the SQL query. in the form of $column => $otherColumn    
     * @param string $sortColumn The name of the column to sort by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectWithColumnsOrSort($columns, $tables, array $whereValuesArray, array $whereColumnsArray,
            array $whereValuesArray2, array $whereColumnsArray2, $sortColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($sortColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $where .= " OR (";
        $where = self::prepareWhereValuesSQL($whereValuesArray2, $where); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray2, $where); //the columns
        $where .= ")";
        $sql = "SELECT $columns FROM $tables WHERE $where ORDER BY $sortColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray, $whereValuesArray2);
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues & $whereColumns OR ($whereValues & $whereColumns)"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn
     * @param array $whereValuesArray2  The input for the where clause (after the OR). form $column => $value
     * @param array $whereColumnsArray2 The where matching tables(after the OR) to be selected by the SQL query. in the form of $column => $otherColumn    
     * @param string $sortColumn The name of teh column to sort by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectWithColumnsOr($columns, $tables, array $whereValuesArray, array $whereColumnsArray,
            array $whereValuesArray2, array $whereColumnsArray2, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $where .= " OR (";
        $where = self::prepareWhereValuesSQL($whereValuesArray2, $where); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray2, $where); //the columns
        $where .= ")";
        $sql = "SELECT $columns FROM $tables WHERE $where;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray, $whereValuesArray2);
    }
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues AND $whereColumns AND $notNullColumn IS NOT NULL"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn
     * @param string $notNullColumn The name of the column which results are only returned for if the value is not null
     * @param boolean $singleRow return one row or many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */  
    public function selectWithColumnsIsNotNull($columns, $tables, array $whereValuesArray, 
         array $whereColumnsArray, $notNullColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($notNullColumn));       
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $sql = "SELECT $columns FROM $tables WHERE $where AND $notNullColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    /**
     * Runs a select query like: SELECT * FROM question_answer WHERE CONNECTION_ID NOT IN 
     * (SELECT PARENT_ID FROM question_answer WHERE PARENT_ID IS NOT NULL) 
     * AND TYPE = "answer" AND LOOP_CHILD_ID IS NULL
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $table The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param string $whereColumn The column where it's values are not found in the subquery
     * @param string $isNotInColumn The name of the column which results are only returned for if the value is not null
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param string $isNullColumn the col which is not NULL at the end of the query
     * @param boolean $singleRow return one row or many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */  
    public function selectWithSelectWhereColumnsIsNotinAnotherColumn($columns, $table, $whereColumn, 
         $isNotInColumn, array $whereValuesArray, $isNullColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($table));
        assert(is_string($isNotInColumn));       
        assert(is_bool($singleRow));
        $where = "$whereColumn NOT IN (SELECT $isNotInColumn FROM $table WHERE $isNotInColumn IS NOT NULL)";
        $where = self::prepareWhereValuesSQL($whereValuesArray, $where); //the values
        $where .= " AND $isNullColumn IS NULL";
        $table = strtolower($table); //lowercase tables
        $sql = "SELECT $columns FROM $table WHERE $where;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues AND $whereColumns AND $notNullColumn IS NOT NULL GROUP BY $sortColumn"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn
     * @param string $notNullColumn The name of the column which results are only returned for if the value is not null
     * @param string $groupColumn The name of the column to group by
     * @param boolean $singleRow return one row or many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
     public function selectWithColumnsIsNotNullGroupBy($columns, $tables, array $whereValuesArray, 
             array $whereColumnsArray, $notNullColumn, $groupColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($notNullColumn));
        assert(is_string($groupColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $sql = "SELECT $columns FROM $tables WHERE $where AND $notNullColumn GROUP BY $groupColumn;";
        
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }  
    
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $whereValues GROUP BY $groupColumn"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param string $groupColumn The name of the column to group by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectGroupBy($columns, $tables, array $whereValuesArray, $groupColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($groupColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $sql = "SELECT $columns FROM $tables WHERE $where GROUP BY $groupColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
       
    /**
     * Runs a select query like: "SELECT $column FROM $table WHERE $where GROUP BY $groupColumn"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereDateAfter The input for the where clause. form $column  => $value. run as $value > database column:value
     * @param array $whereDateBefore The input for the where clause. form $column  => $value. run as $value < database column:value
     * @param string $groupColumn The name of the column to group by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
     public function selectWithDateCheckGroupBy($columns, $tables, array $whereValuesArray,
            array $whereDateAfter, array $whereDateBefore, $groupColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($groupColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereValuesSQLLess($whereDateAfter, $where); //the values
        $where = self::prepareWhereValuesSQLGreaterIsNull($whereDateBefore, $where);
        $sql = "SELECT DISTINCT $columns FROM $tables WHERE $where GROUP BY $groupColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray, $whereDateAfter, $whereDateBefore);
    }
    /**
     * Runs a select query like: "SELECT $columns FROM ($columns FROM $tables WHERE $where ORDER BY $orderBy desc) as TEMP_TABLE 
     * LEFT JOIN $leftJoinTable ON $leftJoinWhereColumns GROUP BY $groupColumn;"
     * 
     * Used to order the result and then group the first of teh duplicates
     * 
     * @param string $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $innerColumns The columns to be selected in the nested SQL query ($columns must slect from these columns)
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param string $orderBy the column to be backwards ordered by
     * @param sting $leftJoinTable the table to join on
     * @param array $leftJoinWhereColumnsArray the array of the columns to align join with (the where)
     * @param array $whereOrValuesArray  The input for the where clause. form $column => $value
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereDateAfter The input for the where clause. form $column  => $value. run as $value > database column:value
     * @param array $whereDateBefore The input for the where clause. form $column  => $value. run as $value < database column:value
     * @param string $groupColumn The name of the column to group by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectOrderDescWithSelectWhereOrWithDateGroupBy($columns, $innerColumns, $tables, $orderBy,
            $leftJoinTable,array $leftJoinWhereColumnsArray, 
            array $whereOrValuesArray, array $whereValuesArray, array $whereDateAfter, array $whereDateBefore, $groupColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($groupColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQLOr($whereOrValuesArray); //the values
        $where = self::prepareWhereValuesSQL($whereValuesArray, $where);
        $where = self::prepareWhereValuesSQLLess($whereDateAfter, $where); //the values
        $where = self::prepareWhereValuesSQLGreaterIsNull($whereDateBefore, $where);
        $leftJoinWhereColumns = self::prepareWhereColumnsSQL($leftJoinWhereColumnsArray);
        $sql = "SELECT $columns FROM (SELECT $innerColumns FROM $tables ORDER BY $orderBy desc) as TEMP_TABLE ".
                "LEFT JOIN $leftJoinTable ON $leftJoinWhereColumns WHERE $where GROUP BY $groupColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereOrValuesArray, $whereValuesArray, $whereDateAfter, $whereDateBefore);
    }
    /**
     * Runs a select query like: "SELECT DISTINCT $column FROM $table WHERE $whereValues & $whereColumns OR ($whereValues & $whereColumns)"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn
     * @param array $whereValuesArray2  The input for the where clause (after the OR). form $column => $value
     * @param array $whereColumnsArray2 The where matching tables(after the OR) to be selected by the SQL query. in the form of $column => $otherColumn    
     * @param string $sortColumn The name of teh column to sort by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectDistinctWithColumnsOr($columns, $tables, array $whereValuesArray, array $whereColumnsArray,
            array $whereValuesArray2, array $whereColumnsArray2, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $where .= " OR (";
        $where = self::prepareWhereValuesSQL($whereValuesArray2, $where); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray2, $where); //the columns
        $where .= ")";
        $sql = "SELECT DISTINCT $columns FROM $tables WHERE $where;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray, $whereValuesArray2);
    }
    
    
    /**
     * Runs a select ALL query like: ""SELECT * FROM $tables;""
     * 
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @return array The results, eg result[15]['column']
     */
    public function selectAll($tables) {
        assert(is_string($tables));
        $tables = strtolower($tables); //lowercase tables
        $sql = "SELECT * FROM $tables;";
        return $this->runQueryReturnResults($sql, false);
    }
    
    /**
     * Runs a select ALL query like: ""SELECT * FROM $tables ORDER BY $orderColumn;""
     * 
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param string $sortColumn The name of teh column to sort by
     * @return array The results, eg result[15]['column']
     */
    
    public function selectAllOrder($tables, $sortColumn) {
        assert(is_string($tables));
        assert(is_string($sortColumn));
        $tables = strtolower($tables); //lowercase tables
        $sql = "SELECT * FROM $tables ORDER BY $sortColumn;";
        return $this->runQueryReturnResults($sql, false);
    }
    /**
     * Runs a insert like: "insert into $table ($columns) values ($values);"
     * 
     * @param array $insertArray  The input for the columns/values clause. form $column => $value
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @return string Returns the primary key of the insertion (eg quiz_id)
     */
    public function insert(array $insertArray, $tables) {
        assert(is_string($tables));
        $tables = strtolower($tables); //lowercase tables
        $columns = self::prepareInsertColumns($insertArray);
        $values = self::prepareInsertValues($insertArray);
        $sql = "insert into $tables ($columns) values ($values);";
        $this->runQuery($sql, $insertArray);
        return $lastInsertID = self::$connection->lastInsertID();
    }
    
    /**
     * Runs a insert like: "INSERT INTO $table ($columns [and $insertValues]) SELECT $columns, $values  FROM $table WHERE $whereValues"
     * 
     * @param string  $insertTables The tables for teh data to be insert into. form "table1, table2 etc"
     * @param string $insertColumns The columns to be have data put into. form "col1, col2, col3 etc"
     * @param string $selectColumns The columns to be slected by the slect query. Can also column a variable as well. eg "col1, col2 etc."
     * @param string $selectTables The tables to be selected by the SQL query. in the form of "table1, table2 etc"
     * @param array $whereValuesArray  The input for the where clause (values). form $column => $value
     * @param string $insertValuesArray The insert values for binding, in addition to the select clause (optional)
     * @return string Returns the primary key of the insertion (eg quiz_id)
     */
    public function insertWithSelectWhere($insertTables, $insertColumns, $selectColumns, $selectTables, array $whereValuesArray, $insertValuesArray = array()) {
        assert(is_string($insertTables));
        assert(is_string($insertColumns));
        assert(is_string($selectTables));
        $insertTables = strtolower($insertTables); //lowercase tables
        $selectColumns = strtolower($selectColumns); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $selectColumns = self::prepareInsertValues($insertValuesArray, $selectColumns);
        $sql = "INSERT INTO $insertTables ($insertColumns) ".
                "SELECT $selectColumns FROM $selectTables WHERE $where;";
        $this->runQuery($sql, $whereValuesArray, $insertValuesArray);
        return $lastInsertID = self::$connection->lastInsertID();
    }
    
    /**
     * Runs a delete like: "delete from $tables where $whereValuesArray AND "
     * 
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $deleteValues  The input for the where clause. form $column => $value
     * @return string Returns the primary key of the insertion (eg quiz_id)
     */
    public function delete($tables, $deleteValues) {
        /* @var $where string */
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($deleteValues); //the values        
        $sql = "delete from $tables where $where;";
        $this->runQuery($sql, $deleteValues);
    }


    /**
     * Runs a select query like: "SELECT DISTINCT $column FROM $table WHERE $whereValues & $whereColumns"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn   
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectDistinct($columns, $tables, array $whereValuesArray, array $whereColumnsArray, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $sql = "SELECT DISTINCT $columns FROM $tables WHERE $where;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }
    
    /**
     * Runs a select query like: "SELECT DISTINCT $column FROM $table WHERE $whereValues & $whereColumns"
     * 
     * @param string  $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn   
     * @param string $sortColumn The name of teh column to sort by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectDistinctOrder($columns, $tables, array $whereValuesArray, array $whereColumnsArray, $sortColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($sortColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $stmt = self::$connection->prepare("SELECT DISTINCT $columns FROM $tables WHERE " . $where . "ORDER BY $sortColumn;") or die('Problem preparing query');
        $sql = "SELECT DISTINCT $columns FROM $tables WHERE " . $where . "ORDER BY $sortColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereValuesArray);
    }

    /**
     * Runs a FULL outer join query like: ""SELECT $column FROM $table LEFT JOIN $joinTable ON $joinWhere LEFT JOIN $joinTable2 ON $joinWhere2 WHERE $where;""
     * 
     * @param string $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereData  The input for the where clause. form $column => $value
     * @param string $joinTable The table for the 1st join "LEFT JOIN $joinTable ON"
     * @param array $tableArray The 1st ON (where) matching tables to be selected by the SQL query. in the form of $column => $otherColumn 
     * @param string $joinTable2 The table for the 2nd join "LEFT JOIN $joinTable ON"  
     * @param array $tableArray2 The 2nd ON (where) matching tables to be selected by the SQL query. in the form of $column => $otherColumn  
     * @param boolean $singleRow return one row of many? true is the default (single row)
      * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectFullOuterJoin($columns, $tables, array $whereData, $joinTable, 
            $tableArray, $joinTable2, array $tableArray2,$singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($joinTable));
        assert(is_string($joinTable2));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereData);
        $joinWhere = self::prepareWhereColumnsSQL($tableArray);
        $joinWhere2 = self::prepareWhereColumnsSQL($tableArray2);
        $sql = "SELECT $columns FROM $tables " . 
                "LEFT JOIN $joinTable ON $joinWhere " . 
                "LEFT JOIN $joinTable2 ON $joinWhere2 WHERE $where;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereData);
    }
    
    /**
     * Runs a FULL outer join query like: ""SELECT $column FROM $table LEFT JOIN $joinTable ON $joinWhere LEFT JOIN $joinTable2 ON $joinWhere2 WHERE $where;""
     * 
     * @param string $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereData  The input for the where clause. form $column => $value
     * @param string $joinTable The table for the 1st join "LEFT JOIN $joinTable ON"
     * @param array $tableArray The 1st ON (where) matching tables to be selected by the SQL query. in the form of $column => $otherColumn 
     * @param string $joinTable2 The table for the 2nd join "LEFT JOIN $joinTable ON"  
     * @param array $tableArray2 The 2nd ON (where) matching tables to be selected by the SQL query. in the form of $column => $otherColumn  
     * @param string $sortColumn The name of teh column to sort by
     * @param boolean $singleRow return one row of many? true is the default (single row)
     * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectFullOuterJoinOrder($columns, $tables, array $whereData, $joinTable, 
            $tableArray, $joinTable2, array $tableArray2, $sortColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($joinTable));
        assert(is_string($joinTable2));
        assert(is_string($sortColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQL($whereData);
        $joinWhere = self::prepareWhereColumnsSQL($tableArray);
        $joinWhere2 = self::prepareWhereColumnsSQL($tableArray2);
        $sql = "SELECT $columns FROM $tables " . 
                "LEFT JOIN $joinTable ON $joinWhere " . 
                "LEFT JOIN $joinTable2 ON $joinWhere2 WHERE $where ORDER BY $sortColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereData);
    }
    
     /**
     * Runs a LEFT join query like: ""SELECT $column FROM $table LEFT JOIN $joinTable ON $joinWhere WHERE $where GROUP BY $groupColumn;""
     * 
     * @param string $columns The columns to be selected in the SQL query. In the form: "xx, yyy, max(zzz) etc"
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $whereData  The input for the where clause. form $column => $value
     * @param string $joinTable The table for the 1st join "LEFT JOIN $joinTable ON"
     * @param array $tableArray The ON (where) matching tables to be selected by the SQL query. in the form of $column => $otherColumn 
     * @param string $groupColumn The column name to group the results by 
     * @param boolean $singleRow return one row of many? true is the default (single row)
      * @return array The results, eg result[15]['column'] or result['column']
     */
    public function selectLeftJoinOrGroupBy($columns, $tables, array $whereData, $joinTable, 
            $tableArray, $groupColumn, $singleRow=True) {
        assert(is_string($columns));
        assert(is_string($tables));
        assert(is_string($joinTable));
        assert(is_string($groupColumn));
        assert(is_bool($singleRow));
        $tables = strtolower($tables); //lowercase tables
        $where = self::prepareWhereValuesSQLOr($whereData);
        $joinWhere = self::prepareWhereColumnsSQL($tableArray);
        $sql = "SELECT $columns FROM $tables " . 
                "LEFT JOIN $joinTable ON $joinWhere WHERE $where GROUP BY $groupColumn;";
        return $this->runQueryReturnResults($sql, $singleRow, $whereData);
    }
    
    /**
     * Updates columns. runs query like: UPDATE quiz SET SHARED_QUIZ_ID =  '16' WHERE QUIZ_ID = 16 AND $colum = $otherColumn;
     * 
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $setValuesArray The SET matching columns to be updated by the SQL query. in the form of $column => $value    
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @param array $whereColumnsArray The where matching tables to be selected by the SQL query. in the form of $column => $otherColumn  
     * @return void
     */
    public function updateSetWhereColumns($tables, array $setValuesArray,  array $whereValuesArray, array $whereColumnsArray) {
        assert(is_string($tables));
        $tables = strtolower($tables); //lowercase tables
        $setColumns = self:: prepareSetValuesSQL($setValuesArray); //the columns
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereColumnsSQL($whereColumnsArray, $where); //the columns
        $sql = "UPDATE $tables SET $setColumns WHERE $where;";
        $this->runQuery($sql, $whereValuesArray, $setValuesArray);
    }
    
    /**
     * Updates columns. runs query like: UPDATE quiz SET SHARED_QUIZ_ID =  SHARED_QUIZ_ID+1 WHERE QUIZ_ID = 16 AND $colum = $otherColumn;
     * 
     * This function does not escape/bind $setColumns. This is useful to increment a column etc
     * 
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $setColumns The SET matching columns to be updated by the SQL query. in the form of $column => $column  
     * @param array $whereValuesArray  The input for the where clause. form $column => $value
     * @return void
     */
    public function updateSetButSetNotEscaped($tables, array $setValuesArray, array $whereValuesArray) {
        assert(is_string($tables));
        $tables = strtolower($tables); //lowercase tables
        $setColumns = self::prepareSetValuesSQLNoBinding($setValuesArray); //the columns
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $sql = "UPDATE $tables SET $setColumns WHERE $where;";
        $this->runQuery($sql, $whereValuesArray);
    }
    
    /**
     * Updates columns. runs query like: UPDATE quiz SET SHARED_QUIZ_ID =  '16' WHERE QUIZ_ID = 16;
     * 
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $setValuesArray The SET matching tables to be updated by the SQL query. in the form of $column => $value    
     * @param array $whereValuesArray  The input for the where clause. form $column => $value  
     * @return void
     */
    public function updateSetWhere($tables, array $setValuesArray,  array $whereValuesArray) {
        assert(is_string($tables));
        $tables = strtolower($tables); //lowercase tables
        $setColumns = self:: prepareSetValuesSQL($setValuesArray); //the columns
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $sql = "UPDATE $tables SET $setColumns WHERE $where;";
        $this->runQuery($sql, $whereValuesArray, $setValuesArray);
    }
    
    /**
     * Updates columns. runs query like: UPDATE quiz SET SHARED_QUIZ_ID =  '16' WHERE QUIZ_ID = 16 and $col > "5";
     * 
     * @param string $tables The tables to be selected by the SQL query. in the form of "xx, yyy, zzz etc"
     * @param array $setValuesArray The SET matching tables to be updated by the SQL query. in the form of $column => $value    
     * @param array $whereValuesArray  The input for the where clause. form $column => $value  
     * @param array $greaterWhereValuesArray Additional input for the where clause. form $column => $value (col greater than value)  
     * @return void
     */
    public function updateSetWhereAndGreaterThanButSetNotEscaped($tables, array $setValuesArray, array $whereValuesArray, array $greaterWhereValuesArray) {
        assert(is_string($tables));
        $tables = strtolower($tables); //lowercase tables
        $setColumns = self::prepareSetValuesSQLNoBinding($setValuesArray); //the columns
        $where = self::prepareWhereValuesSQL($whereValuesArray); //the values
        $where = self::prepareWhereValuesGreaterThanSQL($greaterWhereValuesArray, $where); //the greater than values
        $sql = "UPDATE $tables SET $setColumns WHERE $where;";
        $this->runQuery($sql, $whereValuesArray, $greaterWhereValuesArray);
    }
    
    /**
     * Does the actual PDO query and returns the results
     * 
     * @param string $sql The SQL query
     * @param boolean $singleRow determine if single row returned or not. (true is one row, false is many)
     * @param array $arrays (unlimited number or arrays) the where values for binding - form $column => $value (optional)
     * 
     * returns array The SQL results
     */  
    private function runQueryReturnResults($sql, $singleRow /* + value arrays(unlimited) */){
        assert(is_string($sql));
        assert(is_bool($singleRow));
        $stmt = self::$connection->prepare($sql) or die('Problem preparing query');
        $args = array_slice(func_get_args(), 2); //ignore first two parameters
        foreach ($args as $valueArray) {
            $stmt = self::bindParams($stmt, $valueArray);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($singleRow && ($results)) {   //true and are actaully results
            $results = $results[0];   //return normal array instead
        }
        return $results;
    }
    
    /**
     * Does the actual PDO query (doesn't return any results)
     * 
     * @param string $sql The SQL query
     * @param array $arrays (unlimited number or arrays) the where values for binding - form $column => $value (optional)
     * 
     * returns array The SQL results
     */   
    private function runQuery($sql /* + value arrays(unlimited) */){
        assert(is_string($sql));
        $stmt = self::$connection->prepare($sql) or die('Problem preparing query');
        $args = array_slice(func_get_args(), 1); //ignore first parameter
        foreach ($args as $valueArray) {
            $stmt = self::bindParams($stmt, $valueArray);
        }
        $stmt->execute();
    }
    
    /**
     * Cleans the output (to the web broswer) by running htmlentities on it fisrt (stop cross site scripting)
     * 
     * @param string $output The data to be cleaned
     * @return string The cleaned data
     */
    private static function cleanTheOutput($output){
        assert(is_string($output));
        return htmlentities($output); //convert html entitiles like "<" to &lt;
    }
    
    /**
     * Converts the Where data array to a string in preapation for PDO
     * 
     * @param array $whereColumnsArray An assoicative array in the form of $column(or table.column) => $value 
     * @param string $where a string of the existing where sql query, values will be added on. (optional)
     * @return string Part of the SQL query
     */
    private static function prepareWhereColumnsSQL(array $whereColumnsArray, $where = ""){
        assert(is_string($where));
        foreach ($whereColumnsArray as $columnTemp => $valueTemp) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = $valueTemp";
        }
        return $where;
    }
    
    /**
     * Converts the Where data (IS NULL column) array to a string in preapation for PDO
     * 
     * @param array $whereColumnsArray An  array in the form array($col2, $col2, $col3 etc);
     * @param string $where a string of the existing where sql query, values will be added on. (optional)
     * @return string Part of the SQL query
     */
    private static function prepareWhereIsNullColumnsSQL(array $whereColumnsArray, $where = ""){
        assert(is_string($where));
        foreach ($whereColumnsArray as $columnTemp) {      //build coloumn where query
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp IS NULL";
        }
        return $where;
    }
    
    /**
     * Converts the Where data array to a string in preapation for PDO
     * 
     * @param array $whereValuesArray An assoicative array in the form of $column(or table.column) => $value 
     * @param string $where a string of the existing where sql query, values will be added on. (optional)
     * @return string Part of the SQL query
     */
    private static function prepareWhereValuesSQL(array $whereValuesArray, $where = ""){
        assert(is_string($where));
        foreach ($whereValuesArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp = :" . self::prepareColumnNameForBinding($columnTemp); //replace dot with underscore for table.column
        }
        return $where;
    }
    
    /**
     * Converts the Where data array to a string in preapation for PDO
     * 
     * @param array $whereValuesArray An assoicative array in the form of $column(or table.column) => $value 
     * @param string $where a string of the existing where sql query, values will be added on. (optional)
     * @return string Part of the SQL query
     */
    private static function prepareWhereValuesGreaterThanSQL(array $whereValuesArray, $where = ""){
        assert(is_string($where));
        foreach ($whereValuesArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp > :" . self::prepareColumnNameForBinding($columnTemp); //replace dot with underscore for table.column
        }
        return $where;
    }
    
    /**
     * Converts the Where data array to a string in preapation for PDO with the OR statement
     * 
     * @param array $whereValuesArray An assoicative array in the form of $column(or table.column) => $value 
     * @param string $where a string of the existing where sql query, values will be added on. (optional)
     * @return string Part of the SQL query
     */
    private static function prepareWhereValuesSQLOr(array $whereValuesArray, $where = ""){
        assert(is_string($where));
        foreach ($whereValuesArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "(" : " OR ";
            $where .= "$columnTemp = :" . self::prepareColumnNameForBinding($columnTemp); //replace dot with underscore for table.column
        }
        $where .= ')';
        return $where;
    }

    /**
     * Converts the Where data array to a string in preapation for PDO with the LESS THAN statement
     * 
     * @param array $whereValuesArray An assoicative array in the form of $column(or table.column) => $value 
     * @param string $where a string of the existing where sql query, values will be added on. (optional)
     * @return string Part of the SQL query
     */
    private static function prepareWhereValuesSQLLess (array $whereValuesArray, $where = ""){
        assert(is_string($where));
        foreach ($whereValuesArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "$columnTemp < :" . self::prepareColumnNameForBinding($columnTemp); //replace dot with underscore for table.column
        }
        return $where;
    }
    
    /**
     * Converts the Where data array to a string in preapation for PDO with the IS NULL OR GREATER THAN statement
     * 
     * @param array $whereValuesArray An assoicative array in the form of $column(or table.column) => $value 
     * @param string $where a string of the existing where sql query, values will be added on. (optional)
     * @return string Part of the SQL query
     */
    private static function prepareWhereValuesSQLGreaterIsNull (array $whereValuesArray, $where = ""){
        assert(is_string($where));
        foreach ($whereValuesArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $where .= ($where == "") ? "" : " AND ";
            $where .= "($columnTemp IS NULL OR $columnTemp > :" . self::prepareColumnNameForBinding($columnTemp).")"; //replace dot with underscore for table.column
        }
        return $where;
    }

    /**
     * Binds the variables in the PDO connection
     * 
     * @param $stmt The statement as prepared previsously by PDO.
     * @param $whereValuesArray An assoicative array in the form of $column(or table.column) => $value
     * @return The binded stmt
     */
    private static function bindParams(PDOStatement $stmt, array $whereValuesArray){
        foreach ($whereValuesArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $stmt->bindValue(':' . self::prepareColumnNameForBinding($columnTemp), $valueTemp);
        }
        return $stmt;
    }
    
    /**
     * Prepares the coloumn name (table.column) by replacing the dot with a underscore (doesn't affact the query)
     * 
     * @param $columnName The name of the column to be renamed/prepared
     * @return string The prepared column name
     */
    private static function prepareColumnNameForBinding($columnName){
        assert(is_string($columnName));
        $columnName =  preg_replace('/\\./', '_', $columnName);
        return $columnName;
    }
    
    /**
     * Prepares the Insert values by creating the approiate placeholders for binding
     * 
     * @param array $insertArray  The input for the where clause. form $column => $value
     * @param string $values The existing values if any need adding on (optional)
     * @return string Returns the primary key of the insertion (eg quiz_id)
     */
    private static function prepareInsertValues (array $insertArray, $values = ""){
        assert(is_string($values));
        foreach ($insertArray as $column => $valueTemp) { //$value not used, it's in execute
            $values .= ($values == "") ? "" : ", ";
            $values .= ":".self::prepareColumnNameForBinding($column);
        }
        return $values;
    }
    
    /**
     * Prepares the insert columns by building string with commas in-between
     * 
     * @param array $insertArray  The input for the where clause. form $column => $value
     * @param string $columns The existing columns if any need adding on (optional)
     * @return string A string with words and commas for the SQL query
     */
    private static function prepareInsertColumns (array $insertArray, $columns = ""){
        assert(is_string($columns));
        foreach ($insertArray as $column =>$valueTemp) { //$value not used, it's in execute
            $columns .= ($columns == "") ? "" : ", ";
            $columns .= $column;
        }
        return $columns;
    }
    
    /**
     * Converts the Where data array to a string in preapation for PDO
     * 
     * @param array $setValuesArray An assoicative array for the SET query in the form of $column(or table.column) => $value 
     * @param string $setColumns a part string of the existing set values query, form SET Column = value
     * @return string Part of the SQL query in SET Column = value
     */
    private static function prepareSetValuesSQL(array $setValuesArray, $setColumns = ""){
        assert(is_string($setColumns));
        foreach ($setValuesArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $setColumns .= ($setColumns == "") ? "" : ", ";
            $setColumns .= "$columnTemp = :" . self::prepareColumnNameForBinding($columnTemp); //replace dot with underscore for table.column
        }
        return $setColumns;
    }
    
    /**
     * Converts the Where data array to a string in preapation for PDO The vlaue is not escaped, use with caution
     * 
     * @param array $setValuesArray An assoicative array for the SET query in the form of $column(or table.column) => $column+1 etc
     * @param string $setColumns a part string of the existing set values query, form SET Column = value
     * @return string Part of the SQL query in SET Column = value
     */
    private static function prepareSetValuesSQLNoBinding(array $setValuesArray, $setColumns = ""){
        assert(is_string($setColumns));
        foreach ($setValuesArray as $columnTemp => $valueTemp) {      //$value not used - it's in $data
            $setColumns .= ($setColumns == "") ? "" : ", ";
            $setColumns .= "$columnTemp = $valueTemp"; //replace dot with underscore for table.column
        }
        return $setColumns;
    }
}
