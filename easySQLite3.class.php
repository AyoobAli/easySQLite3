<?php

/**
 * Name: easySQLite3 
 * Description: A PHP class that makes talking to SQLite3 Database easier, and it takes care of prepared statements.
 * Author: Ayoob Ali
 * Website: www.Ayoob.ae
 * License: GNU GPLv3
 * Version: v0.1.0
 * Date: 2020-04-11
 */

/**
    easySQLite3 is a PHP class that makes talking to SQLite3 Database easier,
    and it takes care of prepared statements.
    Copyright (C) 2020  Ayoob Ali

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>
*/


class easySQLite3 extends SQLite3
{

    private $db             = "";
    private $dbName         = "";
    private $tableName      = "";
    private $connected      = false;
    private $verboseLevel   = 1;
    private $isHTML         = false;
    private $curColumns     = [];
    private $curData        = [];
    private $curCondition   = [];
    private $curOrderBy     = "";
    private $curLimit       = "";
    private $curTable       = "";
    private $lastMessage    = "";


    ### 
    ### Class Construct
    ###
    function __construct($database = "") {
        if (isset($database) && !empty($database)) {
            $this->connect($database);
        }
    }

    ### 
    ### Class Destruct
    ###
    function __destruct() {
        if ($this->connected === true) {
            $this->disconnect();
        }
    }

    ### 
    ### Writing Messages
    ###
    private function msg($message = "", $level = 1) {
        $this->lastMessage = $message;
        if ($this->verboseLevel >= $level) {
            if ($this->isHTML == true) {
                echo htmlentities($message, ENT_QUOTES) . "<br>\n";
            }else{
                echo $message . "\n";
            }
            return true;
        }
        return false;
    }

    ### 
    ### Get Last Messages
    ###
    public function getMessage() {
        return $this->lastMessage;
    }

    ### 
    ### Set Verbose Level
    ###
    public function setVerbose($level = 1) {
        $this->verboseLevel = intval($level);
        if (intval($this->verboseLevel) == 0) {
            error_reporting(0);
        }
        return $this->verboseLevel;
    }

    ###
    ### Get Verbose Level
    ###
    public function getVerbose() {
        return $this->verboseLevel;
    }

    ### 
    ### Set newline as HTML
    ###
    public function setHTML($html = true) {
        if ($html == true) {
            $this->isHTML = true;
        }else{
            $this->isHTML = false;
        }
        return $this->isHTML;
    }

    ###
    ### Check if a string starts with
    ###
    private function startWith($string = "", $startWith = "") {
        $string = strtolower($string);
        if (is_array($startWith)) {
            foreach ($startWith as $key => $value) {
                $value = strtolower($value);
                if ( substr($string, 0, strlen($value)) === $value ) {
                    return true;
                }
            }
        }else{
            $startWith = strtolower($startWith);
            if ( substr($string, 0, strlen($startWith)) === $startWith ) {
                return true;
            }
        }
        return false;
    }

    ###
    ### Check if a string contains a specific text
    ###
    private function isContain($string = "", $contains = "") {
        $string = strtolower($string);
        if (is_array($contains)) {
            foreach ($contains as $key => $value) {
                $value = strtolower($value);
                if ( strpos($string, $value) !== false) {
                    return true;
                }
            }
        }else{
            $contains = strtolower($contains);
            if ( strpos($string, $contains) !== false) {
                return true;
            }
        }
        return false;
    }

    ###
    ### Check if Database is connected
    ###
    public function isConnected() {
        return $this->connected;
    }

    ###
    ### Set Table to use
    ###
    public function setTable($table_name = "") {
        $this->curTable = $table_name; 
        return $this->curTable;
    }

    ###
    ### Get Used Table
    ###
    public function getTable() {
        return $this->curTable;
    }

