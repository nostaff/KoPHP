<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Database driver
 *
 * @package    Core
 * @author     Ko Team
 * @version    $Id: driver.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
abstract class Database_Driver
{
    protected $_insertid;

    /**
     * Connect to our database.
     * Returns FALSE on failure or a MySQL resource.
     *
     * @return mixed
     */
    abstract public function connect ();

    /**
     * Perform a query based on a manually written query.
     *
     * @param  string  SQL query to execute
     * @return Database_Result
     */
    abstract public function query ($sql);

    /**
     * Returns the insert id from the result.
     *
     * @return  mixed
     */
    public function lastInsertId ()
    {
        return $this->_insertid;
    }
        
    /**
     *  Compiles the SELECT statement.
     *  Generates a query string based on which functions were used.
     *  Should not be called directly, the get() function calls it.
     *
     * @param   array   select query values
     * @return  string
     */
    abstract public function select ($select);
    
    /**
     * Builds a DELETE query.
     *
     * @param   string  table name
     * @param   array   where clause
     * @return  string
     */
    public function delete ($table, $where)
    {
        return 'DELETE FROM ' . $this->escapeTable($table) . ' WHERE ' . implode(' ', $where);
    }

    /**
     * Builds an UPDATE query.
     *
     * @param   string  table name
     * @param   array   key => value pairs
     * @param   array   where clause
     * @return  string
     */
    public function update ($table, $values, $where)
    {
        foreach ($values as $key => $val) {
            $valstr[] = $this->escapeColumn($key) . ' = ' . $val;
	    }

	    return 'UPDATE ' . $this->escapeTable($table) . ' SET ' . implode(', ', $valstr) . ' WHERE ' . implode(' ', $where);            
     }

    /**
     * Set the charset using 'SET NAMES <charset>'.
     *
     * @param  string  character set to use
     */
    abstract public function charset ($charset);

    /**
     * Wrap the tablename in backticks, has support for: table.field syntax.
     *
     * @param   string  table name
     * @return  string
     */
    abstract public function escapeTable ($table);

    /**
     * Escape a column/field name, has support for special commands.
     *
     * @param   string  column name
     * @return  string
     */
    abstract public function escapeColumn ($column);

    /**
     * Builds a WHERE portion of a query.
     *
     * @param   mixed    key
     * @param   string   value
     * @param   string   type
     * @param   int      number of where clauses
     * @param   boolean  escape the value
     * @return  string
     */
    public function where ($key, $value, $type, $num_wheres, $quote)
    {
        $prefix = ($num_wheres == 0) ? '' : $type;
        if ($quote === - 1) {
            $value = '';
        } else {
            if ($value === NULL) {
                if (! $this->hasOperator($key)) {
                    $key .= ' IS';
                }
                $value = ' NULL';
            } elseif (is_bool($value)) {
                if (! $this->hasOperator($key)) {
                    $key .= ' =';
                }
                $value = ($value == TRUE) ? ' 1' : ' 0';
            } elseif (is_array($value)) {
                $escaped_values = array();
                foreach ($value as $v) {
                    if (is_numeric($v)) {
                        $escaped_values[] = $v;
                    } else {
                        $escaped_values[] = "'" . $this->escapeStr($v) . "'";
                    }
                }
                $value = ' IN (' . implode(",", $escaped_values) . ')';
            } else {
                if (! $this->hasOperator($key) and ! empty($key)) {
                    $key = $this->escapeColumn($key) . ' =';
                } else {
                    preg_match('/^(.+?)([<>!=]+|\bIS(?:\s+NULL))\s*$/i', $key, $matches);
                    if (isset($matches[1]) and isset($matches[2])) {
                        $key = $this->escapeColumn(trim($matches[1])) . ' ' . trim($matches[2]);
                    }
                }
                $value = ' ' . (($quote == TRUE) ? $this->escape($value) : $value);
            }
        }
        return $prefix . $key . $value;
    }

