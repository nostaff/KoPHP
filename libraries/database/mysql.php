<?php
defined('SYS_PATH') or die('No direct access allowed.');

require_once 'driver.php';
/**
 * MySQL Database connection
 *
 * @package    Core
 * @author     Ko Team
 * @version    $Id: mysql.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Database_Mysql extends Database_Driver
{

    /**
     * Database connection link
     */
    protected $_link;

    /**
     * Database configuration
     */
    protected $_config;

    /**
     * Sets the config for the class.
     *
     * @param  array  database configuration
     * @return Database_Mysql
     */
    public function __construct ($config)
    {
        $this->_config = $config;
    }

    /**
     * Closes the database connection.
     */
    public function __destruct ()
    {
        is_resource($this->_link) && mysql_close($this->_link);
    }

    public function connect ()
    {
        if (is_resource($this->_link)) {
            mysql_ping($this->_link);
            return $this->_link;
        }

        // Persistent connections enabled?
        $connect = ($this->_config['persistent'] == TRUE) ? 'mysql_pconnect' : 'mysql_connect';

        $host = isset($this->_config['host']) ? $this->_config['host'] : $this->_config['socket'];
        $host .= isset($this->_config['port']) ? ':' . $this->_config['port'] : '';
        if (($this->_link = $connect($host, $this->_config['username'], $this->_config['password'], TRUE)) && mysql_select_db($this->_config['database'], $this->_link)) {
            isset($this->_config['charset']) && $this->charset($this->_config['charset']);
            $this->_config['pass'] = NULL;
            return $this->_link;
        }
        return FALSE;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $sql
     * @return Mysql_Result | mixed
     */
    public function query ($sql)
    {
        // Make sure the database is connected
        $this->_link or $this->connect();
        // Execute the query
        if (($result = mysql_query($sql, $this->_link)) === FALSE) {
            throw new KoException('[:errno] :error [ :query ]', array(':error' => mysql_error($this->_link) , ':query' => $sql, 'errno' => mysql_errno($this->_link)));
        }
        // If the query is a resource, it was a SELECT, SHOW, DESCRIBE, EXPLAIN query
        if (is_resource($result)) {
            return new Mysql_Result($result, $this->_link);
        }
        // Its an DELETE, INSERT, REPLACE, or UPDATE query
        elseif (is_bool($result)) {
            if(stripos($sql, 'INSERT') !== FALSE) {
            	$this->_insertid = mysql_insert_id($this->_link) ;
                return $this->_insertid ? $this->_insertid : mysql_affected_rows($this->_link);
            } else {
                return mysql_affected_rows($this->_link);
            }
        }
    }

    public function charset ($charset)
    {
        is_resource($this->_link) || $this->connect();
        if (mysql_set_charset($charset, $this->_link) === FALSE) {
            throw new KoException('[:errno] :error', array(':error' => mysql_error($this->_link), ':errno' => mysql_errno($this->_link)));
        }
    }

    public function escapeTable ($table)
    {
        if (stripos($table, ' AS ') !== FALSE) {
            // Force 'AS' to uppercase
            $table = str_ireplace(' AS ', ' AS ', $table);
            // Runs escapeTable on both sides of an AS statement
            $table = array_map(array($this , __FUNCTION__), explode(' AS ', $table));
            // Re-create the AS statement
            return implode(' AS ', $table);
        }
        return '`' . str_replace('.', '`.`', $table) . '`';
    }

    public function escapeColumn ($column)
    {
        if ($column == '*')
            return $column;
            // This matches any functions we support to SELECT.
        if (preg_match('/(avg|count|sum|max|min)\(\s*(.*)\s*\)(\s*as\s*(.+)?)?/i', $column, $matches)) {
            if (count($matches) == 3) {
                return $matches[1] . '(' . $this->escapeColumn($matches[2]) . ')';
            } else
                if (count($matches) == 5) {
                    return $matches[1] . '(' . $this->escapeColumn($matches[2]) . ') AS ' . $this->escapeColumn($matches[2]);
                }
        }
        // This matches any modifiers we support to SELECT.
        if (! preg_match('/\b(?:rand|all|distinct(?:row)?|high_priority|sql_(?:small_result|b(?:ig_result|uffer_result)|no_cache|ca(?:che|lc_found_rows)))\s/i', $column)) {
            if (stripos($column, ' AS ') !== FALSE) {
                // Force 'AS' to uppercase
                $column = str_ireplace(' AS ', ' AS ', $column);
                // Runs escapeColumn on both sides of an AS statement
                $column = array_map(array($this , __FUNCTION__), explode(' AS ', $column));
                // Re-create the AS statement
                return implode(' AS ', $column);
            }
            return preg_replace('/[^.*]+/', '`$0`', $column);
        }
        $parts = explode(' ', $column);
        $column = '';
        for ($i = 0, $c = count($parts); $i < $c; $i ++) {
            if ($i == ($c - 1)) {
                $column .= preg_replace('/[^.*]+/', '`$0`', $parts[$i]);
            } else {
                $column .= $parts[$i] . ' ';
            }
        }
        return $column;
    }

    public function replace ($table, $keys, $values)
    {
        foreach ($keys as $key => $value) {
            $keys[$key] = $this->escapeColumn($value);
        }
        return 'REPLACE INTO ' . $this->escapeTable($table) . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ')';
    }

    public function limit ($limit, $offset = 0)
    {
        return 'LIMIT ' . $offset . ', ' . $limit;
    }

    public function select ($select)
    {
        $sql = ($select['_distinct'] == TRUE) ? 'SELECT DISTINCT ' : 'SELECT ';
        $sql .= (count($select['_select']) > 0) ? implode(', ', $select['_select']) : '*';
        if (count($select['_from']) > 0) {
            // Escape the tables
            $froms = array();
            foreach ($select['_from'] as $from) {
                $froms[] = $this->escapeColumn($from);
            }
            $sql .= "\nFROM (";
            $sql .= implode(', ', $froms) . ")";
        }
        if (count($select['_join']) > 0) {
            foreach ($select['_join'] as $join) {
                $sql .= "\n" . $join['type'] . 'JOIN ' . implode(', ', $join['tables']) . ' ON ' . $join['conditions'];
            }
        }
        if (count($select['_where']) > 0) {
            $sql .= "\nWHERE ";
        }
        $sql .= implode("\n", $select['_where']);
        if (count($select['_group']) > 0) {
            $sql .= "\nGROUP BY ";
            $sql .= implode(', ', $select['_group']);
        }
        if (count($select['_having']) > 0) {
            $sql .= "\nHAVING ";
            $sql .= implode("\n", $select['having']);
        }
        if (count($select['_order']) > 0) {
            $sql .= "\nORDER BY ";
            $sql .= implode(', ', $select['_order']);
        }
        if (is_numeric($select['_limit'])) {
            $sql .= "\n";
            $sql .= $this->limit($select['_limit'], $select['_offset']);
        }
//var_dump($sql);
        return $sql;
    }

    public function escapeStr ($str)
    {
        is_resource($this->_link) or $this->connect();
        return mysql_real_escape_string($str, $this->_link);
    }

    public function listTables ()
    {
        $tables = array();
        if ($query = $this->query('SHOW TABLES FROM ' . $this->escapeTable($this->_config['database']))) {
            foreach ($query->fetchArray() as $row) {
                $tables[] = current($row);
            }
        }
        return $tables;
    }

    public function listFields ($table)
    {
        $result = $this->query('SHOW COLUMNS FROM ' . $this->escapeTable($table));
        return $result->fetchArray();
    }

    public function error ()
    {
        return mysql_error($this->_link);
    }

} // End Database_Mysql_Driver Class

