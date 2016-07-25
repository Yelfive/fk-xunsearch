<?php
/**
 * xunsearch Connection class file
 *
 * @author hightman
 * @link http://www.xunsearch.com/
 * @copyright Copyright &copy; 2014 HangZhou YunSheng Network Technology Co., Ltd.
 * @license http://www.xunsearch.com/license/
 * @version $Id$
 */
namespace hightman\xunsearch;

use yii\base\InvalidConfigException;
use yii\base\Object;

/**
 * xunsearch Database is used to wrapper XS class
 *
 * @property-read \XSTokenizerScws $scws
 * @property-read \XSIndex $index
 * @property-read \XSSearch $search
 * @property-read QueryBuilder $queryBuilder
 *
 * @author xjflyttp <xjflyttp@gmail.com>
 * @author hightman <hightman@twomice.net>
 * @since 1.4.9
 */
class Database extends Object
{
    public $iniFile;
    public $charset;
    /**
     * @var array Extra config to add to .ini file when such config does not exist
     */
    public $ini = [];

    public $iniOverwrite = false;

    /**
     * @var \XS
     */
    public $xs;

    /**
     * @var \XSTokenizerScws
     */
    private $_scws;

    /**
     * @var QueryBuilder
     */
    private $_builder;

    /**
     * Initializes, create XS object
     */
    public function init()
    {
        $this->xs = new \XS($this->iniFile);
        if ($this->charset !== null) {
            if ($this->ini) {
                if (!is_array($this->ini)) {
                    throw new InvalidConfigException(__CLASS__ . '::config must be an array');
                }
                $this->xs->setConfigs($this->ini, $this->iniOverwrite);
            }
            $this->xs->setDefaultCharset($this->charset);
        }
    }

    /**
     * @return \XSTokenizerScws get scws tokenizer
     * @throws \XSException
     */
    public function getScws()
    {
        if ($this->_scws === null) {
            $this->_scws = new \XSTokenizerScws;
        }
        return $this->_scws;
    }

    /**
     * @return \XSIndex get xunsearch index object
     */
    public function getIndex()
    {
        return $this->xs->index;
    }

    /**
     * @return \XSSearch get xunsearch search object
     */
    public function getSearch()
    {
        return $this->xs->search;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        if ($this->_builder === null) {
            $this->_builder = new QueryBuilder($this);
        }
        return $this->_builder;
    }

    /**
     * @return \XSDocument
     */
    public function createDoc()
    {
        return new \XSDocument($this->charset);
    }

    /**
     * Quickly add a new document (without checking key conflicts)
     * @param mixed $data \XSDocument object or data array to be added
     */
    public function add($data)
    {
        $this->update($data, true);
    }

    /**
     * @param mixed $data \XSDocument object or data array to be updated
     * @param boolean $add whether to add directly, default to false
     */
    public function update($data, $add = false)
    {
        if ($data instanceof \XSDocument) {
            $this->xs->index->update($data, $add);
        } else {
            $doc = new \XSDocument($data, $this->charset);
            $this->xs->index->update($doc, $add);
        }
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return array('iniFile', 'charset');
    }

    public function __wakeup()
    {
        $this->init();
    }

    /**
     * Forward all methods to \XS, \XSIndex, \XSSearch
     * @param string $name
     * @param array $parameters
     * @return mixed Database or actual return value
     */
    public function __call($name, $parameters)
    {
        // check methods of xs
        if (method_exists($this->xs, $name)) {
            return call_user_func_array(array($this->xs, $name), $parameters);
        }
        // check methods of index object
        if (method_exists('\XSIndex', $name)) {
            $ret = call_user_func_array(array($this->xs->index, $name), $parameters);
            if ($ret === $this->xs->index) {
                return $this;
            }
            return $ret;
        }
        // check methods of search object
        if (method_exists('\XSSearch', $name)) {
            $ret = call_user_func_array(array($this->xs->search, $name), $parameters);
            if ($ret === $this->xs->search) {
                return $this;
            }
            return $ret;
        }
        return parent::__call($name, $parameters);
    }
}