    ### 
    ### Set Columns
    ###
    public function setColumns($column_name = "", $column_type = "", $isPrimary = false, $isUnique = false, $isAutoincrement = false) {
        if ( empty(trim($column_name)) ) {
            $this->msg("[Columns]: Column name not specified.", 2);
            $this->msg("[Columns]: Column type should be: INTEGER, TEXT, REAL, NUMERIC, or BLOB.", 3);
            if (is_array($this->curColumns) and count($this->curColumns) > 0 ) {
                $curColumns = implode(", ", $this->curColumns);
                $this->msg("[Columns]: " . $curColumns, 3);
            }
            return false;
        }

        $column_type = strtolower($column_type);

        switch (true) {

            case $this->isContain($column_type, "int"):
            case $this->isContain($column_type, "unsigned"):

                $this->curColumns[$column_name] = "INTEGER";
                break;

            case $this->isContain($column_type, "char"):
            case $this->isContain($column_type, "text"):
            case $this->isContain($column_type, "txt"):
            case $this->isContain($column_type, "clob"):
            case $this->isContain($column_type, "var"):

                $this->curColumns[$column_name] = "TEXT";
                break;

            case $this->isContain($column_type, "real"):
            case $this->isContain($column_type, "doub"):
            case $this->isContain($column_type, "floa"):

                $this->curColumns[$column_name] = "REAL";
                break;

            case $this->isContain($column_type, "num"):
            case $this->isContain($column_type, "deci"):
            case $this->isContain($column_type, "bool"):
            case $this->isContain($column_type, "date"):
            case $this->isContain($column_type, "time"):

                $this->curColumns[$column_name] = "NUMERIC";
                break;

            case $this->isContain($column_type, "blob"):
            case $this->isContain($column_type, "bin"):

                $this->curColumns[$column_name] = "BLOB";
                break;

            default:
                $this->msg("[Columns]: Column type '$column_type' is incorrect.", 2);
                return false;

        }
        if ($isPrimary == true) {
            if ($isAutoincrement == true and $this->curColumns[$column_name] == "INTEGER") {
                $this->curColumns[$column_name] .= " PRIMARY KEY AUTOINCREMENT";
            }else{
                $this->curColumns[$column_name] .= " PRIMARY KEY";
            }
            
        }
        if ($isUnique == true) {
            $this->curColumns[$column_name] .= " UNIQUE";
        }
        return $this->curColumns;
    }

    ### 
    ### Get Columns
    ###
    public function getColumns() {
        return $this->curColumns;
    }

    ### 
    ### Clear Columns
    ###
    public function clearColumns() {
        $this->curColumns = [];
        return true;
    }

    ### 
    ### Set Data
    ###
    public function setData($column_name = "", $column_value = "") {
        if ( empty(trim($column_name)) ) {
            $this->msg("[Data]: Column name not specified.", 2);
            if (is_array($this->curData) and count($this->curData) > 0) {
                $curData = implode(", ", $this->curData);
                $this->msg("[Data]: " . $curData, 3);
            }
            return false;
        }
        $this->curData[$column_name] = $column_value;
        return $this->curData;
    }

    ### 
    ### Get Data
    ###
    public function getData() {
        return $this->curData;
    }

    ### 
    ### Clear Data
    ###
    public function clearData() {
        $this->curData = [];
        return true;
    }

    ### 
    ### Set Conditions
    ###
    public function setConditions($column_name = "", $column_value = "", $operation = "=", $prefix = "AND", $suffix = "") {
        if ( empty(trim($column_name)) ) {
            $this->msg("[Conditions]: Column name not specified.", 2);
            if (is_array($this->curCondition) and count($this->curCondition) > 0) {
                $curCondition = implode(" ", $this->curCondition);
                $this->msg("[Conditions]: " . $curCondition, 3);
            }
            return false;
        }
        if ($this->curCondition == [] and $prefix == "AND") {
            $prefix = "";
        }
        $conIndex = 0;
        if (is_array($this->curCondition)) {
            $conIndex = count($this->curCondition);
        }
        $this->curCondition[$conIndex]['con'] = $prefix . " " . $column_name . " " . $operation . " ? " . $suffix . " ";
        $this->curCondition[$conIndex]['val'] = $column_value;
        return $this->curCondition;
    }

    ### 
    ### Get Conditions
    ###
    public function getConditions() {
        return $this->curCondition;
    }

    ### 
    ### Clear Conditions
    ###
    public function clearConditions() {
        $this->curCondition = [];
        return true;
    }

    ###
    ### Set Order By
    ###
    public function setOrder($column_name = "", $is_desc = false) {
        if ( empty(trim($column_name)) ) {
            $this->msg("[Order]: Column name not specified.", 2);
            $this->msg("[Order]: " . $this->curOrderBy, 3);
            return false;
        }
        if ($is_desc == true) {
            $this->curOrderBy = $column_name . " DESC";
        }else{
            $this->curOrderBy = $column_name . " ASC";
        }
        return $this->curOrderBy;
    }

    ### 
    ### Get Order By
    ###
    public function getOrder() {
        return $this->curOrderBy;
    }

    ### 
    ### Clear Order By
    ###
    public function clearOrder() {
        $this->curOrderBy = "";
        return true;
    }

