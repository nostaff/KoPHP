<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Model base class.
 *
 * @package    Model
 * @author     Ko Team, Eric
 * @version    $Id: model.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
abstract class Model
{

    /**
     * Database instance
     *
     * @var Database
     */
    protected $db = 'default';

    /**
     * The default table name
     */
    protected $table = '';
    
    // Cache KEY
    protected $cacheConfig = 'default';
    
    /**
     * Loads the database.
     *
     * @param   mixed  Database instance object or string
     * @return  void
     */
    public function __construct ($db = NULL)
    {
        if ($db !== NULL) {
            // Set the database instance name
            $this->db = $db;
        }
        if (is_string($this->db)) {
            $this->db = Database::instance($this->db);
        }
    }

    /**
     * Create a new model instance.
     *
     * @param   string   model name
     * @param   mixed    Database instance object or string
     * @return  Model
     */
    public static function factory ($name, $db = NULL)
    {
        return new $name($db);
    }

    /**
     * Count records by where cond.
     *
     * @param array|string $where
     * @return int
     */
    public function count ($where = array())
    {
        if(empty($where)) {
            return $this->db->count($this->table);
        } else {
            return $this->db->where($where)->count($this->table);
        }
    }

    /**
     * Return select object
     *
     * @return Database Object of db.
     */
    public function getSelectObj ($column = '*')
    {
        return $this->db->select($column)->from($this->table);
    }

    /**
     * Insert a row.
     *
     * @param array $set
     * @return int
     */
    public function insert ($set = array())
    {
        return $this->db->insert($this->table, $set);
    }

    /**
     * Replace a row use primary key
     *
     * @param array $set
     * @return int
     */
    public function replace ($set = array())
    {
        return $this->db->replace($this->table, $set);
    }

    /**
     * Update existing rows.
     *
     * @param array $set
     * @return int  The number affected rows.
     */
    public function update ($set = array(), $where = NULL)
    {
        return $this->db->update($this->table, $set, $where);
    }

    /**
     * Deletes existing rows.
     *
     * @param  array|string $where SQL WHERE clause(s).
     * @return int          The number of rows deleted.
     */
    public function delete ($where = array())
    {
        return $this->db->delete($this->table, $where);
    }

    /**
     * Gets the Database Adapter for this particular model object.
     *
     * @return Database
     */
    public function getAdapter ()
    {
        return $this->db;
    }
    
    /**
     * Return the table name.
     *
     * @return string
     */
    public function getTableName($table='')
    {
        return $table ? $this->db->tablePrefix() . $table : $this->db->tablePrefix() . $this->table;
    }
    
    /**
     * Return the last query.
     *
     * @return string
     */
    public function getLastQuery()
    {
        return $this->db->getLastQuery();
    }
    

    /**
     * get Data from Cache, support multi get
     *
     * @param string | array $key
     * @return mix
     */
    protected function getCache($key)
    {
        return Cache::instance($this->cacheConfig)->get($this->makeCacheKey($key));
    }
    
    /**
     * set data to cache
     *
     * @param string $key  Cache KEY
     * @param mix $data Cache Data
     * @param intger $lifetime
     */
    protected function setCache($key, $data, $lifetime = NULL)
    {
        return Cache::instance($this->cacheConfig)->set($this->makeCacheKey($key), $data, $lifetime);
    }
    
    /**
     * Delete Cache
     *
     * @param string $key
     * @return bool
     */
    protected function deleteCache($key)
    {
        return Cache::instance($this->cacheConfig)->delete($this->makeCacheKey($key));
    }
    
    /**
     * 原子递增
     * @param $key
     * @param $value
     * @return bool
     */
    
    protected function incrementCache ($key, $value = 1)
    {
        return Cache::instance($this->cacheConfig)->increment($this->makeCacheKey($key), $value);
    }
    
	/**
     * 原子递减操作
     *
     * @param int $key		递减字段
     * @param int $value	递减度
     * @return bool
     */
    
    public function decrementCache ($key, $value = -1)
    {
        return Cache::instance($this->cacheConfig)->decrement($this->makeCacheKey($key), $value);
    }
    
    /**
     * update Cache
     *
     * @param string $key  Cache KEY
     * @param mix $data Cache Data
     * @param intger $lifetime
     */
    protected function updateCache($key, $data, $lifetime = NULL)
    {
        return $this->setCache($key, $data, $lifetime);
    }
        
    /**
     * make  cache keֵ
     *
     * @param string $key
     * @return string
     */
    protected function makeCacheKey($key)
    {
        if (is_array($key)) {
            $cacheKey = array();
            foreach ($key as $k) {
            	$cacheKey[] = md5(sprintf('%s_%s', $this->getTableName(), (string) $k));
            }
            return $cacheKey;
        }

        return md5(sprintf('%s_%s', $this->getTableName(), (string) $key));
    }
  
} // End Model