/**
 * MySQL Result
 */
class Mysql_Result extends Database_Result
{
    /**
     * Sets up the result variables.
     *
     * @param  resource  query result
     * @param  resource  database link
     * @param  boolean   return objects or arrays
     * @param  string    SQL query that was run
     */
    public function __construct ($result, $link)
    {
        $this->_result = $result;
        // If the query is a resource, it was a SELECT, SHOW, DESCRIBE, EXPLAIN query
        if (! is_resource($result)) {
            throw new KoException('database.error :error', array(':error' => mysql_error($link)));
        }
    }

    /**
     * Destruct, the cleanup crew!
     */
    public function __destruct ()
    {
        if (is_resource($this->_result)) {
            mysql_free_result($this->_result);
        }
    }

    public function fetchObject ()
    {
        $rows = array();
        if (mysql_num_rows($this->_result)) {
            mysql_data_seek($this->_result, 0);
            while (($row = mysql_fetch_object($this->_result)) !== FALSE) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function fetchArray ()
    {
        $rows = array();
        if (mysql_num_rows($this->_result)) {
            mysql_data_seek($this->_result, 0);
            while (($row = mysql_fetch_array($this->_result, MYSQL_ASSOC)) !== FALSE) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function fetchField ()
    {
        $field_names = array();
        while (($field = mysql_fetch_field($this->_result)) !== FALSE) {
            $field_names[] = $field->name;
        }
        return $field_names;
    }

} // End Mysql_Result Class