    ###
    ### Set Limit
    ###
    public function setLimit($limit_count = "", $limit_offset = "") {
        if ( !is_numeric($limit_count) ) {
            $this->msg("[Limit]: Limit not specified.", 2);
            $this->msg("[Limit]: " . $this->curLimit, 3);
            return false;
        }

        $this->curLimit = "LIMIT " . intval($limit_count);

        if ( is_numeric($limit_offset) ) {
            $this->curLimit .= " OFFSET " . intval($limit_offset);
        }
        return $this->curLimit;
    }

    ### 
    ### Get Limit
    ###
    public function getLimit() {
        return $this->curLimit;
    }

    ### 
    ### Clear Limit
    ###
    public function clearLimit() {
        $this->curLimit = "";
        return true;
    }

    ### 
    ### Check if Tables exist
    ###
    public function isTable($table_name = "") {
        if ( $this->connected === false ) {
            $this->msg("[TableCheck]: Database is not connected.", 2);
            return false;
        }
        if ( empty(trim($table_name)) ) {
            $this->msg("[TableCheck]: Table name not specified.", 2);
            return false;
        }
        $sqlQ = "SELECT name FROM sqlite_master WHERE type='table';";
        $results = $this->db->query($sqlQ);
        $isTable = false;
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            if (trim($row['name']) == trim($table_name)) {
                $isTable = true;
            }
        }
        return $isTable;
    }

    ### 
    ### Connecting to Database
    ###
    public function connect($database = "") {
        if ($this->connected === true) {
            $this->msg("[Connect]: Database already connected.", 2);
            return false;
        }
        if ( !empty($database) ) {
            $this->db = new SQLite3($database);
            if (is_object($this->db)) {
                $this->connected = true;
                $this->dbName = $database;
                $this->msg("[Connect]: Database connected.", 3);
                return true;
            }else{
                $this->msg("[Connect]: Can't Connect to Database.", 2);
                return false;
            }
        }else{
            $this->msg("[Connect]: Database name not specified.", 2);
            return false;
        }
    }

    ### 
    ### Disconnecting from Database
    ###
    public function disconnect() {
        unset($this->db);
        $this->db             = "";
        $this->dbName         = "";
        $this->tableName      = "";
        $this->connected      = false;
        $this->verboseLevel   = 1;
        $this->isHTML         = false;
        $this->curColumns     = [];
        $this->curData        = [];
        $this->curCondition   = [];
        $this->curOrderBy     = "";
        $this->curLimit       = "";
        $this->curTable       = "";
        $this->lastMessage    = "";
        $this->msg("[Disconnect]: Database disconnected.", 3);
        return true;
    }

    ### 
    ### Creating table
    ###
    public function createTable($table_name = "", $columns_array = "") {
        if ( $this->connected === false ) {
            $this->msg("[Create]: Database is not connected.", 2);
            return false;
        }
        if ( empty(trim($table_name)) ) {
            $table_name = $this->curTable;
        }
        if ( empty(trim($table_name)) ) {
            $this->msg("[Create]: Table name not specified.", 2);
            return false;
        }
        if ( !is_array($columns_array) ) {
            $columns_array = $this->curColumns;
        }
        if ( !is_array($columns_array) or count($columns_array) == 0 ) {
            $this->msg("[Create]: Columns data not specified.", 2);
            return false;
        }
        if ($this->isTable($table_name)) {
            $this->msg("[Create]: Table already exist.", 2);
            return false;
        }
        $tmpArr = [];
        foreach ($columns_array as $key => $value) {
            $tmpArr[] = $key . " " . $value;
        }
        $sqlQ = "CREATE TABLE " . trim($table_name) . " (";
        $sqlQ .= implode(", ", $tmpArr);
        $sqlQ .= ")";
        $reval = $this->db->exec($sqlQ);
        $this->msg("[Create]: Table Created.", 3);
        return $reval;
    }

    ### 
    ### Show Tables
    ###
    public function showTables() {
        if ( $this->connected === false ) {
            $this->msg("[Tables]: Database is not connected.", 2);
            return false;
        }

        $sqlQ = "SELECT name FROM sqlite_master WHERE type='table';";
        $results = $this->db->query($sqlQ);
        $reval = [];
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $reval[] = $row['name'];
        }
        return $reval;
    }

    ### 
    ### Insert Record
    ###
    public function insertRecord($table_name = "", $data_array = "") {
        if ( $this->connected === false ) {
            $this->msg("[Insert]: Database is not connected.", 2);
            return false;
        }
        if ( empty(trim($table_name)) ) {
            $table_name = $this->curTable;
        }
        if ( empty(trim($table_name)) ) {
            $this->msg("[Insert]: Table name not specified.", 2);
            return false;
        }
        if ( $this->isTable($table_name) == false ) {
            $this->msg("[Insert]: Table doesn't exist.", 2);
            return false;
        }
        if ( !is_array($data_array) ) {
            $data_array = $this->curData;
        }
        if ( !is_array($data_array) or count($data_array) == 0 ) {
            $this->msg("[Insert]: Data not specified.", 2);
            return false;
        }
        $sqlQ = "INSERT INTO " . trim($table_name) . " ";
        $cname = [];
        $cpram = [];

        foreach ($data_array as $key => $value) {
            $cname[] = $key;
            $cpram[] = "?";
        }

        $sqlQ .= "(" . implode(", ", $cname) . ") VALUES ";
        $sqlQ .= "(" . implode(", ", $cpram) . ")";

        $sth = $this->db->prepare($sqlQ);

        if ( $sth == false ) {
            $this->msg("Insert]: Wrong Query '" . $sqlQ . "'.", 2);
            return false;
        }

        $pramCount = 0;
        foreach ($data_array as $key => $value) {
            $pramCount++;
            $sth->bindValue($pramCount, $value);
        }
        $reval = $sth->execute();
        $this->msg("[Insert]: Data inserted.", 3);
        return $reval;
    }

    ### 
    ### Update Records
    ###
    public function updateRecords($table_name = "", $data_array = "", $sttCondition = "", $order_by = "", $limit = "") {
        if ( $this->connected === false ) {
            $this->msg("[Update]: Database is not connected.", 2);
            return false;
        }
        if ( empty(trim($table_name)) ) {
            $table_name = $this->curTable;
        }
        if ( empty(trim($table_name)) ) {
            $this->msg("[Update]: Table name not specified.", 2);
            return false;
        }
        if ( $this->isTable($table_name) == false ) {
            $this->msg("[Update]: Table doesn't exist.", 2);
            return false;
        }
        if ( !is_array($data_array) ) {
            $data_array = $this->curData;
        }
        if ( !is_array($data_array) or count($data_array) == 0) {
            $this->msg("[Update]: Data not specified.", 2);
            return false;
        }
        if (is_array($this->curCondition) and count($this->curCondition) > 0 and !is_array($sttCondition)) {
            $sttCondition = $this->curCondition;
        }
        if ( !is_array($sttCondition) ) {
            $this->msg("[Update]: Condition not specified.", 2);
            return false;
        }

        $sqlQ = "UPDATE " . trim($table_name) . " SET ";
        $cname = [];
        
        foreach ($data_array as $key => $value) {
            $cname[] = $key . "=?";
        }

        $sqlQ .= implode(", ", $cname) . " ";

        $sqlQ .= " WHERE ";
        foreach ($sttCondition as $key => $value) {
            $sqlQ .= $value['con'];
        }

        if (!empty(trim($this->curOrderBy)) and empty(trim($order_by))) {
            $order_by = $this->curOrderBy;
        }
        if ( !empty(trim($order_by)) ) {
            $sqlQ .= " ORDER BY " .  $order_by;
        }

        if (!empty(trim($this->curLimit)) and empty(trim($limit))) {
            $limit = $this->curLimit;
        }
        if ( !empty(trim($limit)) ) {
            $sqlQ .= " " .  $limit;
        }

        $sth = $this->db->prepare($sqlQ);

        if ( $sth == false ) {
            $this->msg("Update]: Wrong Query '" . $sqlQ . "'.", 2);
            return false;
        }

        $pramCount = 0;
        foreach ($data_array as $key => $value) {
            $pramCount++;
            $sth->bindValue($pramCount, $value);
        }

        foreach ($sttCondition as $key => $value) {
            $pramCount++;
            $sth->bindValue($pramCount, $value['val']);
        }
        
        $reval = $sth->execute();
        $this->msg("[Update]: Data updated.", 3);
        return $reval;
    }

    ### 
    ### Show Records
    ###
    public function showRecords($table_name = "", $sttCondition = "", $orderby = "", $limit = "") {
        if ( $this->connected === false ) {
            $this->msg("[Query]: Database is not connected.", 2);
            return false;
        }
        if ( empty(trim($table_name)) ) {
            $table_name = $this->curTable;
        }
        if ( empty(trim($table_name)) ) {
            $this->msg("[Query]: Table name not specified.", 2);
            return false;
        }
        if ( $this->isTable($table_name) == false ) {
            $this->msg("[Query]: Table doesn't exist.", 2);
            return false;
        }

        $sqlQ = "SELECT * FROM $table_name ";

        if (is_array($this->curCondition) and count($this->curCondition) > 0 and !is_array($sttCondition)) {
            $sttCondition = $this->curCondition;
        }
        if ( is_array($sttCondition) ) {
            $sqlQ .= " WHERE ";
            foreach ($sttCondition as $key => $value) {
                $sqlQ .= $value['con'];
            }
        }
        if (!empty(trim($this->curOrderBy)) and empty(trim($orderby))) {
            $orderby = $this->curOrderBy;
        }
        if ( !empty(trim($orderby)) ) {
            $sqlQ .= " ORDER BY " .  $orderby;
        }

        if (!empty(trim($this->curLimit)) and empty(trim($limit))) {
            $limit = $this->curLimit;
        }
        if ( !empty(trim($limit)) ) {
            $sqlQ .= " " .  $limit;
        }
        
        $sth = $this->db->prepare($sqlQ);
        
        if ( $sth == false ) {
            $this->msg("[Query]: Wrong Query '" . $sqlQ . "'.", 2);
            return false;
        }

        if ( is_array($sttCondition) ) {
            $pramCount = 0;
            foreach ($sttCondition as $key => $value) {
                $pramCount++;
                $sth->bindValue($pramCount, $value['val']);
            }
        }
        $results = $sth->execute();
        $reval = [];
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $reval[] = $row;
        }
        return $reval;
    }

    ### 
    ### Delete Records
    ###
    public function deleteRecords($table_name = "", $sttCondition = "", $orderby = "", $limit = "") {
        if ( $this->connected === false ) {
            $this->msg("[Delete]: Database is not connected.", 2);
            return false;
        }
        if ( empty(trim($table_name)) ) {
            $table_name = $this->curTable;
        }
        if ( empty(trim($table_name)) ) {
            $this->msg("[Delete]: Table name not specified.", 2);
            return false;
        }
        if ( $this->isTable($table_name) == false ) {
            $this->msg("[Delete]: Table doesn't exist.", 2);
            return false;
        }

        $sqlQ = "DELETE FROM $table_name ";

        if (is_array($this->curCondition) and count($this->curCondition) > 0 and !is_array($sttCondition)) {
            $sttCondition = $this->curCondition;
        }
        if ( !is_array($sttCondition) ) {
            $this->msg("[Delete]: Condition not specified.", 2);
            return false;
        }

        $sqlQ .= " WHERE ";
        foreach ($sttCondition as $key => $value) {
            $sqlQ .= $value['con'];
        }
        
        if (!empty(trim($this->curOrderBy)) and empty(trim($orderby))) {
            $orderby = $this->curOrderBy;
        }
        if ( !empty(trim($orderby)) ) {
            $sqlQ .= " ORDER BY " .  $orderby;
        }

        if (!empty(trim($this->curLimit)) and empty(trim($limit))) {
            $limit = $this->curLimit;
        }
        if ( !empty(trim($limit)) ) {
            $sqlQ .= " " .  $limit;
        }
        
        $sth = $this->db->prepare($sqlQ);

        if ( $sth == false ) {
            $this->msg("[Delete]: Wrong Query '" . $sqlQ . "'.", 2);
            return false;
        }

        if ( is_array($sttCondition) ) {
            $pramCount = 0;
            foreach ($sttCondition as $key => $value) {
                $pramCount++;
                $sth->bindValue($pramCount, $value['val']);
            }
        }
        $results = $sth->execute();
        $this->msg("[Delete]: Data deleted.", 3);
        return $results;
    }

    ###
    ### Fetch specific query
    ###
    public function fetchQuery($query = "", $bindValue = "") {
        if ( $this->connected === false ) {
            $this->msg("[fetchQuery]: Database is not connected.", 2);
            return false;
        }
        if ( empty(trim($query)) ) {
            $this->msg("[fetchQuery]: Query is not specified.", 2);
            return false;
        }

        $sth = $this->db->prepare($query);

        if ( $sth == false ) {
            $this->msg("[fetchQuery]: Wrong Query '" . $query . "'.", 2);
            return false;
        }

        if ( is_array($bindValue) && count($bindValue) > 0 ) {
            $pramCount = 0;
            foreach ($bindValue as $key => $value) {
                $pramCount++;
                $sth->bindValue($pramCount, $value);
            }
        }
        $results = $sth->execute();
        $reval = [];
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $reval[] = $row;
        }
        return $reval;
    }

}

?>