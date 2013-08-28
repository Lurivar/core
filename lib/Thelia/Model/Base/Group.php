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
use Thelia\Model\Admin as ChildAdmin;
use Thelia\Model\AdminGroup as ChildAdminGroup;
use Thelia\Model\AdminGroupQuery as ChildAdminGroupQuery;
use Thelia\Model\AdminQuery as ChildAdminQuery;
use Thelia\Model\Group as ChildGroup;
use Thelia\Model\GroupI18n as ChildGroupI18n;
use Thelia\Model\GroupI18nQuery as ChildGroupI18nQuery;
use Thelia\Model\GroupModule as ChildGroupModule;
use Thelia\Model\GroupModuleQuery as ChildGroupModuleQuery;
use Thelia\Model\GroupQuery as ChildGroupQuery;
use Thelia\Model\GroupResource as ChildGroupResource;
use Thelia\Model\GroupResourceQuery as ChildGroupResourceQuery;
use Thelia\Model\Resource as ChildResource;
use Thelia\Model\ResourceQuery as ChildResourceQuery;
use Thelia\Model\Map\GroupTableMap;

abstract class Group implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Thelia\\Model\\Map\\GroupTableMap';


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
     * The value for the code field.
     * @var        string
     */
    protected $code;

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
     * @var        ObjectCollection|ChildAdminGroup[] Collection to store aggregation of ChildAdminGroup objects.
     */
    protected $collAdminGroups;
    protected $collAdminGroupsPartial;

    /**
     * @var        ObjectCollection|ChildGroupResource[] Collection to store aggregation of ChildGroupResource objects.
     */
    protected $collGroupResources;
    protected $collGroupResourcesPartial;

    /**
     * @var        ObjectCollection|ChildGroupModule[] Collection to store aggregation of ChildGroupModule objects.
     */
    protected $collGroupModules;
    protected $collGroupModulesPartial;

    /**
     * @var        ObjectCollection|ChildGroupI18n[] Collection to store aggregation of ChildGroupI18n objects.
     */
    protected $collGroupI18ns;
    protected $collGroupI18nsPartial;

    /**
     * @var        ChildAdmin[] Collection to store aggregation of ChildAdmin objects.
     */
    protected $collAdmins;

    /**
     * @var        ChildResource[] Collection to store aggregation of ChildResource objects.
     */
    protected $collResources;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    // i18n behavior

    /**
     * Current locale
     * @var        string
     */
    protected $currentLocale = 'en_EN';

    /**
     * Current translation objects
     * @var        array[ChildGroupI18n]
     */
    protected $currentTranslations;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $adminsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $resourcesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $adminGroupsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $groupResourcesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $groupModulesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $groupI18nsScheduledForDeletion = null;

    /**
     * Initializes internal state of Thelia\Model\Base\Group object.
     */
    public function __construct()
    {
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
     * Compares this with another <code>Group</code> instance.  If
     * <code>obj</code> is an instance of <code>Group</code>, delegates to
     * <code>equals(Group)</code>.  Otherwise, returns <code>false</code>.
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
        return array_key_exists($name, $this->virtualColumns);
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
     * @return Group The current object, for fluid interface
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
     * @return Group The current object, for fluid interface
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
     * Get the [code] column value.
     *
     * @return   string
     */
    public function getCode()
    {

        return $this->code;
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
     * @return   \Thelia\Model\Group The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[] = GroupTableMap::ID;
        }


        return $this;
    } // setId()

    /**
     * Set the value of [code] column.
     *
     * @param      string $v new value
     * @return   \Thelia\Model\Group The current object (for fluent API support)
     */
    public function setCode($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->code !== $v) {
            $this->code = $v;
            $this->modifiedColumns[] = GroupTableMap::CODE;
        }


        return $this;
    } // setCode()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Thelia\Model\Group The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->created_at !== null || $dt !== null) {
            if ($dt !== $this->created_at) {
                $this->created_at = $dt;
                $this->modifiedColumns[] = GroupTableMap::CREATED_AT;
            }
        } // if either are not null


        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [updated_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Thelia\Model\Group The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->updated_at !== null || $dt !== null) {
            if ($dt !== $this->updated_at) {
                $this->updated_at = $dt;
                $this->modifiedColumns[] = GroupTableMap::UPDATED_AT;
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


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : GroupTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : GroupTableMap::translateFieldName('Code', TableMap::TYPE_PHPNAME, $indexType)];
            $this->code = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : GroupTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : GroupTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 4; // 4 = GroupTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \Thelia\Model\Group object", 0, $e);
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
            $con = Propel::getServiceContainer()->getReadConnection(GroupTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildGroupQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collAdminGroups = null;

            $this->collGroupResources = null;

            $this->collGroupModules = null;

            $this->collGroupI18ns = null;

            $this->collAdmins = null;
            $this->collResources = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Group::setDeleted()
     * @see Group::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(GroupTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildGroupQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(GroupTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior
                if (!$this->isColumnModified(GroupTableMap::CREATED_AT)) {
                    $this->setCreatedAt(time());
                }
                if (!$this->isColumnModified(GroupTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(GroupTableMap::UPDATED_AT)) {
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
                GroupTableMap::addInstanceToPool($this);
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

            if ($this->adminsScheduledForDeletion !== null) {
                if (!$this->adminsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    $pk  = $this->getPrimaryKey();
                    foreach ($this->adminsScheduledForDeletion->getPrimaryKeys(false) as $remotePk) {
                        $pks[] = array($pk, $remotePk);
                    }

                    AdminGroupQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);
                    $this->adminsScheduledForDeletion = null;
                }

                foreach ($this->getAdmins() as $admin) {
                    if ($admin->isModified()) {
                        $admin->save($con);
                    }
                }
            } elseif ($this->collAdmins) {
                foreach ($this->collAdmins as $admin) {
                    if ($admin->isModified()) {
                        $admin->save($con);
                    }
                }
            }

            if ($this->resourcesScheduledForDeletion !== null) {
                if (!$this->resourcesScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    $pk  = $this->getPrimaryKey();
                    foreach ($this->resourcesScheduledForDeletion->getPrimaryKeys(false) as $remotePk) {
                        $pks[] = array($pk, $remotePk);
                    }

                    GroupResourceQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);
                    $this->resourcesScheduledForDeletion = null;
                }

                foreach ($this->getResources() as $resource) {
                    if ($resource->isModified()) {
                        $resource->save($con);
                    }
                }
            } elseif ($this->collResources) {
                foreach ($this->collResources as $resource) {
                    if ($resource->isModified()) {
                        $resource->save($con);
                    }
                }
            }

            if ($this->adminGroupsScheduledForDeletion !== null) {
                if (!$this->adminGroupsScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\AdminGroupQuery::create()
                        ->filterByPrimaryKeys($this->adminGroupsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->adminGroupsScheduledForDeletion = null;
                }
            }

                if ($this->collAdminGroups !== null) {
            foreach ($this->collAdminGroups as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->groupResourcesScheduledForDeletion !== null) {
                if (!$this->groupResourcesScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\GroupResourceQuery::create()
                        ->filterByPrimaryKeys($this->groupResourcesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->groupResourcesScheduledForDeletion = null;
                }
            }

                if ($this->collGroupResources !== null) {
            foreach ($this->collGroupResources as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->groupModulesScheduledForDeletion !== null) {
                if (!$this->groupModulesScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\GroupModuleQuery::create()
                        ->filterByPrimaryKeys($this->groupModulesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->groupModulesScheduledForDeletion = null;
                }
            }

                if ($this->collGroupModules !== null) {
            foreach ($this->collGroupModules as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->groupI18nsScheduledForDeletion !== null) {
                if (!$this->groupI18nsScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\GroupI18nQuery::create()
                        ->filterByPrimaryKeys($this->groupI18nsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->groupI18nsScheduledForDeletion = null;
                }
            }

                if ($this->collGroupI18ns !== null) {
            foreach ($this->collGroupI18ns as $referrerFK) {
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

        $this->modifiedColumns[] = GroupTableMap::ID;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . GroupTableMap::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(GroupTableMap::ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(GroupTableMap::CODE)) {
            $modifiedColumns[':p' . $index++]  = 'CODE';
        }
        if ($this->isColumnModified(GroupTableMap::CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED_AT';
        }
        if ($this->isColumnModified(GroupTableMap::UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'UPDATED_AT';
        }

        $sql = sprintf(
            'INSERT INTO group (%s) VALUES (%s)',
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
                    case 'CODE':
                        $stmt->bindValue($identifier, $this->code, PDO::PARAM_STR);
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
        $pos = GroupTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getCode();
                break;
            case 2:
                return $this->getCreatedAt();
                break;
            case 3:
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
        if (isset($alreadyDumpedObjects['Group'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Group'][$this->getPrimaryKey()] = true;
        $keys = GroupTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getCode(),
            $keys[2] => $this->getCreatedAt(),
            $keys[3] => $this->getUpdatedAt(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach($virtualColumns as $key => $virtualColumn)
        {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collAdminGroups) {
                $result['AdminGroups'] = $this->collAdminGroups->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collGroupResources) {
                $result['GroupResources'] = $this->collGroupResources->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collGroupModules) {
                $result['GroupModules'] = $this->collGroupModules->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collGroupI18ns) {
                $result['GroupI18ns'] = $this->collGroupI18ns->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
        $pos = GroupTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

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
                $this->setCode($value);
                break;
            case 2:
                $this->setCreatedAt($value);
                break;
            case 3:
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
        $keys = GroupTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setCode($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setCreatedAt($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setUpdatedAt($arr[$keys[3]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(GroupTableMap::DATABASE_NAME);

        if ($this->isColumnModified(GroupTableMap::ID)) $criteria->add(GroupTableMap::ID, $this->id);
        if ($this->isColumnModified(GroupTableMap::CODE)) $criteria->add(GroupTableMap::CODE, $this->code);
        if ($this->isColumnModified(GroupTableMap::CREATED_AT)) $criteria->add(GroupTableMap::CREATED_AT, $this->created_at);
        if ($this->isColumnModified(GroupTableMap::UPDATED_AT)) $criteria->add(GroupTableMap::UPDATED_AT, $this->updated_at);

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
        $criteria = new Criteria(GroupTableMap::DATABASE_NAME);
        $criteria->add(GroupTableMap::ID, $this->id);

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
     * @param      object $copyObj An object of \Thelia\Model\Group (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setCode($this->getCode());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getAdminGroups() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addAdminGroup($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getGroupResources() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addGroupResource($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getGroupModules() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addGroupModule($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getGroupI18ns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addGroupI18n($relObj->copy($deepCopy));
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
     * @return                 \Thelia\Model\Group Clone of current object.
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
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('AdminGroup' == $relationName) {
            return $this->initAdminGroups();
        }
        if ('GroupResource' == $relationName) {
            return $this->initGroupResources();
        }
        if ('GroupModule' == $relationName) {
            return $this->initGroupModules();
        }
        if ('GroupI18n' == $relationName) {
            return $this->initGroupI18ns();
        }
    }

    /**
     * Clears out the collAdminGroups collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addAdminGroups()
     */
    public function clearAdminGroups()
    {
        $this->collAdminGroups = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collAdminGroups collection loaded partially.
     */
    public function resetPartialAdminGroups($v = true)
    {
        $this->collAdminGroupsPartial = $v;
    }

    /**
     * Initializes the collAdminGroups collection.
     *
     * By default this just sets the collAdminGroups collection to an empty array (like clearcollAdminGroups());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initAdminGroups($overrideExisting = true)
    {
        if (null !== $this->collAdminGroups && !$overrideExisting) {
            return;
        }
        $this->collAdminGroups = new ObjectCollection();
        $this->collAdminGroups->setModel('\Thelia\Model\AdminGroup');
    }

    /**
     * Gets an array of ChildAdminGroup objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildAdminGroup[] List of ChildAdminGroup objects
     * @throws PropelException
     */
    public function getAdminGroups($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collAdminGroupsPartial && !$this->isNew();
        if (null === $this->collAdminGroups || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collAdminGroups) {
                // return empty collection
                $this->initAdminGroups();
            } else {
                $collAdminGroups = ChildAdminGroupQuery::create(null, $criteria)
                    ->filterByGroup($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collAdminGroupsPartial && count($collAdminGroups)) {
                        $this->initAdminGroups(false);

                        foreach ($collAdminGroups as $obj) {
                            if (false == $this->collAdminGroups->contains($obj)) {
                                $this->collAdminGroups->append($obj);
                            }
                        }

                        $this->collAdminGroupsPartial = true;
                    }

                    $collAdminGroups->getInternalIterator()->rewind();

                    return $collAdminGroups;
                }

                if ($partial && $this->collAdminGroups) {
                    foreach ($this->collAdminGroups as $obj) {
                        if ($obj->isNew()) {
                            $collAdminGroups[] = $obj;
                        }
                    }
                }

                $this->collAdminGroups = $collAdminGroups;
                $this->collAdminGroupsPartial = false;
            }
        }

        return $this->collAdminGroups;
    }

    /**
     * Sets a collection of AdminGroup objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $adminGroups A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildGroup The current object (for fluent API support)
     */
    public function setAdminGroups(Collection $adminGroups, ConnectionInterface $con = null)
    {
        $adminGroupsToDelete = $this->getAdminGroups(new Criteria(), $con)->diff($adminGroups);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->adminGroupsScheduledForDeletion = clone $adminGroupsToDelete;

        foreach ($adminGroupsToDelete as $adminGroupRemoved) {
            $adminGroupRemoved->setGroup(null);
        }

        $this->collAdminGroups = null;
        foreach ($adminGroups as $adminGroup) {
            $this->addAdminGroup($adminGroup);
        }

        $this->collAdminGroups = $adminGroups;
        $this->collAdminGroupsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related AdminGroup objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related AdminGroup objects.
     * @throws PropelException
     */
    public function countAdminGroups(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collAdminGroupsPartial && !$this->isNew();
        if (null === $this->collAdminGroups || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collAdminGroups) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getAdminGroups());
            }

            $query = ChildAdminGroupQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGroup($this)
                ->count($con);
        }

        return count($this->collAdminGroups);
    }

    /**
     * Method called to associate a ChildAdminGroup object to this object
     * through the ChildAdminGroup foreign key attribute.
     *
     * @param    ChildAdminGroup $l ChildAdminGroup
     * @return   \Thelia\Model\Group The current object (for fluent API support)
     */
    public function addAdminGroup(ChildAdminGroup $l)
    {
        if ($this->collAdminGroups === null) {
            $this->initAdminGroups();
            $this->collAdminGroupsPartial = true;
        }

        if (!in_array($l, $this->collAdminGroups->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddAdminGroup($l);
        }

        return $this;
    }

    /**
     * @param AdminGroup $adminGroup The adminGroup object to add.
     */
    protected function doAddAdminGroup($adminGroup)
    {
        $this->collAdminGroups[]= $adminGroup;
        $adminGroup->setGroup($this);
    }

    /**
     * @param  AdminGroup $adminGroup The adminGroup object to remove.
     * @return ChildGroup The current object (for fluent API support)
     */
    public function removeAdminGroup($adminGroup)
    {
        if ($this->getAdminGroups()->contains($adminGroup)) {
            $this->collAdminGroups->remove($this->collAdminGroups->search($adminGroup));
            if (null === $this->adminGroupsScheduledForDeletion) {
                $this->adminGroupsScheduledForDeletion = clone $this->collAdminGroups;
                $this->adminGroupsScheduledForDeletion->clear();
            }
            $this->adminGroupsScheduledForDeletion[]= clone $adminGroup;
            $adminGroup->setGroup(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Group is new, it will return
     * an empty collection; or if this Group has previously
     * been saved, it will retrieve related AdminGroups from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Group.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildAdminGroup[] List of ChildAdminGroup objects
     */
    public function getAdminGroupsJoinAdmin($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildAdminGroupQuery::create(null, $criteria);
        $query->joinWith('Admin', $joinBehavior);

        return $this->getAdminGroups($query, $con);
    }

    /**
     * Clears out the collGroupResources collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addGroupResources()
     */
    public function clearGroupResources()
    {
        $this->collGroupResources = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collGroupResources collection loaded partially.
     */
    public function resetPartialGroupResources($v = true)
    {
        $this->collGroupResourcesPartial = $v;
    }

    /**
     * Initializes the collGroupResources collection.
     *
     * By default this just sets the collGroupResources collection to an empty array (like clearcollGroupResources());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initGroupResources($overrideExisting = true)
    {
        if (null !== $this->collGroupResources && !$overrideExisting) {
            return;
        }
        $this->collGroupResources = new ObjectCollection();
        $this->collGroupResources->setModel('\Thelia\Model\GroupResource');
    }

    /**
     * Gets an array of ChildGroupResource objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildGroupResource[] List of ChildGroupResource objects
     * @throws PropelException
     */
    public function getGroupResources($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collGroupResourcesPartial && !$this->isNew();
        if (null === $this->collGroupResources || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collGroupResources) {
                // return empty collection
                $this->initGroupResources();
            } else {
                $collGroupResources = ChildGroupResourceQuery::create(null, $criteria)
                    ->filterByGroup($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collGroupResourcesPartial && count($collGroupResources)) {
                        $this->initGroupResources(false);

                        foreach ($collGroupResources as $obj) {
                            if (false == $this->collGroupResources->contains($obj)) {
                                $this->collGroupResources->append($obj);
                            }
                        }

                        $this->collGroupResourcesPartial = true;
                    }

                    $collGroupResources->getInternalIterator()->rewind();

                    return $collGroupResources;
                }

                if ($partial && $this->collGroupResources) {
                    foreach ($this->collGroupResources as $obj) {
                        if ($obj->isNew()) {
                            $collGroupResources[] = $obj;
                        }
                    }
                }

                $this->collGroupResources = $collGroupResources;
                $this->collGroupResourcesPartial = false;
            }
        }

        return $this->collGroupResources;
    }

    /**
     * Sets a collection of GroupResource objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $groupResources A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildGroup The current object (for fluent API support)
     */
    public function setGroupResources(Collection $groupResources, ConnectionInterface $con = null)
    {
        $groupResourcesToDelete = $this->getGroupResources(new Criteria(), $con)->diff($groupResources);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->groupResourcesScheduledForDeletion = clone $groupResourcesToDelete;

        foreach ($groupResourcesToDelete as $groupResourceRemoved) {
            $groupResourceRemoved->setGroup(null);
        }

        $this->collGroupResources = null;
        foreach ($groupResources as $groupResource) {
            $this->addGroupResource($groupResource);
        }

        $this->collGroupResources = $groupResources;
        $this->collGroupResourcesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related GroupResource objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related GroupResource objects.
     * @throws PropelException
     */
    public function countGroupResources(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collGroupResourcesPartial && !$this->isNew();
        if (null === $this->collGroupResources || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collGroupResources) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getGroupResources());
            }

            $query = ChildGroupResourceQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGroup($this)
                ->count($con);
        }

        return count($this->collGroupResources);
    }

    /**
     * Method called to associate a ChildGroupResource object to this object
     * through the ChildGroupResource foreign key attribute.
     *
     * @param    ChildGroupResource $l ChildGroupResource
     * @return   \Thelia\Model\Group The current object (for fluent API support)
     */
    public function addGroupResource(ChildGroupResource $l)
    {
        if ($this->collGroupResources === null) {
            $this->initGroupResources();
            $this->collGroupResourcesPartial = true;
        }

        if (!in_array($l, $this->collGroupResources->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddGroupResource($l);
        }

        return $this;
    }

    /**
     * @param GroupResource $groupResource The groupResource object to add.
     */
    protected function doAddGroupResource($groupResource)
    {
        $this->collGroupResources[]= $groupResource;
        $groupResource->setGroup($this);
    }

    /**
     * @param  GroupResource $groupResource The groupResource object to remove.
     * @return ChildGroup The current object (for fluent API support)
     */
    public function removeGroupResource($groupResource)
    {
        if ($this->getGroupResources()->contains($groupResource)) {
            $this->collGroupResources->remove($this->collGroupResources->search($groupResource));
            if (null === $this->groupResourcesScheduledForDeletion) {
                $this->groupResourcesScheduledForDeletion = clone $this->collGroupResources;
                $this->groupResourcesScheduledForDeletion->clear();
            }
            $this->groupResourcesScheduledForDeletion[]= clone $groupResource;
            $groupResource->setGroup(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Group is new, it will return
     * an empty collection; or if this Group has previously
     * been saved, it will retrieve related GroupResources from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Group.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildGroupResource[] List of ChildGroupResource objects
     */
    public function getGroupResourcesJoinResource($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildGroupResourceQuery::create(null, $criteria);
        $query->joinWith('Resource', $joinBehavior);

        return $this->getGroupResources($query, $con);
    }

    /**
     * Clears out the collGroupModules collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addGroupModules()
     */
    public function clearGroupModules()
    {
        $this->collGroupModules = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collGroupModules collection loaded partially.
     */
    public function resetPartialGroupModules($v = true)
    {
        $this->collGroupModulesPartial = $v;
    }

    /**
     * Initializes the collGroupModules collection.
     *
     * By default this just sets the collGroupModules collection to an empty array (like clearcollGroupModules());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initGroupModules($overrideExisting = true)
    {
        if (null !== $this->collGroupModules && !$overrideExisting) {
            return;
        }
        $this->collGroupModules = new ObjectCollection();
        $this->collGroupModules->setModel('\Thelia\Model\GroupModule');
    }

    /**
     * Gets an array of ChildGroupModule objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildGroupModule[] List of ChildGroupModule objects
     * @throws PropelException
     */
    public function getGroupModules($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collGroupModulesPartial && !$this->isNew();
        if (null === $this->collGroupModules || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collGroupModules) {
                // return empty collection
                $this->initGroupModules();
            } else {
                $collGroupModules = ChildGroupModuleQuery::create(null, $criteria)
                    ->filterByGroup($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collGroupModulesPartial && count($collGroupModules)) {
                        $this->initGroupModules(false);

                        foreach ($collGroupModules as $obj) {
                            if (false == $this->collGroupModules->contains($obj)) {
                                $this->collGroupModules->append($obj);
                            }
                        }

                        $this->collGroupModulesPartial = true;
                    }

                    $collGroupModules->getInternalIterator()->rewind();

                    return $collGroupModules;
                }

                if ($partial && $this->collGroupModules) {
                    foreach ($this->collGroupModules as $obj) {
                        if ($obj->isNew()) {
                            $collGroupModules[] = $obj;
                        }
                    }
                }

                $this->collGroupModules = $collGroupModules;
                $this->collGroupModulesPartial = false;
            }
        }

        return $this->collGroupModules;
    }

    /**
     * Sets a collection of GroupModule objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $groupModules A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildGroup The current object (for fluent API support)
     */
    public function setGroupModules(Collection $groupModules, ConnectionInterface $con = null)
    {
        $groupModulesToDelete = $this->getGroupModules(new Criteria(), $con)->diff($groupModules);


        $this->groupModulesScheduledForDeletion = $groupModulesToDelete;

        foreach ($groupModulesToDelete as $groupModuleRemoved) {
            $groupModuleRemoved->setGroup(null);
        }

        $this->collGroupModules = null;
        foreach ($groupModules as $groupModule) {
            $this->addGroupModule($groupModule);
        }

        $this->collGroupModules = $groupModules;
        $this->collGroupModulesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related GroupModule objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related GroupModule objects.
     * @throws PropelException
     */
    public function countGroupModules(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collGroupModulesPartial && !$this->isNew();
        if (null === $this->collGroupModules || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collGroupModules) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getGroupModules());
            }

            $query = ChildGroupModuleQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGroup($this)
                ->count($con);
        }

        return count($this->collGroupModules);
    }

    /**
     * Method called to associate a ChildGroupModule object to this object
     * through the ChildGroupModule foreign key attribute.
     *
     * @param    ChildGroupModule $l ChildGroupModule
     * @return   \Thelia\Model\Group The current object (for fluent API support)
     */
    public function addGroupModule(ChildGroupModule $l)
    {
        if ($this->collGroupModules === null) {
            $this->initGroupModules();
            $this->collGroupModulesPartial = true;
        }

        if (!in_array($l, $this->collGroupModules->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddGroupModule($l);
        }

        return $this;
    }

    /**
     * @param GroupModule $groupModule The groupModule object to add.
     */
    protected function doAddGroupModule($groupModule)
    {
        $this->collGroupModules[]= $groupModule;
        $groupModule->setGroup($this);
    }

    /**
     * @param  GroupModule $groupModule The groupModule object to remove.
     * @return ChildGroup The current object (for fluent API support)
     */
    public function removeGroupModule($groupModule)
    {
        if ($this->getGroupModules()->contains($groupModule)) {
            $this->collGroupModules->remove($this->collGroupModules->search($groupModule));
            if (null === $this->groupModulesScheduledForDeletion) {
                $this->groupModulesScheduledForDeletion = clone $this->collGroupModules;
                $this->groupModulesScheduledForDeletion->clear();
            }
            $this->groupModulesScheduledForDeletion[]= clone $groupModule;
            $groupModule->setGroup(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Group is new, it will return
     * an empty collection; or if this Group has previously
     * been saved, it will retrieve related GroupModules from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Group.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildGroupModule[] List of ChildGroupModule objects
     */
    public function getGroupModulesJoinModule($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildGroupModuleQuery::create(null, $criteria);
        $query->joinWith('Module', $joinBehavior);

        return $this->getGroupModules($query, $con);
    }

    /**
     * Clears out the collGroupI18ns collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addGroupI18ns()
     */
    public function clearGroupI18ns()
    {
        $this->collGroupI18ns = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collGroupI18ns collection loaded partially.
     */
    public function resetPartialGroupI18ns($v = true)
    {
        $this->collGroupI18nsPartial = $v;
    }

    /**
     * Initializes the collGroupI18ns collection.
     *
     * By default this just sets the collGroupI18ns collection to an empty array (like clearcollGroupI18ns());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initGroupI18ns($overrideExisting = true)
    {
        if (null !== $this->collGroupI18ns && !$overrideExisting) {
            return;
        }
        $this->collGroupI18ns = new ObjectCollection();
        $this->collGroupI18ns->setModel('\Thelia\Model\GroupI18n');
    }

    /**
     * Gets an array of ChildGroupI18n objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildGroupI18n[] List of ChildGroupI18n objects
     * @throws PropelException
     */
    public function getGroupI18ns($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collGroupI18nsPartial && !$this->isNew();
        if (null === $this->collGroupI18ns || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collGroupI18ns) {
                // return empty collection
                $this->initGroupI18ns();
            } else {
                $collGroupI18ns = ChildGroupI18nQuery::create(null, $criteria)
                    ->filterByGroup($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collGroupI18nsPartial && count($collGroupI18ns)) {
                        $this->initGroupI18ns(false);

                        foreach ($collGroupI18ns as $obj) {
                            if (false == $this->collGroupI18ns->contains($obj)) {
                                $this->collGroupI18ns->append($obj);
                            }
                        }

                        $this->collGroupI18nsPartial = true;
                    }

                    $collGroupI18ns->getInternalIterator()->rewind();

                    return $collGroupI18ns;
                }

                if ($partial && $this->collGroupI18ns) {
                    foreach ($this->collGroupI18ns as $obj) {
                        if ($obj->isNew()) {
                            $collGroupI18ns[] = $obj;
                        }
                    }
                }

                $this->collGroupI18ns = $collGroupI18ns;
                $this->collGroupI18nsPartial = false;
            }
        }

        return $this->collGroupI18ns;
    }

    /**
     * Sets a collection of GroupI18n objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $groupI18ns A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildGroup The current object (for fluent API support)
     */
    public function setGroupI18ns(Collection $groupI18ns, ConnectionInterface $con = null)
    {
        $groupI18nsToDelete = $this->getGroupI18ns(new Criteria(), $con)->diff($groupI18ns);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->groupI18nsScheduledForDeletion = clone $groupI18nsToDelete;

        foreach ($groupI18nsToDelete as $groupI18nRemoved) {
            $groupI18nRemoved->setGroup(null);
        }

        $this->collGroupI18ns = null;
        foreach ($groupI18ns as $groupI18n) {
            $this->addGroupI18n($groupI18n);
        }

        $this->collGroupI18ns = $groupI18ns;
        $this->collGroupI18nsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related GroupI18n objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related GroupI18n objects.
     * @throws PropelException
     */
    public function countGroupI18ns(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collGroupI18nsPartial && !$this->isNew();
        if (null === $this->collGroupI18ns || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collGroupI18ns) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getGroupI18ns());
            }

            $query = ChildGroupI18nQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGroup($this)
                ->count($con);
        }

        return count($this->collGroupI18ns);
    }

    /**
     * Method called to associate a ChildGroupI18n object to this object
     * through the ChildGroupI18n foreign key attribute.
     *
     * @param    ChildGroupI18n $l ChildGroupI18n
     * @return   \Thelia\Model\Group The current object (for fluent API support)
     */
    public function addGroupI18n(ChildGroupI18n $l)
    {
        if ($l && $locale = $l->getLocale()) {
            $this->setLocale($locale);
            $this->currentTranslations[$locale] = $l;
        }
        if ($this->collGroupI18ns === null) {
            $this->initGroupI18ns();
            $this->collGroupI18nsPartial = true;
        }

        if (!in_array($l, $this->collGroupI18ns->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddGroupI18n($l);
        }

        return $this;
    }

    /**
     * @param GroupI18n $groupI18n The groupI18n object to add.
     */
    protected function doAddGroupI18n($groupI18n)
    {
        $this->collGroupI18ns[]= $groupI18n;
        $groupI18n->setGroup($this);
    }

    /**
     * @param  GroupI18n $groupI18n The groupI18n object to remove.
     * @return ChildGroup The current object (for fluent API support)
     */
    public function removeGroupI18n($groupI18n)
    {
        if ($this->getGroupI18ns()->contains($groupI18n)) {
            $this->collGroupI18ns->remove($this->collGroupI18ns->search($groupI18n));
            if (null === $this->groupI18nsScheduledForDeletion) {
                $this->groupI18nsScheduledForDeletion = clone $this->collGroupI18ns;
                $this->groupI18nsScheduledForDeletion->clear();
            }
            $this->groupI18nsScheduledForDeletion[]= clone $groupI18n;
            $groupI18n->setGroup(null);
        }

        return $this;
    }

    /**
     * Clears out the collAdmins collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addAdmins()
     */
    public function clearAdmins()
    {
        $this->collAdmins = null; // important to set this to NULL since that means it is uninitialized
        $this->collAdminsPartial = null;
    }

    /**
     * Initializes the collAdmins collection.
     *
     * By default this just sets the collAdmins collection to an empty collection (like clearAdmins());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initAdmins()
    {
        $this->collAdmins = new ObjectCollection();
        $this->collAdmins->setModel('\Thelia\Model\Admin');
    }

    /**
     * Gets a collection of ChildAdmin objects related by a many-to-many relationship
     * to the current object by way of the admin_group cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildAdmin[] List of ChildAdmin objects
     */
    public function getAdmins($criteria = null, ConnectionInterface $con = null)
    {
        if (null === $this->collAdmins || null !== $criteria) {
            if ($this->isNew() && null === $this->collAdmins) {
                // return empty collection
                $this->initAdmins();
            } else {
                $collAdmins = ChildAdminQuery::create(null, $criteria)
                    ->filterByGroup($this)
                    ->find($con);
                if (null !== $criteria) {
                    return $collAdmins;
                }
                $this->collAdmins = $collAdmins;
            }
        }

        return $this->collAdmins;
    }

    /**
     * Sets a collection of Admin objects related by a many-to-many relationship
     * to the current object by way of the admin_group cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $admins A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return ChildGroup The current object (for fluent API support)
     */
    public function setAdmins(Collection $admins, ConnectionInterface $con = null)
    {
        $this->clearAdmins();
        $currentAdmins = $this->getAdmins();

        $this->adminsScheduledForDeletion = $currentAdmins->diff($admins);

        foreach ($admins as $admin) {
            if (!$currentAdmins->contains($admin)) {
                $this->doAddAdmin($admin);
            }
        }

        $this->collAdmins = $admins;

        return $this;
    }

    /**
     * Gets the number of ChildAdmin objects related by a many-to-many relationship
     * to the current object by way of the admin_group cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ChildAdmin objects
     */
    public function countAdmins($criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        if (null === $this->collAdmins || null !== $criteria) {
            if ($this->isNew() && null === $this->collAdmins) {
                return 0;
            } else {
                $query = ChildAdminQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByGroup($this)
                    ->count($con);
            }
        } else {
            return count($this->collAdmins);
        }
    }

    /**
     * Associate a ChildAdmin object to this object
     * through the admin_group cross reference table.
     *
     * @param  ChildAdmin $admin The ChildAdminGroup object to relate
     * @return ChildGroup The current object (for fluent API support)
     */
    public function addAdmin(ChildAdmin $admin)
    {
        if ($this->collAdmins === null) {
            $this->initAdmins();
        }

        if (!$this->collAdmins->contains($admin)) { // only add it if the **same** object is not already associated
            $this->doAddAdmin($admin);
            $this->collAdmins[] = $admin;
        }

        return $this;
    }

    /**
     * @param    Admin $admin The admin object to add.
     */
    protected function doAddAdmin($admin)
    {
        $adminGroup = new ChildAdminGroup();
        $adminGroup->setAdmin($admin);
        $this->addAdminGroup($adminGroup);
        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$admin->getGroups()->contains($this)) {
            $foreignCollection   = $admin->getGroups();
            $foreignCollection[] = $this;
        }
    }

    /**
     * Remove a ChildAdmin object to this object
     * through the admin_group cross reference table.
     *
     * @param ChildAdmin $admin The ChildAdminGroup object to relate
     * @return ChildGroup The current object (for fluent API support)
     */
    public function removeAdmin(ChildAdmin $admin)
    {
        if ($this->getAdmins()->contains($admin)) {
            $this->collAdmins->remove($this->collAdmins->search($admin));

            if (null === $this->adminsScheduledForDeletion) {
                $this->adminsScheduledForDeletion = clone $this->collAdmins;
                $this->adminsScheduledForDeletion->clear();
            }

            $this->adminsScheduledForDeletion[] = $admin;
        }

        return $this;
    }

    /**
     * Clears out the collResources collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addResources()
     */
    public function clearResources()
    {
        $this->collResources = null; // important to set this to NULL since that means it is uninitialized
        $this->collResourcesPartial = null;
    }

    /**
     * Initializes the collResources collection.
     *
     * By default this just sets the collResources collection to an empty collection (like clearResources());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initResources()
    {
        $this->collResources = new ObjectCollection();
        $this->collResources->setModel('\Thelia\Model\Resource');
    }

    /**
     * Gets a collection of ChildResource objects related by a many-to-many relationship
     * to the current object by way of the group_resource cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildResource[] List of ChildResource objects
     */
    public function getResources($criteria = null, ConnectionInterface $con = null)
    {
        if (null === $this->collResources || null !== $criteria) {
            if ($this->isNew() && null === $this->collResources) {
                // return empty collection
                $this->initResources();
            } else {
                $collResources = ChildResourceQuery::create(null, $criteria)
                    ->filterByGroup($this)
                    ->find($con);
                if (null !== $criteria) {
                    return $collResources;
                }
                $this->collResources = $collResources;
            }
        }

        return $this->collResources;
    }

    /**
     * Sets a collection of Resource objects related by a many-to-many relationship
     * to the current object by way of the group_resource cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $resources A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return ChildGroup The current object (for fluent API support)
     */
    public function setResources(Collection $resources, ConnectionInterface $con = null)
    {
        $this->clearResources();
        $currentResources = $this->getResources();

        $this->resourcesScheduledForDeletion = $currentResources->diff($resources);

        foreach ($resources as $resource) {
            if (!$currentResources->contains($resource)) {
                $this->doAddResource($resource);
            }
        }

        $this->collResources = $resources;

        return $this;
    }

    /**
     * Gets the number of ChildResource objects related by a many-to-many relationship
     * to the current object by way of the group_resource cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ChildResource objects
     */
    public function countResources($criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        if (null === $this->collResources || null !== $criteria) {
            if ($this->isNew() && null === $this->collResources) {
                return 0;
            } else {
                $query = ChildResourceQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByGroup($this)
                    ->count($con);
            }
        } else {
            return count($this->collResources);
        }
    }

    /**
     * Associate a ChildResource object to this object
     * through the group_resource cross reference table.
     *
     * @param  ChildResource $resource The ChildGroupResource object to relate
     * @return ChildGroup The current object (for fluent API support)
     */
    public function addResource(ChildResource $resource)
    {
        if ($this->collResources === null) {
            $this->initResources();
        }

        if (!$this->collResources->contains($resource)) { // only add it if the **same** object is not already associated
            $this->doAddResource($resource);
            $this->collResources[] = $resource;
        }

        return $this;
    }

    /**
     * @param    Resource $resource The resource object to add.
     */
    protected function doAddResource($resource)
    {
        $groupResource = new ChildGroupResource();
        $groupResource->setResource($resource);
        $this->addGroupResource($groupResource);
        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$resource->getGroups()->contains($this)) {
            $foreignCollection   = $resource->getGroups();
            $foreignCollection[] = $this;
        }
    }

    /**
     * Remove a ChildResource object to this object
     * through the group_resource cross reference table.
     *
     * @param ChildResource $resource The ChildGroupResource object to relate
     * @return ChildGroup The current object (for fluent API support)
     */
    public function removeResource(ChildResource $resource)
    {
        if ($this->getResources()->contains($resource)) {
            $this->collResources->remove($this->collResources->search($resource));

            if (null === $this->resourcesScheduledForDeletion) {
                $this->resourcesScheduledForDeletion = clone $this->collResources;
                $this->resourcesScheduledForDeletion->clear();
            }

            $this->resourcesScheduledForDeletion[] = $resource;
        }

        return $this;
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->id = null;
        $this->code = null;
        $this->created_at = null;
        $this->updated_at = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
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
            if ($this->collAdminGroups) {
                foreach ($this->collAdminGroups as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collGroupResources) {
                foreach ($this->collGroupResources as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collGroupModules) {
                foreach ($this->collGroupModules as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collGroupI18ns) {
                foreach ($this->collGroupI18ns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collAdmins) {
                foreach ($this->collAdmins as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collResources) {
                foreach ($this->collResources as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        // i18n behavior
        $this->currentLocale = 'en_EN';
        $this->currentTranslations = null;

        if ($this->collAdminGroups instanceof Collection) {
            $this->collAdminGroups->clearIterator();
        }
        $this->collAdminGroups = null;
        if ($this->collGroupResources instanceof Collection) {
            $this->collGroupResources->clearIterator();
        }
        $this->collGroupResources = null;
        if ($this->collGroupModules instanceof Collection) {
            $this->collGroupModules->clearIterator();
        }
        $this->collGroupModules = null;
        if ($this->collGroupI18ns instanceof Collection) {
            $this->collGroupI18ns->clearIterator();
        }
        $this->collGroupI18ns = null;
        if ($this->collAdmins instanceof Collection) {
            $this->collAdmins->clearIterator();
        }
        $this->collAdmins = null;
        if ($this->collResources instanceof Collection) {
            $this->collResources->clearIterator();
        }
        $this->collResources = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(GroupTableMap::DEFAULT_STRING_FORMAT);
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     ChildGroup The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[] = GroupTableMap::UPDATED_AT;

        return $this;
    }

    // i18n behavior

    /**
     * Sets the locale for translations
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     *
     * @return    ChildGroup The current object (for fluent API support)
     */
    public function setLocale($locale = 'en_EN')
    {
        $this->currentLocale = $locale;

        return $this;
    }

    /**
     * Gets the locale for translations
     *
     * @return    string $locale Locale to use for the translation, e.g. 'fr_FR'
     */
    public function getLocale()
    {
        return $this->currentLocale;
    }

    /**
     * Returns the current translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildGroupI18n */
    public function getTranslation($locale = 'en_EN', ConnectionInterface $con = null)
    {
        if (!isset($this->currentTranslations[$locale])) {
            if (null !== $this->collGroupI18ns) {
                foreach ($this->collGroupI18ns as $translation) {
                    if ($translation->getLocale() == $locale) {
                        $this->currentTranslations[$locale] = $translation;

                        return $translation;
                    }
                }
            }
            if ($this->isNew()) {
                $translation = new ChildGroupI18n();
                $translation->setLocale($locale);
            } else {
                $translation = ChildGroupI18nQuery::create()
                    ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                    ->findOneOrCreate($con);
                $this->currentTranslations[$locale] = $translation;
            }
            $this->addGroupI18n($translation);
        }

        return $this->currentTranslations[$locale];
    }

    /**
     * Remove the translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return    ChildGroup The current object (for fluent API support)
     */
    public function removeTranslation($locale = 'en_EN', ConnectionInterface $con = null)
    {
        if (!$this->isNew()) {
            ChildGroupI18nQuery::create()
                ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                ->delete($con);
        }
        if (isset($this->currentTranslations[$locale])) {
            unset($this->currentTranslations[$locale]);
        }
        foreach ($this->collGroupI18ns as $key => $translation) {
            if ($translation->getLocale() == $locale) {
                unset($this->collGroupI18ns[$key]);
                break;
            }
        }

        return $this;
    }

    /**
     * Returns the current translation
     *
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildGroupI18n */
    public function getCurrentTranslation(ConnectionInterface $con = null)
    {
        return $this->getTranslation($this->getLocale(), $con);
    }


        /**
         * Get the [title] column value.
         *
         * @return   string
         */
        public function getTitle()
        {
        return $this->getCurrentTranslation()->getTitle();
    }


        /**
         * Set the value of [title] column.
         *
         * @param      string $v new value
         * @return   \Thelia\Model\GroupI18n The current object (for fluent API support)
         */
        public function setTitle($v)
        {    $this->getCurrentTranslation()->setTitle($v);

        return $this;
    }


        /**
         * Get the [description] column value.
         *
         * @return   string
         */
        public function getDescription()
        {
        return $this->getCurrentTranslation()->getDescription();
    }


        /**
         * Set the value of [description] column.
         *
         * @param      string $v new value
         * @return   \Thelia\Model\GroupI18n The current object (for fluent API support)
         */
        public function setDescription($v)
        {    $this->getCurrentTranslation()->setDescription($v);

        return $this;
    }


        /**
         * Get the [chapo] column value.
         *
         * @return   string
         */
        public function getChapo()
        {
        return $this->getCurrentTranslation()->getChapo();
    }


        /**
         * Set the value of [chapo] column.
         *
         * @param      string $v new value
         * @return   \Thelia\Model\GroupI18n The current object (for fluent API support)
         */
        public function setChapo($v)
        {    $this->getCurrentTranslation()->setChapo($v);

        return $this;
    }


        /**
         * Get the [postscriptum] column value.
         *
         * @return   string
         */
        public function getPostscriptum()
        {
        return $this->getCurrentTranslation()->getPostscriptum();
    }


        /**
         * Set the value of [postscriptum] column.
         *
         * @param      string $v new value
         * @return   \Thelia\Model\GroupI18n The current object (for fluent API support)
         */
        public function setPostscriptum($v)
        {    $this->getCurrentTranslation()->setPostscriptum($v);

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
