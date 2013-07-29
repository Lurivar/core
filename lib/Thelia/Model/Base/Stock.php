<?php

namespace Thelia\Model\Base;

use \DateTime;
use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;
use Thelia\Model\AttributeCombination as ChildAttributeCombination;
use Thelia\Model\AttributeCombinationQuery as ChildAttributeCombinationQuery;
use Thelia\Model\CartItem as ChildCartItem;
use Thelia\Model\CartItemQuery as ChildCartItemQuery;
use Thelia\Model\Product as ChildProduct;
use Thelia\Model\ProductPrice as ChildProductPrice;
use Thelia\Model\ProductPriceQuery as ChildProductPriceQuery;
use Thelia\Model\ProductQuery as ChildProductQuery;
use Thelia\Model\Stock as ChildStock;
use Thelia\Model\StockQuery as ChildStockQuery;
use Thelia\Model\Map\StockTableMap;

abstract class Stock implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Thelia\\Model\\Map\\StockTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the product_id field.
     * @var        int
     */
    protected $product_id;

    /**
     * The value for the increase field.
     * @var        double
     */
    protected $increase;

    /**
     * The value for the quantity field.
     * @var        double
     */
    protected $quantity;

    /**
     * The value for the promo field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $promo;

    /**
     * The value for the newness field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $newness;

    /**
     * The value for the weight field.
     * @var        double
     */
    protected $weight;

    /**
     * The value for the created_at field.
     * @var        string
     */
    protected $created_at;

    /**
     * The value for the updated_at field.
     * @var        string
     */
    protected $updated_at;

    /**
     * @var        Product
     */
    protected $aProduct;

    /**
     * @var        ObjectCollection|ChildAttributeCombination[] Collection to store aggregation of ChildAttributeCombination objects.
     */
    protected $collAttributeCombinations;
    protected $collAttributeCombinationsPartial;

    /**
     * @var        ObjectCollection|ChildCartItem[] Collection to store aggregation of ChildCartItem objects.
     */
    protected $collCartItems;
    protected $collCartItemsPartial;

    /**
     * @var        ObjectCollection|ChildProductPrice[] Collection to store aggregation of ChildProductPrice objects.
     */
    protected $collProductPrices;
    protected $collProductPricesPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $attributeCombinationsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $cartItemsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $productPricesScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->promo = 0;
        $this->newness = 0;
    }

    /**
     * Initializes internal state of Thelia\Model\Base\Stock object.
     * @see applyDefaults()
     */
    public function __construct()
    {
        $this->applyDefaultValues();
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !empty($this->modifiedColumns);
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return in_array($col, $this->modifiedColumns);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return array_unique($this->modifiedColumns);
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (Boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (Boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            while (false !== ($offset = array_search($col, $this->modifiedColumns))) {
                array_splice($this->modifiedColumns, $offset, 1);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>Stock</code> instance.  If
     * <code>obj</code> is an instance of <code>Stock</code>, delegates to
     * <code>equals(Stock)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param      obj The object to compare to.
     * @return Whether equal to the object specified.
     */
    public function equals($obj)
    {
        $thisclazz = get_class($this);
        if (!is_object($obj) || !($obj instanceof $thisclazz)) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey()
            || null === $obj->getPrimaryKey())  {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        if (null !== $this->getPrimaryKey()) {
            return crc32(serialize($this->getPrimaryKey()));
        }

        return crc32(serialize(clone $this));
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @param string $name The virtual column name
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return isset($this->virtualColumns[$name]);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @return mixed
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return Stock The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return Stock The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $this->fromArray($parser->toArray($data), TableMap::TYPE_PHPNAME);
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [id] column value.
     *
     * @return   int
     */
    public function getId()
    {

        return $this->id;
    }

    /**
     * Get the [product_id] column value.
     *
     * @return   int
     */
    public function getProductId()
    {

        return $this->product_id;
    }

    /**
     * Get the [increase] column value.
     *
     * @return   double
     */
    public function getIncrease()
    {

        return $this->increase;
    }

    /**
     * Get the [quantity] column value.
     *
     * @return   double
     */
    public function getQuantity()
    {

        return $this->quantity;
    }

    /**
     * Get the [promo] column value.
     *
     * @return   int
     */
    public function getPromo()
    {

        return $this->promo;
    }

    /**
     * Get the [newness] column value.
     *
     * @return   int
     */
    public function getNewness()
    {

        return $this->newness;
    }

    /**
     * Get the [weight] column value.
     *
     * @return   double
     */
    public function getWeight()
    {

        return $this->weight;
    }

    /**
     * Get the [optionally formatted] temporal [created_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->created_at;
        } else {
            return $this->created_at !== null ? $this->created_at->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [updated_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getUpdatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->updated_at;
        } else {
            return $this->updated_at !== null ? $this->updated_at->format($format) : null;
        }
    }

    /**
     * Set the value of [id] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[] = StockTableMap::ID;
        }


        return $this;
    } // setId()

    /**
     * Set the value of [product_id] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setProductId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->product_id !== $v) {
            $this->product_id = $v;
            $this->modifiedColumns[] = StockTableMap::PRODUCT_ID;
        }

        if ($this->aProduct !== null && $this->aProduct->getId() !== $v) {
            $this->aProduct = null;
        }


        return $this;
    } // setProductId()

    /**
     * Set the value of [increase] column.
     *
     * @param      double $v new value
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setIncrease($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->increase !== $v) {
            $this->increase = $v;
            $this->modifiedColumns[] = StockTableMap::INCREASE;
        }


        return $this;
    } // setIncrease()

    /**
     * Set the value of [quantity] column.
     *
     * @param      double $v new value
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setQuantity($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->quantity !== $v) {
            $this->quantity = $v;
            $this->modifiedColumns[] = StockTableMap::QUANTITY;
        }


        return $this;
    } // setQuantity()

    /**
     * Set the value of [promo] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setPromo($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->promo !== $v) {
            $this->promo = $v;
            $this->modifiedColumns[] = StockTableMap::PROMO;
        }


        return $this;
    } // setPromo()

    /**
     * Set the value of [newness] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setNewness($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->newness !== $v) {
            $this->newness = $v;
            $this->modifiedColumns[] = StockTableMap::NEWNESS;
        }


        return $this;
    } // setNewness()

    /**
     * Set the value of [weight] column.
     *
     * @param      double $v new value
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setWeight($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->weight !== $v) {
            $this->weight = $v;
            $this->modifiedColumns[] = StockTableMap::WEIGHT;
        }


        return $this;
    } // setWeight()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->created_at !== null || $dt !== null) {
            if ($dt !== $this->created_at) {
                $this->created_at = $dt;
                $this->modifiedColumns[] = StockTableMap::CREATED_AT;
            }
        } // if either are not null


        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [updated_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->updated_at !== null || $dt !== null) {
            if ($dt !== $this->updated_at) {
                $this->updated_at = $dt;
                $this->modifiedColumns[] = StockTableMap::UPDATED_AT;
            }
        } // if either are not null


        return $this;
    } // setUpdatedAt()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
            if ($this->promo !== 0) {
                return false;
            }

            if ($this->newness !== 0) {
                return false;
            }

        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : StockTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : StockTableMap::translateFieldName('ProductId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->product_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : StockTableMap::translateFieldName('Increase', TableMap::TYPE_PHPNAME, $indexType)];
            $this->increase = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : StockTableMap::translateFieldName('Quantity', TableMap::TYPE_PHPNAME, $indexType)];
            $this->quantity = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : StockTableMap::translateFieldName('Promo', TableMap::TYPE_PHPNAME, $indexType)];
            $this->promo = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : StockTableMap::translateFieldName('Newness', TableMap::TYPE_PHPNAME, $indexType)];
            $this->newness = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : StockTableMap::translateFieldName('Weight', TableMap::TYPE_PHPNAME, $indexType)];
            $this->weight = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : StockTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : StockTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 9; // 9 = StockTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \Thelia\Model\Stock object", 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
        if ($this->aProduct !== null && $this->product_id !== $this->aProduct->getId()) {
            $this->aProduct = null;
        }
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(StockTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildStockQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aProduct = null;
            $this->collAttributeCombinations = null;

            $this->collCartItems = null;

            $this->collProductPrices = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Stock::setDeleted()
     * @see Stock::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(StockTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildStockQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(StockTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior
                if (!$this->isColumnModified(StockTableMap::CREATED_AT)) {
                    $this->setCreatedAt(time());
                }
                if (!$this->isColumnModified(StockTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(StockTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                StockTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aProduct !== null) {
                if ($this->aProduct->isModified() || $this->aProduct->isNew()) {
                    $affectedRows += $this->aProduct->save($con);
                }
                $this->setProduct($this->aProduct);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->attributeCombinationsScheduledForDeletion !== null) {
                if (!$this->attributeCombinationsScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\AttributeCombinationQuery::create()
                        ->filterByPrimaryKeys($this->attributeCombinationsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->attributeCombinationsScheduledForDeletion = null;
                }
            }

                if ($this->collAttributeCombinations !== null) {
            foreach ($this->collAttributeCombinations as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->cartItemsScheduledForDeletion !== null) {
                if (!$this->cartItemsScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\CartItemQuery::create()
                        ->filterByPrimaryKeys($this->cartItemsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->cartItemsScheduledForDeletion = null;
                }
            }

                if ($this->collCartItems !== null) {
            foreach ($this->collCartItems as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->productPricesScheduledForDeletion !== null) {
                if (!$this->productPricesScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\ProductPriceQuery::create()
                        ->filterByPrimaryKeys($this->productPricesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->productPricesScheduledForDeletion = null;
                }
            }

                if ($this->collProductPrices !== null) {
            foreach ($this->collProductPrices as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[] = StockTableMap::ID;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . StockTableMap::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(StockTableMap::ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(StockTableMap::PRODUCT_ID)) {
            $modifiedColumns[':p' . $index++]  = 'PRODUCT_ID';
        }
        if ($this->isColumnModified(StockTableMap::INCREASE)) {
            $modifiedColumns[':p' . $index++]  = 'INCREASE';
        }
        if ($this->isColumnModified(StockTableMap::QUANTITY)) {
            $modifiedColumns[':p' . $index++]  = 'QUANTITY';
        }
        if ($this->isColumnModified(StockTableMap::PROMO)) {
            $modifiedColumns[':p' . $index++]  = 'PROMO';
        }
        if ($this->isColumnModified(StockTableMap::NEWNESS)) {
            $modifiedColumns[':p' . $index++]  = 'NEWNESS';
        }
        if ($this->isColumnModified(StockTableMap::WEIGHT)) {
            $modifiedColumns[':p' . $index++]  = 'WEIGHT';
        }
        if ($this->isColumnModified(StockTableMap::CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED_AT';
        }
        if ($this->isColumnModified(StockTableMap::UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'UPDATED_AT';
        }

        $sql = sprintf(
            'INSERT INTO stock (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'PRODUCT_ID':
                        $stmt->bindValue($identifier, $this->product_id, PDO::PARAM_INT);
                        break;
                    case 'INCREASE':
                        $stmt->bindValue($identifier, $this->increase, PDO::PARAM_STR);
                        break;
                    case 'QUANTITY':
                        $stmt->bindValue($identifier, $this->quantity, PDO::PARAM_STR);
                        break;
                    case 'PROMO':
                        $stmt->bindValue($identifier, $this->promo, PDO::PARAM_INT);
                        break;
                    case 'NEWNESS':
                        $stmt->bindValue($identifier, $this->newness, PDO::PARAM_INT);
                        break;
                    case 'WEIGHT':
                        $stmt->bindValue($identifier, $this->weight, PDO::PARAM_STR);
                        break;
                    case 'CREATED_AT':
                        $stmt->bindValue($identifier, $this->created_at ? $this->created_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'UPDATED_AT':
                        $stmt->bindValue($identifier, $this->updated_at ? $this->updated_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = StockTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getProductId();
                break;
            case 2:
                return $this->getIncrease();
                break;
            case 3:
                return $this->getQuantity();
                break;
            case 4:
                return $this->getPromo();
                break;
            case 5:
                return $this->getNewness();
                break;
            case 6:
                return $this->getWeight();
                break;
            case 7:
                return $this->getCreatedAt();
                break;
            case 8:
                return $this->getUpdatedAt();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['Stock'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Stock'][$this->getPrimaryKey()] = true;
        $keys = StockTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getProductId(),
            $keys[2] => $this->getIncrease(),
            $keys[3] => $this->getQuantity(),
            $keys[4] => $this->getPromo(),
            $keys[5] => $this->getNewness(),
            $keys[6] => $this->getWeight(),
            $keys[7] => $this->getCreatedAt(),
            $keys[8] => $this->getUpdatedAt(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach($virtualColumns as $key => $virtualColumn)
        {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aProduct) {
                $result['Product'] = $this->aProduct->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collAttributeCombinations) {
                $result['AttributeCombinations'] = $this->collAttributeCombinations->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collCartItems) {
                $result['CartItems'] = $this->collCartItems->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collProductPrices) {
                $result['ProductPrices'] = $this->collProductPrices->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string $name
     * @param      mixed  $value field value
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return void
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = StockTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @param      mixed $value field value
     * @return void
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setProductId($value);
                break;
            case 2:
                $this->setIncrease($value);
                break;
            case 3:
                $this->setQuantity($value);
                break;
            case 4:
                $this->setPromo($value);
                break;
            case 5:
                $this->setNewness($value);
                break;
            case 6:
                $this->setWeight($value);
                break;
            case 7:
                $this->setCreatedAt($value);
                break;
            case 8:
                $this->setUpdatedAt($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = StockTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setProductId($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setIncrease($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setQuantity($arr[$keys[3]]);
        if (array_key_exists($keys[4], $arr)) $this->setPromo($arr[$keys[4]]);
        if (array_key_exists($keys[5], $arr)) $this->setNewness($arr[$keys[5]]);
        if (array_key_exists($keys[6], $arr)) $this->setWeight($arr[$keys[6]]);
        if (array_key_exists($keys[7], $arr)) $this->setCreatedAt($arr[$keys[7]]);
        if (array_key_exists($keys[8], $arr)) $this->setUpdatedAt($arr[$keys[8]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(StockTableMap::DATABASE_NAME);

        if ($this->isColumnModified(StockTableMap::ID)) $criteria->add(StockTableMap::ID, $this->id);
        if ($this->isColumnModified(StockTableMap::PRODUCT_ID)) $criteria->add(StockTableMap::PRODUCT_ID, $this->product_id);
        if ($this->isColumnModified(StockTableMap::INCREASE)) $criteria->add(StockTableMap::INCREASE, $this->increase);
        if ($this->isColumnModified(StockTableMap::QUANTITY)) $criteria->add(StockTableMap::QUANTITY, $this->quantity);
        if ($this->isColumnModified(StockTableMap::PROMO)) $criteria->add(StockTableMap::PROMO, $this->promo);
        if ($this->isColumnModified(StockTableMap::NEWNESS)) $criteria->add(StockTableMap::NEWNESS, $this->newness);
        if ($this->isColumnModified(StockTableMap::WEIGHT)) $criteria->add(StockTableMap::WEIGHT, $this->weight);
        if ($this->isColumnModified(StockTableMap::CREATED_AT)) $criteria->add(StockTableMap::CREATED_AT, $this->created_at);
        if ($this->isColumnModified(StockTableMap::UPDATED_AT)) $criteria->add(StockTableMap::UPDATED_AT, $this->updated_at);

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(StockTableMap::DATABASE_NAME);
        $criteria->add(StockTableMap::ID, $this->id);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return   int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {

        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Thelia\Model\Stock (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setProductId($this->getProductId());
        $copyObj->setIncrease($this->getIncrease());
        $copyObj->setQuantity($this->getQuantity());
        $copyObj->setPromo($this->getPromo());
        $copyObj->setNewness($this->getNewness());
        $copyObj->setWeight($this->getWeight());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getAttributeCombinations() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addAttributeCombination($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getCartItems() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addCartItem($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getProductPrices() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addProductPrice($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return                 \Thelia\Model\Stock Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Declares an association between this object and a ChildProduct object.
     *
     * @param                  ChildProduct $v
     * @return                 \Thelia\Model\Stock The current object (for fluent API support)
     * @throws PropelException
     */
    public function setProduct(ChildProduct $v = null)
    {
        if ($v === null) {
            $this->setProductId(NULL);
        } else {
            $this->setProductId($v->getId());
        }

        $this->aProduct = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildProduct object, it will not be re-added.
        if ($v !== null) {
            $v->addStock($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildProduct object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildProduct The associated ChildProduct object.
     * @throws PropelException
     */
    public function getProduct(ConnectionInterface $con = null)
    {
        if ($this->aProduct === null && ($this->product_id !== null)) {
            $this->aProduct = ChildProductQuery::create()->findPk($this->product_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aProduct->addStocks($this);
             */
        }

        return $this->aProduct;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('AttributeCombination' == $relationName) {
            return $this->initAttributeCombinations();
        }
        if ('CartItem' == $relationName) {
            return $this->initCartItems();
        }
        if ('ProductPrice' == $relationName) {
            return $this->initProductPrices();
        }
    }

    /**
     * Clears out the collAttributeCombinations collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addAttributeCombinations()
     */
    public function clearAttributeCombinations()
    {
        $this->collAttributeCombinations = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collAttributeCombinations collection loaded partially.
     */
    public function resetPartialAttributeCombinations($v = true)
    {
        $this->collAttributeCombinationsPartial = $v;
    }

    /**
     * Initializes the collAttributeCombinations collection.
     *
     * By default this just sets the collAttributeCombinations collection to an empty array (like clearcollAttributeCombinations());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initAttributeCombinations($overrideExisting = true)
    {
        if (null !== $this->collAttributeCombinations && !$overrideExisting) {
            return;
        }
        $this->collAttributeCombinations = new ObjectCollection();
        $this->collAttributeCombinations->setModel('\Thelia\Model\AttributeCombination');
    }

    /**
     * Gets an array of ChildAttributeCombination objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildStock is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildAttributeCombination[] List of ChildAttributeCombination objects
     * @throws PropelException
     */
    public function getAttributeCombinations($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collAttributeCombinationsPartial && !$this->isNew();
        if (null === $this->collAttributeCombinations || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collAttributeCombinations) {
                // return empty collection
                $this->initAttributeCombinations();
            } else {
                $collAttributeCombinations = ChildAttributeCombinationQuery::create(null, $criteria)
                    ->filterByStock($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collAttributeCombinationsPartial && count($collAttributeCombinations)) {
                        $this->initAttributeCombinations(false);

                        foreach ($collAttributeCombinations as $obj) {
                            if (false == $this->collAttributeCombinations->contains($obj)) {
                                $this->collAttributeCombinations->append($obj);
                            }
                        }

                        $this->collAttributeCombinationsPartial = true;
                    }

                    $collAttributeCombinations->getInternalIterator()->rewind();

                    return $collAttributeCombinations;
                }

                if ($partial && $this->collAttributeCombinations) {
                    foreach ($this->collAttributeCombinations as $obj) {
                        if ($obj->isNew()) {
                            $collAttributeCombinations[] = $obj;
                        }
                    }
                }

                $this->collAttributeCombinations = $collAttributeCombinations;
                $this->collAttributeCombinationsPartial = false;
            }
        }

        return $this->collAttributeCombinations;
    }

    /**
     * Sets a collection of AttributeCombination objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $attributeCombinations A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildStock The current object (for fluent API support)
     */
    public function setAttributeCombinations(Collection $attributeCombinations, ConnectionInterface $con = null)
    {
        $attributeCombinationsToDelete = $this->getAttributeCombinations(new Criteria(), $con)->diff($attributeCombinations);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->attributeCombinationsScheduledForDeletion = clone $attributeCombinationsToDelete;

        foreach ($attributeCombinationsToDelete as $attributeCombinationRemoved) {
            $attributeCombinationRemoved->setStock(null);
        }

        $this->collAttributeCombinations = null;
        foreach ($attributeCombinations as $attributeCombination) {
            $this->addAttributeCombination($attributeCombination);
        }

        $this->collAttributeCombinations = $attributeCombinations;
        $this->collAttributeCombinationsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related AttributeCombination objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related AttributeCombination objects.
     * @throws PropelException
     */
    public function countAttributeCombinations(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collAttributeCombinationsPartial && !$this->isNew();
        if (null === $this->collAttributeCombinations || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collAttributeCombinations) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getAttributeCombinations());
            }

            $query = ChildAttributeCombinationQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByStock($this)
                ->count($con);
        }

        return count($this->collAttributeCombinations);
    }

    /**
     * Method called to associate a ChildAttributeCombination object to this object
     * through the ChildAttributeCombination foreign key attribute.
     *
     * @param    ChildAttributeCombination $l ChildAttributeCombination
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function addAttributeCombination(ChildAttributeCombination $l)
    {
        if ($this->collAttributeCombinations === null) {
            $this->initAttributeCombinations();
            $this->collAttributeCombinationsPartial = true;
        }

        if (!in_array($l, $this->collAttributeCombinations->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddAttributeCombination($l);
        }

        return $this;
    }

    /**
     * @param AttributeCombination $attributeCombination The attributeCombination object to add.
     */
    protected function doAddAttributeCombination($attributeCombination)
    {
        $this->collAttributeCombinations[]= $attributeCombination;
        $attributeCombination->setStock($this);
    }

    /**
     * @param  AttributeCombination $attributeCombination The attributeCombination object to remove.
     * @return ChildStock The current object (for fluent API support)
     */
    public function removeAttributeCombination($attributeCombination)
    {
        if ($this->getAttributeCombinations()->contains($attributeCombination)) {
            $this->collAttributeCombinations->remove($this->collAttributeCombinations->search($attributeCombination));
            if (null === $this->attributeCombinationsScheduledForDeletion) {
                $this->attributeCombinationsScheduledForDeletion = clone $this->collAttributeCombinations;
                $this->attributeCombinationsScheduledForDeletion->clear();
            }
            $this->attributeCombinationsScheduledForDeletion[]= clone $attributeCombination;
            $attributeCombination->setStock(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Stock is new, it will return
     * an empty collection; or if this Stock has previously
     * been saved, it will retrieve related AttributeCombinations from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Stock.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildAttributeCombination[] List of ChildAttributeCombination objects
     */
    public function getAttributeCombinationsJoinAttribute($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildAttributeCombinationQuery::create(null, $criteria);
        $query->joinWith('Attribute', $joinBehavior);

        return $this->getAttributeCombinations($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Stock is new, it will return
     * an empty collection; or if this Stock has previously
     * been saved, it will retrieve related AttributeCombinations from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Stock.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildAttributeCombination[] List of ChildAttributeCombination objects
     */
    public function getAttributeCombinationsJoinAttributeAv($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildAttributeCombinationQuery::create(null, $criteria);
        $query->joinWith('AttributeAv', $joinBehavior);

        return $this->getAttributeCombinations($query, $con);
    }

    /**
     * Clears out the collCartItems collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addCartItems()
     */
    public function clearCartItems()
    {
        $this->collCartItems = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collCartItems collection loaded partially.
     */
    public function resetPartialCartItems($v = true)
    {
        $this->collCartItemsPartial = $v;
    }

    /**
     * Initializes the collCartItems collection.
     *
     * By default this just sets the collCartItems collection to an empty array (like clearcollCartItems());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initCartItems($overrideExisting = true)
    {
        if (null !== $this->collCartItems && !$overrideExisting) {
            return;
        }
        $this->collCartItems = new ObjectCollection();
        $this->collCartItems->setModel('\Thelia\Model\CartItem');
    }

    /**
     * Gets an array of ChildCartItem objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildStock is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildCartItem[] List of ChildCartItem objects
     * @throws PropelException
     */
    public function getCartItems($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collCartItemsPartial && !$this->isNew();
        if (null === $this->collCartItems || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collCartItems) {
                // return empty collection
                $this->initCartItems();
            } else {
                $collCartItems = ChildCartItemQuery::create(null, $criteria)
                    ->filterByStock($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collCartItemsPartial && count($collCartItems)) {
                        $this->initCartItems(false);

                        foreach ($collCartItems as $obj) {
                            if (false == $this->collCartItems->contains($obj)) {
                                $this->collCartItems->append($obj);
                            }
                        }

                        $this->collCartItemsPartial = true;
                    }

                    $collCartItems->getInternalIterator()->rewind();

                    return $collCartItems;
                }

                if ($partial && $this->collCartItems) {
                    foreach ($this->collCartItems as $obj) {
                        if ($obj->isNew()) {
                            $collCartItems[] = $obj;
                        }
                    }
                }

                $this->collCartItems = $collCartItems;
                $this->collCartItemsPartial = false;
            }
        }

        return $this->collCartItems;
    }

    /**
     * Sets a collection of CartItem objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $cartItems A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildStock The current object (for fluent API support)
     */
    public function setCartItems(Collection $cartItems, ConnectionInterface $con = null)
    {
        $cartItemsToDelete = $this->getCartItems(new Criteria(), $con)->diff($cartItems);


        $this->cartItemsScheduledForDeletion = $cartItemsToDelete;

        foreach ($cartItemsToDelete as $cartItemRemoved) {
            $cartItemRemoved->setStock(null);
        }

        $this->collCartItems = null;
        foreach ($cartItems as $cartItem) {
            $this->addCartItem($cartItem);
        }

        $this->collCartItems = $cartItems;
        $this->collCartItemsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related CartItem objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related CartItem objects.
     * @throws PropelException
     */
    public function countCartItems(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collCartItemsPartial && !$this->isNew();
        if (null === $this->collCartItems || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collCartItems) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getCartItems());
            }

            $query = ChildCartItemQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByStock($this)
                ->count($con);
        }

        return count($this->collCartItems);
    }

    /**
     * Method called to associate a ChildCartItem object to this object
     * through the ChildCartItem foreign key attribute.
     *
     * @param    ChildCartItem $l ChildCartItem
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function addCartItem(ChildCartItem $l)
    {
        if ($this->collCartItems === null) {
            $this->initCartItems();
            $this->collCartItemsPartial = true;
        }

        if (!in_array($l, $this->collCartItems->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddCartItem($l);
        }

        return $this;
    }

    /**
     * @param CartItem $cartItem The cartItem object to add.
     */
    protected function doAddCartItem($cartItem)
    {
        $this->collCartItems[]= $cartItem;
        $cartItem->setStock($this);
    }

    /**
     * @param  CartItem $cartItem The cartItem object to remove.
     * @return ChildStock The current object (for fluent API support)
     */
    public function removeCartItem($cartItem)
    {
        if ($this->getCartItems()->contains($cartItem)) {
            $this->collCartItems->remove($this->collCartItems->search($cartItem));
            if (null === $this->cartItemsScheduledForDeletion) {
                $this->cartItemsScheduledForDeletion = clone $this->collCartItems;
                $this->cartItemsScheduledForDeletion->clear();
            }
            $this->cartItemsScheduledForDeletion[]= clone $cartItem;
            $cartItem->setStock(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Stock is new, it will return
     * an empty collection; or if this Stock has previously
     * been saved, it will retrieve related CartItems from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Stock.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildCartItem[] List of ChildCartItem objects
     */
    public function getCartItemsJoinCart($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildCartItemQuery::create(null, $criteria);
        $query->joinWith('Cart', $joinBehavior);

        return $this->getCartItems($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Stock is new, it will return
     * an empty collection; or if this Stock has previously
     * been saved, it will retrieve related CartItems from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Stock.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildCartItem[] List of ChildCartItem objects
     */
    public function getCartItemsJoinProduct($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildCartItemQuery::create(null, $criteria);
        $query->joinWith('Product', $joinBehavior);

        return $this->getCartItems($query, $con);
    }

    /**
     * Clears out the collProductPrices collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addProductPrices()
     */
    public function clearProductPrices()
    {
        $this->collProductPrices = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collProductPrices collection loaded partially.
     */
    public function resetPartialProductPrices($v = true)
    {
        $this->collProductPricesPartial = $v;
    }

    /**
     * Initializes the collProductPrices collection.
     *
     * By default this just sets the collProductPrices collection to an empty array (like clearcollProductPrices());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initProductPrices($overrideExisting = true)
    {
        if (null !== $this->collProductPrices && !$overrideExisting) {
            return;
        }
        $this->collProductPrices = new ObjectCollection();
        $this->collProductPrices->setModel('\Thelia\Model\ProductPrice');
    }

    /**
     * Gets an array of ChildProductPrice objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildStock is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildProductPrice[] List of ChildProductPrice objects
     * @throws PropelException
     */
    public function getProductPrices($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collProductPricesPartial && !$this->isNew();
        if (null === $this->collProductPrices || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collProductPrices) {
                // return empty collection
                $this->initProductPrices();
            } else {
                $collProductPrices = ChildProductPriceQuery::create(null, $criteria)
                    ->filterByStock($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collProductPricesPartial && count($collProductPrices)) {
                        $this->initProductPrices(false);

                        foreach ($collProductPrices as $obj) {
                            if (false == $this->collProductPrices->contains($obj)) {
                                $this->collProductPrices->append($obj);
                            }
                        }

                        $this->collProductPricesPartial = true;
                    }

                    $collProductPrices->getInternalIterator()->rewind();

                    return $collProductPrices;
                }

                if ($partial && $this->collProductPrices) {
                    foreach ($this->collProductPrices as $obj) {
                        if ($obj->isNew()) {
                            $collProductPrices[] = $obj;
                        }
                    }
                }

                $this->collProductPrices = $collProductPrices;
                $this->collProductPricesPartial = false;
            }
        }

        return $this->collProductPrices;
    }

    /**
     * Sets a collection of ProductPrice objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $productPrices A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildStock The current object (for fluent API support)
     */
    public function setProductPrices(Collection $productPrices, ConnectionInterface $con = null)
    {
        $productPricesToDelete = $this->getProductPrices(new Criteria(), $con)->diff($productPrices);


        $this->productPricesScheduledForDeletion = $productPricesToDelete;

        foreach ($productPricesToDelete as $productPriceRemoved) {
            $productPriceRemoved->setStock(null);
        }

        $this->collProductPrices = null;
        foreach ($productPrices as $productPrice) {
            $this->addProductPrice($productPrice);
        }

        $this->collProductPrices = $productPrices;
        $this->collProductPricesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ProductPrice objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ProductPrice objects.
     * @throws PropelException
     */
    public function countProductPrices(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collProductPricesPartial && !$this->isNew();
        if (null === $this->collProductPrices || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collProductPrices) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getProductPrices());
            }

            $query = ChildProductPriceQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByStock($this)
                ->count($con);
        }

        return count($this->collProductPrices);
    }

    /**
     * Method called to associate a ChildProductPrice object to this object
     * through the ChildProductPrice foreign key attribute.
     *
     * @param    ChildProductPrice $l ChildProductPrice
     * @return   \Thelia\Model\Stock The current object (for fluent API support)
     */
    public function addProductPrice(ChildProductPrice $l)
    {
        if ($this->collProductPrices === null) {
            $this->initProductPrices();
            $this->collProductPricesPartial = true;
        }

        if (!in_array($l, $this->collProductPrices->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddProductPrice($l);
        }

        return $this;
    }

    /**
     * @param ProductPrice $productPrice The productPrice object to add.
     */
    protected function doAddProductPrice($productPrice)
    {
        $this->collProductPrices[]= $productPrice;
        $productPrice->setStock($this);
    }

    /**
     * @param  ProductPrice $productPrice The productPrice object to remove.
     * @return ChildStock The current object (for fluent API support)
     */
    public function removeProductPrice($productPrice)
    {
        if ($this->getProductPrices()->contains($productPrice)) {
            $this->collProductPrices->remove($this->collProductPrices->search($productPrice));
            if (null === $this->productPricesScheduledForDeletion) {
                $this->productPricesScheduledForDeletion = clone $this->collProductPrices;
                $this->productPricesScheduledForDeletion->clear();
            }
            $this->productPricesScheduledForDeletion[]= clone $productPrice;
            $productPrice->setStock(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Stock is new, it will return
     * an empty collection; or if this Stock has previously
     * been saved, it will retrieve related ProductPrices from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Stock.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildProductPrice[] List of ChildProductPrice objects
     */
    public function getProductPricesJoinCurrency($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildProductPriceQuery::create(null, $criteria);
        $query->joinWith('Currency', $joinBehavior);

        return $this->getProductPrices($query, $con);
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->id = null;
        $this->product_id = null;
        $this->increase = null;
        $this->quantity = null;
        $this->promo = null;
        $this->newness = null;
        $this->weight = null;
        $this->created_at = null;
        $this->updated_at = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collAttributeCombinations) {
                foreach ($this->collAttributeCombinations as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collCartItems) {
                foreach ($this->collCartItems as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collProductPrices) {
                foreach ($this->collProductPrices as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        if ($this->collAttributeCombinations instanceof Collection) {
            $this->collAttributeCombinations->clearIterator();
        }
        $this->collAttributeCombinations = null;
        if ($this->collCartItems instanceof Collection) {
            $this->collCartItems->clearIterator();
        }
        $this->collCartItems = null;
        if ($this->collProductPrices instanceof Collection) {
            $this->collProductPrices->clearIterator();
        }
        $this->collProductPrices = null;
        $this->aProduct = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(StockTableMap::DEFAULT_STRING_FORMAT);
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     ChildStock The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[] = StockTableMap::UPDATED_AT;

        return $this;
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
