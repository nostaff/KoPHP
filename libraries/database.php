<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Provides database access in a platform agnostic way, using simple query building blocks.
 *
 * @package    Core
 * @author     Ko Team
 * @version    $Id: database.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Database
{

    /*
     * Database instances
     */
    public static $instances = array();

    protected $_config = array(
        'persistent'    => FALSE ,
        'connection'    => '' ,
        'charset'       => 'utf8' ,
        'table_prefix'  => '' ,
    );

    /**
     * @var Database_Driver
     */
    protected $_driver;

    protected $_select = array();

    protected $_from = array();

    protected $_join = array();

    protected $_where = array();

    protected $_order = array();

    protected $_group = array();

    protected $_having = array();

    protected $_distinct = FALSE;

    protected $_limit = FALSE;

    protected $_offset = FALSE;

    protected $_set = array();

    protected $_lastQuery = '';
    
    /**
     * @var Log
     */
    protected $_logQuery = TRUE;

    /**
     * Returns a singleton instance of Database.
     *
     * @param   mixed   configuration array
     * @return  Database
     */
    public static function &instance ($database = 'default', $config = NULL)
    {
        if (! isset(self::$instances[$database])) {
            self::$instances[$database] = new self($config === NULL ? $database : $config);
        }
        return self::$instances[$database];
    }

    /**
     * Sets up the database configuration, loads the Database_Driver.
     *
     * @throws  KoException
     */
    public function __construct ($config = array())
    {
        if (empty($config)) {
            $config = Ko::config('database.default');
        } elseif (is_string($config)) {
            $name = $config;
            if (($config = Ko::config('database.' . $config)) === NULL)
                throw new KoException('database.undefined_group :group', array(':group' => $name));
        }
        // Merge the default config with the passed config
        $this->_config = array_merge($this->_config, $config);
        $driver = 'Database_' . ucfirst($this->_config['type']);
        if (! Ko::autoload($driver))
            throw new KoException('core.driver_not_found :error', array(':error' => $this->_config['type'] . get_class($this)));
        $this->_driver = new $driver($this->_config);
        if (! ($this->_driver instanceof Database_Driver))
            throw new KoException('core.driver_implements :error', array(':error' => $this->_config['type'] . get_class($this) . 'Database_Driver'));
    }

    /**
     * Runs a query into the driver and returns the result.
     *
     * @param string $sql SQL       Query to execute
     * @param array|string $bind    Data to bind into SELECT placeholders.
     * @return Database_Result
     */
    public function query ($sql = '', $binds = array())
    {
        if ($sql == '')
            return FALSE;
        
        // Compile binds if needed
        if (isset($binds)) {
            $sql = $this->bind($sql, $binds);
        }
        // Set the last query
        $this->_lastQuery = $sql;
        
        // Init logger enginer
        if($this->_logQuery !== FALSE) {
            if( ! $this->_logQuery instanceof Log) {
                $this->_logQuery = Log::instance('db')->attach(new Log_File(DATA_PATH . 'logs/' . date('Ymd') . '/', 'query-' . date('Ymd') . '.php'));
            }
            $this->_logQuery->add('sql', str_replace(array("\r\n", "\r", "\n"), " ", $sql));
        }
        
        // Fetch the result
        return $this->_driver->query($sql);
    }

    /**
     * Selects the column names for a database query.
     *
     * @param   string  string or array of column names to select
     * @return  Database  This Database object.
     */
    public function select ($column = '*')
    {
        if (func_num_args() > 1) {
            $column = func_get_args();
        } elseif (is_string($column)) {
            $column = explode(',', $column);
        } else {
            $column = (array) $column;
        }
        unset($this->_select);
        foreach ($column as $val) {
            if (($val = trim($val)) === '')
                continue;
            if (strpos($val, '(') === FALSE and $val !== '*') {
                if (preg_match('/^DISTINCT\s++(.+)$/i', $val, $matches)) {
                    $this->_distinct = TRUE;
                }
                $val = $this->escapeColumn($val);
            }
            $this->_select[] = $val;
        }
        return $this;
    }

    /**
     * Selects the from table(s) for a database query.
     *
     * @param   string  string or array of tables to select
     * @return  Database  This Database object.
     */
    public function from ($table)
    {
        if (func_num_args() > 1) {
            $table = func_get_args();
        } elseif (is_string($table)) {
            $table = explode(',', $table);
        } else {
            $table = array($table);
        }
        foreach ($table as $val) {
            if (is_string($val)) {
                if (($val = trim($val)) === '')
                    continue;
                if (stripos($val, ' AS ') !== FALSE) {
                    $val = str_ireplace(' as ', ' AS ', $val);
                    list ($table, $alias) = explode(' AS ', $val);
                    $this->_from[] = $this->_config['table_prefix'] . $table . ' AS ' . $this->_config['table_prefix'] . $alias;
                } else {
                    $this->_from[] = $this->_config['table_prefix'] . $val;
                }
            }
        }
        return $this;
    }

    /**
     * Generates the JOIN portion of the query.
     *
     * @param   string        table name
     * @param   string|array  where key or array of key => value pairs
     * @param   string        where value
     * @param   string        type of join
     * @return  Database      This Database object.
     */
    public function join ($table, $key, $value = NULL, $type = '')
    {
        $join = array();
        if (! empty($type)) {
            $type = strtoupper(trim($type));
            if (! in_array($type, array('LEFT' , 'RIGHT' , 'OUTER' , 'INNER' , 'LEFT OUTER' , 'RIGHT OUTER'), TRUE)) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }
        $cond = array();
        $keys = is_array($key) ? $key : array($key => $value);
        foreach ($keys as $key => $value) {
            if (is_string($value)) {
                $value = $this->escapeColumn($this->_config['table_prefix'] . $value);
            }
            $cond[] = $this->_driver->where($key, $value, 'AND ', count($cond), FALSE);
        }
        if (! is_array($this->_join)) {
            $this->_join = array();
        }
        if (! is_array($table)) {
            $table = array($table);
        }
        foreach ($table as $t) {
            if (is_string($t)) {
                if (stripos($t, ' AS ') !== FALSE) {
                    $t = str_ireplace(' AS ', ' AS ', $t);
                    list ($table, $alias) = explode(' AS ', $t);
                    $t = $this->_config['table_prefix'] . $table . ' AS ' . $this->_config['table_prefix'] . $alias;
                } else {
                    $t = $this->_config['table_prefix'] . $t;
                }
            }
            $join['tables'][] = $this->escapeColumn($t);
        }
        $join['conditions'] = '(' . trim(implode(' ', $cond)) . ')';
        $join['type'] = $type;
        $this->_join[] = $join;
        return $this;
    }

    /**
     * Selects the where(s) for a database query.
     *
     * @param   string|array  key name or array of key => value pairs
     * @param   string        value to match with key
     * @param   boolean       disable quoting of WHERE clause
     * @return  Database      This Database object.
     */
    public function where ($key, $value = NULL, $quote = TRUE)
    {
        $quote = (func_num_args() < 2 and ! is_array($key)) ? - 1 : $quote;
        if (is_object($key)) {
            $keys = array((string) $key => '');
        } elseif (! is_array($key)) {
            $keys = array($key => $value);
        } else {
            $keys = $key;
        }
        foreach ($keys as $key => $value) {
            $this->_where[] = $this->_driver->where($key, $value, 'AND ', count($this->_where), $quote);
        }
        return $this;
    }

    /**
     * Selects the OR where(s) for a database query.
     *
     * @param   string|array  key name or array of key => value pairs
     * @param   string        value to match with key
     * @param   boolean       disable quoting of WHERE clause
     * @return  Database        This Database object.
     */
    public function orWhere ($key, $value = NULL, $quote = TRUE)
    {
        $quote = (func_num_args() < 2 and ! is_array($key)) ? - 1 : $quote;
        if (is_object($key)) {
            $keys = array((string) $key => '');
        } elseif (! is_array($key)) {
            $keys = array($key => $value);
        } else {
            $keys = $key;
        }
        foreach ($keys as $key => $value) {
            $this->_where[] = $this->_driver->where($key, $value, 'OR ', count($this->_where), $quote);
        }
        return $this;
    }

    /**
     * Selects the like(s) for a database query.
     *
     * @param   string|array  field name or array of field => match pairs
     * @param   string        like value to match with field
     * @param   boolean       automatically add starting and ending wildcards
     * @return  Database        This Database object.
     */
    public function like ($field, $match = '', $auto = TRUE)
    {
        $fields = is_array($field) ? $field : array($field => $match);
        foreach ($fields as $field => $match) {
            $this->_where[] = $this->_driver->like($field, $match, $auto, 'AND ', count($this->_where));
        }
        return $this;
    }

    /**
     * Selects the or like(s) for a database query.
     *
     * @param   string|array  field name or array of field => match pairs
     * @param   string        like value to match with field
     * @param   boolean       automatically add starting and ending wildcards
     * @return  Database        This Database object.
     */
    public function orLike ($field, $match = '', $auto = TRUE)
    {
        $fields = is_array($field) ? $field : array($field => $match);
        foreach ($fields as $field => $match) {
            $this->_where[] = $this->_driver->like($field, $match, $auto, 'OR ', count($this->_where));
        }
        return $this;
    }

    /**
     * Selects the not like(s) for a database query.
     *
     * @param   string|array  field name or array of field => match pairs
     * @param   string        like value to match with field
     * @param   boolean       automatically add starting and ending wildcards
     * @return  Database        This Database object.
     */
    public function notLike ($field, $match = '', $auto = TRUE)
    {
        $fields = is_array($field) ? $field : array($field => $match);
        foreach ($fields as $field => $match) {
            $this->_where[] = $this->_driver->notLike($field, $match, $auto, 'AND ', count($this->_where));
        }
        return $this;
    }

    /**
     * Selects the or not like(s) for a database query.
     *
     * @param   string|array  field name or array of field => match pairs
     * @param   string        like value to match with field
     * @return  Database        This Database object.
     */
    public function orNotLike ($field, $match = '', $auto = TRUE)
    {
        $fields = is_array($field) ? $field : array($field => $match);
        foreach ($fields as $field => $match) {
            $this->_where[] = $this->_driver->notLike($field, $match, $auto, 'OR ', count($this->_where));
        }
        return $this;
    }

    /**
     * Adds an "IN" condition to the where clause
     *
     * @param   string  Name of the column being examined
     * @param   mixed   An array or string to match against
     * @param   bool    Generate a NOT IN clause instead
     * @return  Database  This Database object.
     */
    
    public function in ($field, $values, $not = FALSE)
    {
        $values = $this->escape($values);
        $where = $this->escapeColumn($field) . ' ' . ($not === TRUE ? 'NOT ' : '') . 'IN (' . $values . ')';
        $this->_where[] = $this->_driver->where($where, '', 'AND ', count($this->_where), - 1);
        return $this;
    }

    /**
     * Adds a "NOT IN" condition to the where clause
     *
     * @param   string  Name of the column being examined
     * @param   mixed   An array or string to match against
     * @return  Database  This Database object.
     */
    public function notIn ($field, $values)
    {
        return $this->in($field, $values, TRUE);
    }
    
    /**
     * Chooses the column to group by in a select query.
     *
     * @param   string  column name to group by
     * @return  Database  This Database object.
     */
    public function group ($by)
    {
        if (! is_array($by)) {
            $by = explode(',', (string) $by);
        }
        foreach ($by as $val) {
            $val = trim($val);
            if ($val != '') {
                $this->_group[] = $this->escapeColumn($val);
            }
        }
        return $this;
    }

    /**
     * Selects the having(s) for a database query.
     *
     * @param   string|array  key name or array of key => value pairs
     * @param   string        value to match with key
     * @param   boolean       disable quoting of WHERE clause
     * @return  Database        This Database object.
     */
    public function having ($key, $value = '', $quote = TRUE)
    {
        $this->_having[] = $this->_driver->where($key, $value, 'AND', count($this->_having), $quote);
        return $this;
    }

    /**
     * Selects the or having(s) for a database query.
     *
     * @param   string|array  key name or array of key => value pairs
     * @param   string        value to match with key
     * @param   boolean       disable quoting of WHERE clause
     * @return  Database        This Database object.
     */
    public function orHaving ($key, $value = '', $quote = TRUE)
    {
        $this->_having[] = $this->_driver->where($key, $value, 'OR', count($this->_having), $quote);
        return $this;
    }

    /**
     * Chooses which column(s) to order the select query by.
     *
     * @param   string|array  column(s) to order on, can be an array, single column, or comma seperated list of columns
     * @param   string        direction of the order
     * @return  Database        This Database object.
     */
    public function order ($order, $direction = NULL)
    {
        if (! is_array($order)) {
            $order = array($order => $direction);
        }
        foreach ($order as $column => $direction) {
            $direction = strtoupper(trim($direction));
            if (! in_array($direction, array('ASC' , 'DESC' , 'RAND()' , 'RANDOM()' , 'NULL'))) {
                $direction = 'ASC';
            }
            $this->_order[] = $this->escapeColumn($column) . ' ' . $direction;
        }
        return $this;
    }

    /**
     * Selects the limit section of a query.
     *
     * @param   integer  number of rows to limit result to
     * @param   integer  offset in result to start returning rows from
     * @return  Database   This Database object.
     */
    public function limit ($limit, $offset = NULL)
    {
        $this->_limit = (int) $limit;
        if ($offset !== NULL or ! is_int($this->_offset)) {
            $this->offset($offset);
        }
        return $this;
    }

    /**
     * Sets the offset portion of a query.
     *
     * @param   integer  offset value
     * @return  Database   This Database object.
     */
    public function offset ($value)
    {
        $this->_offset = (int) $value;
        return $this;
    }

    /**
     * Builds an array of query results.
     *
     * @return  array
     */
    public function fetchArray ($sql = '', $binds = array())
    {
        if ($sql == '') {
            $sql = $this->compile();
        }
        $result = $this->query($sql, $binds);
        if($result instanceof Database_Result){
            return $result->fetchArray();
        } else {
            return $result;
        }
    }

    /**
     * Builds an Object of query results.
     *
     * @return  Object
     */
    public function fetchObject ($sql = '', $binds = array())
    {
        if ($sql == '') {
            $sql = $this->compile();
        }
        $result = $this->query($sql, $binds);
        if($result instanceof Database_Result){
            return $result->fetchObject();
        } else {
            return $result;
        }
    }

    /**
     * Builds an array of one row query result.
     *
     * @return  array
     */
    public function fetchRow ($sql = '', $binds = array())
    {
    	if ($sql == '') {
    		$sql = $this->limit(1)->compile();
    	}
    	$result = $this->query($sql, $binds);
    	if($result instanceof Database_Result){
    		$result = $result->fetchObject();
    		return $result ? current($result) : false;
    	} else {
    		return $result;
    	}
    }

    /**
     * Compiles the select statement based on the other functions called and runs the query.
     *
     * @param   string  table name
     * @param   string  limit clause
     * @param   string  offset clause
     * @return  Database_Result
     */
    public function execute ($table = '', $limit = NULL, $offset = NULL)
    {
        return $this->query($this->compile($table, $limit, $offset));
    }

    /**
     * Compiles the select statement based on the other functions called and returns the query string.
     *
     * @param   string  table name
     * @param   string  limit clause
     * @param   string  offset clause
     * @return  string  sql string
     */
    public function compile ($table = '', $limit = NULL, $offset = NULL)
    {
        if ($table != '') {
            $this->from($table);
        }
        if (! is_null($limit)) {
            $this->limit($limit, $offset);
        }
        $sql = $this->_driver->select(get_object_vars($this));
        $this->reset();
        return $sql;
    }

    /**
     * Compiles an insert string and runs the query.
     *
     * @param   string  table name
     * @param   array   array of key/value pairs to insert
     * @return  Database_Result  Query result
     */
    public function insert ($table = '', $set = NULL)
    {
        if (! is_null($set)) {
            $this->setValue($set);
        }
        if ($this->_set == NULL)
            throw new KoException('database.must_use_set');
        if ($table == '') {
            if (! isset($this->_from[0]))
                throw new KoException('database.must_use_table');
            $table = $this->_from[0];
        }
        $sql = $this->_driver->insert($this->_config['table_prefix'] . $table, array_keys($this->_set), array_values($this->_set));
        $this->reset();
        return $this->query($sql);
    }

    /**
     * Returns the insert id from the result.
     *
     * @return  int
     */
    public function lastInsertId ()
    {
        return $this->_driver->lastInsertId();
    }

    /**
     * Compiles a replace string and runs the query.
     *
     * @param   string  table name
     * @param   array   array of key/value pairs to replace
     * @return  Database_Result  Query result
     */
    public function replace ($table, $set = NULL)
    {
        if (! is_null($set)) {
            $this->setValue($set);
        }
        if ($this->_set == NULL)
            throw new KoException('database.must_use_set');
        if ($table == '') {
            if (! isset($this->_from[0]))
                throw new KoException('database.must_use_table');
            $table = $this->_from[0];
        }
        $sql = $this->_driver->replace($this->_config['table_prefix'] . $table, array_keys($this->_set), array_values($this->_set));
        $this->reset();
        return $this->query($sql);
    }

    /**
     * Compiles an update string and runs the query.
     *
     * @param   string  table name
     * @param   array   associative array of update values
     * @param   array   where clause
     * @return  Database_Result  Query result
     */
    public function update ($table = '', $set = NULL, $where = NULL)
    {
        if (is_array($set)) {
            $this->setValue($set);
        }
        if (! is_null($where)) {
            $this->where ($where);
        }
        if ($this->_set == FALSE)
            throw new KoException('database.must_use_set');
        if ($table == '') {
            if (! isset($this->_from[0]))
                throw new KoException('database.must_use_table');
            $table = $this->_from[0];
        }
        
       $sql = $this->_driver->update($this->_config['table_prefix'] . $table, $this->_set, $this->_where );
        
        $this->reset();
        return $this->query($sql);
    }
    

    /**
     * Compiles a delete string and runs the query.
     *
     * @param   string  table name
     * @param   array   where clause
     * @return  Database_Result  Query result
     */
    public function delete ($table = '', $where = NULL)
    {
        if ($table == '') {
            if (! isset($this->_from[0]))
                throw new KoException('database.must_use_table');
            $table = $this->_from[0];
        } else {
            $table = $this->_config['table_prefix'] . $table;
        }
        if (! is_null($where)) {
            $this->where ($where);
        }
        if (count($this->_where) < 1)
            throw new KoException('database.must_use_where');
        $sql = $this->_driver->delete($table, $this->_where);
        $this->reset();
        return $this->query($sql);
    }

    /**
     * Returns the last query run.
     *
     * @return  string SQL
     */
    public function getLastQuery ()
    {
        return $this->_lastQuery;
    }

    /**
     * Count query records of curent query.
     *
     * @param   string   table name
     * @param   array    where clause
     * @return  integer
     */
    public function count ($table = FALSE, $where = NULL)
    {
        if (count($this->_from) < 1) {
            if ($table == FALSE)
                throw new KoException('database.must_use_table');
            $this->from($table);
        }
        if ($where !== NULL) {
            $this->where ($where);
        }
        return (int) $this->select('COUNT(*) AS ' . $this->escapeColumn('count'))->fetchRow()->count;
    }

    /**
     * Lists all the tables in the current database.
     *
     * @return  array
     */
    public function listTables ()
    {
        return $this->_driver->listTables();
    }

    /**
     * Get the field data for a database table, along with the field's attributes.
     *
     * @param   string  table name
     * @return  array
     */
    public function listFields ($table = '')
    {
        return $this->_driver->listFields($this->_config['table_prefix'] . $table);
    }

    /**
     * Combine a SQL statement with the bind values. Used for safe queries.
     *
     * For example:
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->bind ($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string $text  query to bind to the values
     * @param array $value  array of values to bind to the query
     * @param bool $single  whether this is a single bind.
     * @return string
     */
    public function bind ($text, $value, $single = FALSE)
    {
        if ($single == TRUE) {
            return str_replace('?', $this->escape($value), $text);
        } else {
            foreach ((array) $value as $val) {
                if (strpos($text, '?') != FALSE) {
                    $text = substr_replace($text, $this->escape($val), strpos($text, '?'), 1);
                }
            }
            return $text;
        }
    }
    
    /**
     * Escapes a value for a query.
     *
     * @param   mixed   value to escape
     * @return  string
     */
    public function escape ($value)
    {
        return $this->_driver->escape($value);
    }

    /**
     * Escapes a string for a query.
     *
     * @param   string  string to escape
     * @return  string
     */
    public function escapeStr ($str)
    {
        return $this->_driver->escapeStr($str);
    }

    /**
     * Escapes a table name for a query.
     *
     * @param   string  string to escape
     * @return  string
     */
    public function escapeTable ($table)
    {
        return $this->_driver->escapeTable($table);
    }

    /**
     * Escapes a column name for a query.
     *
     * @param   string  string to escape
     * @return  string
     */
    public function escapeColumn ($column)
    {
        return $this->_driver->escapeColumn($column);
    }

    /**
     * Returns table prefix of current configuration.
     *
     * @return  string
     */
    public function tablePrefix ()
    {
        return $this->_config['table_prefix'];
    }

    /**
     * Allows key/value pairs to be set for inserting or updating.
     *
     * @param   string|array  key name or array of key => value pairs
     * @param   string        value to match with key
     * @return  Database        This Database object.
     */
    protected function setValue ($key, $value = '')
    {
        if (! is_array($key)) {
            $key = array($key => $value);
        }
        foreach ($key as $k => $v) {
            $this->_set[$k] = $this->escape($v);
        }
        return $this;
    }

    /**
     * Resets all private select and insert variables.
     *
     * @return  void
     */
    protected function reset ()
    {
        $this->_select = array();
        $this->_from = array();
        $this->_join = array();
        $this->_where = array();
        $this->_order = array();
        $this->_group = array();
        $this->_having = array();
        $this->_set = array();
        
        $this->_distinct = FALSE;
        
        $this->_limit = FALSE;
        $this->_offset = FALSE;
    }
} // End Database Class