    /**
     * Builds a LIKE portion of a query.
     *
     * @param   mixed    field name
     * @param   string   value to match with field
     * @param   boolean  add wildcards before and after the match
     * @param   string   clause type (AND or OR)
     * @param   int      number of likes
     * @return  string
     */
    public function like ($field, $match, $auto, $type, $num_likes)
    {
        $prefix = ($num_likes == 0) ? '' : $type;
        $match = $this->escapeStr($match);
        if ($auto === TRUE) {
            // Add the start and end quotes
            $match = '%' . str_replace('%', '\\%', $match) . '%';
        }
        return $prefix . ' ' . $this->escapeColumn($field) . ' LIKE \'' . $match . '\'';
    }

    /**
     * Builds a NOT LIKE portion of a query.
     *
     * @param   mixed   field name
     * @param   string  value to match with field
     * @param   string  clause type (AND or OR)
     * @param   int     number of likes
     * @return  string
     */
    public function notLike ($field, $match, $auto, $type, $num_likes)
    {
        $prefix = ($num_likes == 0) ? '' : $type;
        $match = $this->escapeStr($match);
        if ($auto === TRUE) {
            // Add the start and end quotes
            $match = '%' . $match . '%';
        }
        return $prefix . ' ' . $this->escapeColumn($field) . ' NOT LIKE \'' . $match . '\'';
    }

    /**
     * Builds an INSERT query.
     *
     * @param   string  table name
     * @param   array   keys
     * @param   array   values
     * @return  string
     */
    public function insert ($table, $keys, $values)
    {
        // Escape the column names
        foreach ($keys as $key => $value) {
            $keys[$key] = $this->escapeColumn($value);
        }
        return 'INSERT INTO ' . $this->escapeTable($table) . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ')';
    }

    /**
     * Builds an INSERT query.
     *
     * @param   string  table name
     * @param   array   keys
     * @param   array   values
     * @return  string
     */
    public function batchInsert ($table, $keys, $values)
    {
        // Escape the column names
        foreach ($keys as $key => $value) {
            $keys[$key] = $this->escapeColumn($value);
        }
        $insert = array();
        foreach ($values as $value) {
            $insert = '(' . implode(', ', $value) . ')';
        }
        return 'INSERT INTO ' . $this->escapeTable($table) . ' (' . implode(', ', $keys) . ') VALUES ' . implode(', ', $insert);
    }

    /**
     * Builds a replace portion of a query.
     *
     * @param   string  table name
     * @param   array   keys
     * @param   array   values
     * @return  string
     */
    abstract public function replace ($table, $keys, $values);

    /**
     * Builds a LIMIT portion of a query.
     *
     * @param   integer  limit
     * @param   integer  offset
     * @return  string
     */
    abstract public function limit ($limit, $offset = 0);

    /**
     * Determines if the string has an arithmetic operator in it.
     *
     * @param   string   string to check
     * @return  boolean
     */
    public function hasOperator ($str)
    {
        return (bool) preg_match('/[<>!=]|\sIS(?:\s+NOT\s+)?\b|BETWEEN/i', trim($str));
    }

    /**
     * Escapes any input value.
     *
     * @param   mixed   value to escape
     * @return  string
     */
    public function escape ($value)
    {
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->escape($val);
            }
            return implode(', ', $value);
        } elseif (is_bool($value)) {
            return (int) $value;
        } elseif (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

    /**
     * Escapes a string for a query.
     *
     * @param   mixed   value to escape
     * @return  string
     */
    abstract public function escapeStr ($str);

    /**
     * Lists all tables in the database.
     *
     * @return  array
     */
    abstract public function listTables ();

    /**
     * Lists all fields in a table.
     *
     * @param   string  table name
     * @return  array
     */
    abstract function listFields ($table);

    /**
     * Returns the last database error.
     *
     * @return  string
     */
    abstract public function error ();

} // End Database Driver Interface

/**
 * Database_Result
 *
 */
abstract class Database_Result
{

    // Result resource, insert id, and SQL
    protected $_result;

    /**
     * Builds an Object of query results.
     *
     * @return  Object
     */
    abstract public function fetchObject ();

    /**
     * Builds an array of query results.
     *
     * @return  array
     */
    abstract public function fetchArray ();

    /**
     * Builds an Field of query results.
     *
     * @return  array
     */
    abstract public function fetchField ();

} // End Database Result Interface
