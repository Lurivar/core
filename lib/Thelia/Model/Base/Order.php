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
use Thelia\Model\CouponOrder as ChildCouponOrder;
use Thelia\Model\CouponOrderQuery as ChildCouponOrderQuery;
use Thelia\Model\Currency as ChildCurrency;
use Thelia\Model\CurrencyQuery as ChildCurrencyQuery;
use Thelia\Model\Customer as ChildCustomer;
use Thelia\Model\CustomerQuery as ChildCustomerQuery;
use Thelia\Model\Order as ChildOrder;
use Thelia\Model\OrderAddress as ChildOrderAddress;
use Thelia\Model\OrderAddressQuery as ChildOrderAddressQuery;
use Thelia\Model\OrderProduct as ChildOrderProduct;
use Thelia\Model\OrderProductQuery as ChildOrderProductQuery;
use Thelia\Model\OrderQuery as ChildOrderQuery;
use Thelia\Model\OrderStatus as ChildOrderStatus;
use Thelia\Model\OrderStatusQuery as ChildOrderStatusQuery;
use Thelia\Model\Map\OrderTableMap;

abstract class Order implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Thelia\\Model\\Map\\OrderTableMap';


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
     * The value for the ref field.
     * @var        string
     */
    protected $ref;

    /**
     * The value for the customer_id field.
     * @var        int
     */
    protected $customer_id;

    /**
     * The value for the address_invoice field.
     * @var        int
     */
    protected $address_invoice;

    /**
     * The value for the address_delivery field.
     * @var        int
     */
    protected $address_delivery;

    /**
     * The value for the invoice_date field.
     * @var        string
     */
    protected $invoice_date;

    /**
     * The value for the currency_id field.
     * @var        int
     */
    protected $currency_id;

    /**
     * The value for the currency_rate field.
     * @var        double
     */
    protected $currency_rate;

    /**
     * The value for the transaction field.
     * @var        string
     */
    protected $transaction;

    /**
     * The value for the delivery_num field.
     * @var        string
     */
    protected $delivery_num;

    /**
     * The value for the invoice field.
     * @var        string
     */
    protected $invoice;

    /**
     * The value for the postage field.
     * @var        double
     */
    protected $postage;

    /**
     * The value for the payment field.
     * @var        string
     */
    protected $payment;

    /**
     * The value for the carrier field.
     * @var        string
     */
    protected $carrier;

    /**
     * The value for the status_id field.
     * @var        int
     */
    protected $status_id;

    /**
     * The value for the lang field.
     * @var        string
     */
    protected $lang;

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
     * @var        Currency
     */
    protected $aCurrency;

    /**
     * @var        Customer
     */
    protected $aCustomer;

    /**
     * @var        OrderAddress
     */
    protected $aOrderAddressRelatedByAddressInvoice;

    /**
     * @var        OrderAddress
     */
    protected $aOrderAddressRelatedByAddressDelivery;

    /**
     * @var        OrderStatus
     */
    protected $aOrderStatus;

    /**
     * @var        ObjectCollection|ChildOrderProduct[] Collection to store aggregation of ChildOrderProduct objects.
     */
    protected $collOrderProducts;
    protected $collOrderProductsPartial;

    /**
     * @var        ObjectCollection|ChildCouponOrder[] Collection to store aggregation of ChildCouponOrder objects.
     */
    protected $collCouponOrders;
    protected $collCouponOrdersPartial;

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
    protected $orderProductsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $couponOrdersScheduledForDeletion = null;

    /**
     * Initializes internal state of Thelia\Model\Base\Order object.
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
     * Compares this with another <code>Order</code> instance.  If
     * <code>obj</code> is an instance of <code>Order</code>, delegates to
     * <code>equals(Order)</code>.  Otherwise, returns <code>false</code>.
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
     * @return Order The current object, for fluid interface
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
     * @return Order The current object, for fluid interface
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
     * Get the [ref] column value.
     *
     * @return   string
     */
    public function getRef()
    {

        return $this->ref;
    }

    /**
     * Get the [customer_id] column value.
     *
     * @return   int
     */
    public function getCustomerId()
    {

        return $this->customer_id;
    }

    /**
     * Get the [address_invoice] column value.
     *
     * @return   int
     */
    public function getAddressInvoice()
    {

        return $this->address_invoice;
    }

    /**
     * Get the [address_delivery] column value.
     *
     * @return   int
     */
    public function getAddressDelivery()
    {

        return $this->address_delivery;
    }

    /**
     * Get the [optionally formatted] temporal [invoice_date] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getInvoiceDate($format = NULL)
    {
        if ($format === null) {
            return $this->invoice_date;
        } else {
            return $this->invoice_date !== null ? $this->invoice_date->format($format) : null;
        }
    }

    /**
     * Get the [currency_id] column value.
     *
     * @return   int
     */
    public function getCurrencyId()
    {

        return $this->currency_id;
    }

    /**
     * Get the [currency_rate] column value.
     *
     * @return   double
     */
    public function getCurrencyRate()
    {

        return $this->currency_rate;
    }

    /**
     * Get the [transaction] column value.
     *
     * @return   string
     */
    public function getTransaction()
    {

        return $this->transaction;
    }

    /**
     * Get the [delivery_num] column value.
     *
     * @return   string
     */
    public function getDeliveryNum()
    {

        return $this->delivery_num;
    }

    /**
     * Get the [invoice] column value.
     *
     * @return   string
     */
    public function getInvoice()
    {

        return $this->invoice;
    }

    /**
     * Get the [postage] column value.
     *
     * @return   double
     */
    public function getPostage()
    {

        return $this->postage;
    }

    /**
     * Get the [payment] column value.
     *
     * @return   string
     */
    public function getPayment()
    {

        return $this->payment;
    }

    /**
     * Get the [carrier] column value.
     *
     * @return   string
     */
    public function getCarrier()
    {

        return $this->carrier;
    }

    /**
     * Get the [status_id] column value.
     *
     * @return   int
     */
    public function getStatusId()
    {

        return $this->status_id;
    }

    /**
     * Get the [lang] column value.
     *
     * @return   string
     */
    public function getLang()
    {

        return $this->lang;
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
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[] = OrderTableMap::ID;
        }


        return $this;
    } // setId()

    /**
     * Set the value of [ref] column.
     *
     * @param      string $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setRef($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->ref !== $v) {
            $this->ref = $v;
            $this->modifiedColumns[] = OrderTableMap::REF;
        }


        return $this;
    } // setRef()

    /**
     * Set the value of [customer_id] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setCustomerId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->customer_id !== $v) {
            $this->customer_id = $v;
            $this->modifiedColumns[] = OrderTableMap::CUSTOMER_ID;
        }

        if ($this->aCustomer !== null && $this->aCustomer->getId() !== $v) {
            $this->aCustomer = null;
        }


        return $this;
    } // setCustomerId()

    /**
     * Set the value of [address_invoice] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setAddressInvoice($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->address_invoice !== $v) {
            $this->address_invoice = $v;
            $this->modifiedColumns[] = OrderTableMap::ADDRESS_INVOICE;
        }

        if ($this->aOrderAddressRelatedByAddressInvoice !== null && $this->aOrderAddressRelatedByAddressInvoice->getId() !== $v) {
            $this->aOrderAddressRelatedByAddressInvoice = null;
        }


        return $this;
    } // setAddressInvoice()

    /**
     * Set the value of [address_delivery] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setAddressDelivery($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->address_delivery !== $v) {
            $this->address_delivery = $v;
            $this->modifiedColumns[] = OrderTableMap::ADDRESS_DELIVERY;
        }

        if ($this->aOrderAddressRelatedByAddressDelivery !== null && $this->aOrderAddressRelatedByAddressDelivery->getId() !== $v) {
            $this->aOrderAddressRelatedByAddressDelivery = null;
        }


        return $this;
    } // setAddressDelivery()

    /**
     * Sets the value of [invoice_date] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setInvoiceDate($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->invoice_date !== null || $dt !== null) {
            if ($dt !== $this->invoice_date) {
                $this->invoice_date = $dt;
                $this->modifiedColumns[] = OrderTableMap::INVOICE_DATE;
            }
        } // if either are not null


        return $this;
    } // setInvoiceDate()

    /**
     * Set the value of [currency_id] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setCurrencyId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->currency_id !== $v) {
            $this->currency_id = $v;
            $this->modifiedColumns[] = OrderTableMap::CURRENCY_ID;
        }

        if ($this->aCurrency !== null && $this->aCurrency->getId() !== $v) {
            $this->aCurrency = null;
        }


        return $this;
    } // setCurrencyId()

    /**
     * Set the value of [currency_rate] column.
     *
     * @param      double $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setCurrencyRate($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->currency_rate !== $v) {
            $this->currency_rate = $v;
            $this->modifiedColumns[] = OrderTableMap::CURRENCY_RATE;
        }


        return $this;
    } // setCurrencyRate()

    /**
     * Set the value of [transaction] column.
     *
     * @param      string $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setTransaction($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->transaction !== $v) {
            $this->transaction = $v;
            $this->modifiedColumns[] = OrderTableMap::TRANSACTION;
        }


        return $this;
    } // setTransaction()

    /**
     * Set the value of [delivery_num] column.
     *
     * @param      string $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setDeliveryNum($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->delivery_num !== $v) {
            $this->delivery_num = $v;
            $this->modifiedColumns[] = OrderTableMap::DELIVERY_NUM;
        }


        return $this;
    } // setDeliveryNum()

    /**
     * Set the value of [invoice] column.
     *
     * @param      string $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setInvoice($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->invoice !== $v) {
            $this->invoice = $v;
            $this->modifiedColumns[] = OrderTableMap::INVOICE;
        }


        return $this;
    } // setInvoice()

    /**
     * Set the value of [postage] column.
     *
     * @param      double $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setPostage($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->postage !== $v) {
            $this->postage = $v;
            $this->modifiedColumns[] = OrderTableMap::POSTAGE;
        }


        return $this;
    } // setPostage()

    /**
     * Set the value of [payment] column.
     *
     * @param      string $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setPayment($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->payment !== $v) {
            $this->payment = $v;
            $this->modifiedColumns[] = OrderTableMap::PAYMENT;
        }


        return $this;
    } // setPayment()

    /**
     * Set the value of [carrier] column.
     *
     * @param      string $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setCarrier($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->carrier !== $v) {
            $this->carrier = $v;
            $this->modifiedColumns[] = OrderTableMap::CARRIER;
        }


        return $this;
    } // setCarrier()

    /**
     * Set the value of [status_id] column.
     *
     * @param      int $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setStatusId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->status_id !== $v) {
            $this->status_id = $v;
            $this->modifiedColumns[] = OrderTableMap::STATUS_ID;
        }

        if ($this->aOrderStatus !== null && $this->aOrderStatus->getId() !== $v) {
            $this->aOrderStatus = null;
        }


        return $this;
    } // setStatusId()

    /**
     * Set the value of [lang] column.
     *
     * @param      string $v new value
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setLang($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->lang !== $v) {
            $this->lang = $v;
            $this->modifiedColumns[] = OrderTableMap::LANG;
        }


        return $this;
    } // setLang()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->created_at !== null || $dt !== null) {
            if ($dt !== $this->created_at) {
                $this->created_at = $dt;
                $this->modifiedColumns[] = OrderTableMap::CREATED_AT;
            }
        } // if either are not null


        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [updated_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->updated_at !== null || $dt !== null) {
            if ($dt !== $this->updated_at) {
                $this->updated_at = $dt;
                $this->modifiedColumns[] = OrderTableMap::UPDATED_AT;
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


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : OrderTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : OrderTableMap::translateFieldName('Ref', TableMap::TYPE_PHPNAME, $indexType)];
            $this->ref = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : OrderTableMap::translateFieldName('CustomerId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->customer_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : OrderTableMap::translateFieldName('AddressInvoice', TableMap::TYPE_PHPNAME, $indexType)];
            $this->address_invoice = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : OrderTableMap::translateFieldName('AddressDelivery', TableMap::TYPE_PHPNAME, $indexType)];
            $this->address_delivery = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : OrderTableMap::translateFieldName('InvoiceDate', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00') {
                $col = null;
            }
            $this->invoice_date = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : OrderTableMap::translateFieldName('CurrencyId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->currency_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : OrderTableMap::translateFieldName('CurrencyRate', TableMap::TYPE_PHPNAME, $indexType)];
            $this->currency_rate = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : OrderTableMap::translateFieldName('Transaction', TableMap::TYPE_PHPNAME, $indexType)];
            $this->transaction = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : OrderTableMap::translateFieldName('DeliveryNum', TableMap::TYPE_PHPNAME, $indexType)];
            $this->delivery_num = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : OrderTableMap::translateFieldName('Invoice', TableMap::TYPE_PHPNAME, $indexType)];
            $this->invoice = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 11 + $startcol : OrderTableMap::translateFieldName('Postage', TableMap::TYPE_PHPNAME, $indexType)];
            $this->postage = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 12 + $startcol : OrderTableMap::translateFieldName('Payment', TableMap::TYPE_PHPNAME, $indexType)];
            $this->payment = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 13 + $startcol : OrderTableMap::translateFieldName('Carrier', TableMap::TYPE_PHPNAME, $indexType)];
            $this->carrier = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 14 + $startcol : OrderTableMap::translateFieldName('StatusId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->status_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 15 + $startcol : OrderTableMap::translateFieldName('Lang', TableMap::TYPE_PHPNAME, $indexType)];
            $this->lang = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 16 + $startcol : OrderTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 17 + $startcol : OrderTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 18; // 18 = OrderTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \Thelia\Model\Order object", 0, $e);
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
        if ($this->aCustomer !== null && $this->customer_id !== $this->aCustomer->getId()) {
            $this->aCustomer = null;
        }
        if ($this->aOrderAddressRelatedByAddressInvoice !== null && $this->address_invoice !== $this->aOrderAddressRelatedByAddressInvoice->getId()) {
            $this->aOrderAddressRelatedByAddressInvoice = null;
        }
        if ($this->aOrderAddressRelatedByAddressDelivery !== null && $this->address_delivery !== $this->aOrderAddressRelatedByAddressDelivery->getId()) {
            $this->aOrderAddressRelatedByAddressDelivery = null;
        }
        if ($this->aCurrency !== null && $this->currency_id !== $this->aCurrency->getId()) {
            $this->aCurrency = null;
        }
        if ($this->aOrderStatus !== null && $this->status_id !== $this->aOrderStatus->getId()) {
            $this->aOrderStatus = null;
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
            $con = Propel::getServiceContainer()->getReadConnection(OrderTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildOrderQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aCurrency = null;
            $this->aCustomer = null;
            $this->aOrderAddressRelatedByAddressInvoice = null;
            $this->aOrderAddressRelatedByAddressDelivery = null;
            $this->aOrderStatus = null;
            $this->collOrderProducts = null;

            $this->collCouponOrders = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Order::setDeleted()
     * @see Order::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(OrderTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildOrderQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(OrderTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior
                if (!$this->isColumnModified(OrderTableMap::CREATED_AT)) {
                    $this->setCreatedAt(time());
                }
                if (!$this->isColumnModified(OrderTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(OrderTableMap::UPDATED_AT)) {
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
                OrderTableMap::addInstanceToPool($this);
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

            if ($this->aCurrency !== null) {
                if ($this->aCurrency->isModified() || $this->aCurrency->isNew()) {
                    $affectedRows += $this->aCurrency->save($con);
                }
                $this->setCurrency($this->aCurrency);
            }

            if ($this->aCustomer !== null) {
                if ($this->aCustomer->isModified() || $this->aCustomer->isNew()) {
                    $affectedRows += $this->aCustomer->save($con);
                }
                $this->setCustomer($this->aCustomer);
            }

            if ($this->aOrderAddressRelatedByAddressInvoice !== null) {
                if ($this->aOrderAddressRelatedByAddressInvoice->isModified() || $this->aOrderAddressRelatedByAddressInvoice->isNew()) {
                    $affectedRows += $this->aOrderAddressRelatedByAddressInvoice->save($con);
                }
                $this->setOrderAddressRelatedByAddressInvoice($this->aOrderAddressRelatedByAddressInvoice);
            }

            if ($this->aOrderAddressRelatedByAddressDelivery !== null) {
                if ($this->aOrderAddressRelatedByAddressDelivery->isModified() || $this->aOrderAddressRelatedByAddressDelivery->isNew()) {
                    $affectedRows += $this->aOrderAddressRelatedByAddressDelivery->save($con);
                }
                $this->setOrderAddressRelatedByAddressDelivery($this->aOrderAddressRelatedByAddressDelivery);
            }

            if ($this->aOrderStatus !== null) {
                if ($this->aOrderStatus->isModified() || $this->aOrderStatus->isNew()) {
                    $affectedRows += $this->aOrderStatus->save($con);
                }
                $this->setOrderStatus($this->aOrderStatus);
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

            if ($this->orderProductsScheduledForDeletion !== null) {
                if (!$this->orderProductsScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\OrderProductQuery::create()
                        ->filterByPrimaryKeys($this->orderProductsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->orderProductsScheduledForDeletion = null;
                }
            }

                if ($this->collOrderProducts !== null) {
            foreach ($this->collOrderProducts as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->couponOrdersScheduledForDeletion !== null) {
                if (!$this->couponOrdersScheduledForDeletion->isEmpty()) {
                    \Thelia\Model\CouponOrderQuery::create()
                        ->filterByPrimaryKeys($this->couponOrdersScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->couponOrdersScheduledForDeletion = null;
                }
            }

                if ($this->collCouponOrders !== null) {
            foreach ($this->collCouponOrders as $referrerFK) {
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

        $this->modifiedColumns[] = OrderTableMap::ID;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . OrderTableMap::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(OrderTableMap::ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(OrderTableMap::REF)) {
            $modifiedColumns[':p' . $index++]  = 'REF';
        }
        if ($this->isColumnModified(OrderTableMap::CUSTOMER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'CUSTOMER_ID';
        }
        if ($this->isColumnModified(OrderTableMap::ADDRESS_INVOICE)) {
            $modifiedColumns[':p' . $index++]  = 'ADDRESS_INVOICE';
        }
        if ($this->isColumnModified(OrderTableMap::ADDRESS_DELIVERY)) {
            $modifiedColumns[':p' . $index++]  = 'ADDRESS_DELIVERY';
        }
        if ($this->isColumnModified(OrderTableMap::INVOICE_DATE)) {
            $modifiedColumns[':p' . $index++]  = 'INVOICE_DATE';
        }
        if ($this->isColumnModified(OrderTableMap::CURRENCY_ID)) {
            $modifiedColumns[':p' . $index++]  = 'CURRENCY_ID';
        }
        if ($this->isColumnModified(OrderTableMap::CURRENCY_RATE)) {
            $modifiedColumns[':p' . $index++]  = 'CURRENCY_RATE';
        }
        if ($this->isColumnModified(OrderTableMap::TRANSACTION)) {
            $modifiedColumns[':p' . $index++]  = 'TRANSACTION';
        }
        if ($this->isColumnModified(OrderTableMap::DELIVERY_NUM)) {
            $modifiedColumns[':p' . $index++]  = 'DELIVERY_NUM';
        }
        if ($this->isColumnModified(OrderTableMap::INVOICE)) {
            $modifiedColumns[':p' . $index++]  = 'INVOICE';
        }
        if ($this->isColumnModified(OrderTableMap::POSTAGE)) {
            $modifiedColumns[':p' . $index++]  = 'POSTAGE';
        }
        if ($this->isColumnModified(OrderTableMap::PAYMENT)) {
            $modifiedColumns[':p' . $index++]  = 'PAYMENT';
        }
        if ($this->isColumnModified(OrderTableMap::CARRIER)) {
            $modifiedColumns[':p' . $index++]  = 'CARRIER';
        }
        if ($this->isColumnModified(OrderTableMap::STATUS_ID)) {
            $modifiedColumns[':p' . $index++]  = 'STATUS_ID';
        }
        if ($this->isColumnModified(OrderTableMap::LANG)) {
            $modifiedColumns[':p' . $index++]  = 'LANG';
        }
        if ($this->isColumnModified(OrderTableMap::CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED_AT';
        }
        if ($this->isColumnModified(OrderTableMap::UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'UPDATED_AT';
        }

        $sql = sprintf(
            'INSERT INTO order (%s) VALUES (%s)',
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
                    case 'REF':
                        $stmt->bindValue($identifier, $this->ref, PDO::PARAM_STR);
                        break;
                    case 'CUSTOMER_ID':
                        $stmt->bindValue($identifier, $this->customer_id, PDO::PARAM_INT);
                        break;
                    case 'ADDRESS_INVOICE':
                        $stmt->bindValue($identifier, $this->address_invoice, PDO::PARAM_INT);
                        break;
                    case 'ADDRESS_DELIVERY':
                        $stmt->bindValue($identifier, $this->address_delivery, PDO::PARAM_INT);
                        break;
                    case 'INVOICE_DATE':
                        $stmt->bindValue($identifier, $this->invoice_date ? $this->invoice_date->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'CURRENCY_ID':
                        $stmt->bindValue($identifier, $this->currency_id, PDO::PARAM_INT);
                        break;
                    case 'CURRENCY_RATE':
                        $stmt->bindValue($identifier, $this->currency_rate, PDO::PARAM_STR);
                        break;
                    case 'TRANSACTION':
                        $stmt->bindValue($identifier, $this->transaction, PDO::PARAM_STR);
                        break;
                    case 'DELIVERY_NUM':
                        $stmt->bindValue($identifier, $this->delivery_num, PDO::PARAM_STR);
                        break;
                    case 'INVOICE':
                        $stmt->bindValue($identifier, $this->invoice, PDO::PARAM_STR);
                        break;
                    case 'POSTAGE':
                        $stmt->bindValue($identifier, $this->postage, PDO::PARAM_STR);
                        break;
                    case 'PAYMENT':
                        $stmt->bindValue($identifier, $this->payment, PDO::PARAM_STR);
                        break;
                    case 'CARRIER':
                        $stmt->bindValue($identifier, $this->carrier, PDO::PARAM_STR);
                        break;
                    case 'STATUS_ID':
                        $stmt->bindValue($identifier, $this->status_id, PDO::PARAM_INT);
                        break;
                    case 'LANG':
                        $stmt->bindValue($identifier, $this->lang, PDO::PARAM_STR);
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
        $pos = OrderTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getRef();
                break;
            case 2:
                return $this->getCustomerId();
                break;
            case 3:
                return $this->getAddressInvoice();
                break;
            case 4:
                return $this->getAddressDelivery();
                break;
            case 5:
                return $this->getInvoiceDate();
                break;
            case 6:
                return $this->getCurrencyId();
                break;
            case 7:
                return $this->getCurrencyRate();
                break;
            case 8:
                return $this->getTransaction();
                break;
            case 9:
                return $this->getDeliveryNum();
                break;
            case 10:
                return $this->getInvoice();
                break;
            case 11:
                return $this->getPostage();
                break;
            case 12:
                return $this->getPayment();
                break;
            case 13:
                return $this->getCarrier();
                break;
            case 14:
                return $this->getStatusId();
                break;
            case 15:
                return $this->getLang();
                break;
            case 16:
                return $this->getCreatedAt();
                break;
            case 17:
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
        if (isset($alreadyDumpedObjects['Order'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Order'][$this->getPrimaryKey()] = true;
        $keys = OrderTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getRef(),
            $keys[2] => $this->getCustomerId(),
            $keys[3] => $this->getAddressInvoice(),
            $keys[4] => $this->getAddressDelivery(),
            $keys[5] => $this->getInvoiceDate(),
            $keys[6] => $this->getCurrencyId(),
            $keys[7] => $this->getCurrencyRate(),
            $keys[8] => $this->getTransaction(),
            $keys[9] => $this->getDeliveryNum(),
            $keys[10] => $this->getInvoice(),
            $keys[11] => $this->getPostage(),
            $keys[12] => $this->getPayment(),
            $keys[13] => $this->getCarrier(),
            $keys[14] => $this->getStatusId(),
            $keys[15] => $this->getLang(),
            $keys[16] => $this->getCreatedAt(),
            $keys[17] => $this->getUpdatedAt(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach($virtualColumns as $key => $virtualColumn)
        {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aCurrency) {
                $result['Currency'] = $this->aCurrency->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aCustomer) {
                $result['Customer'] = $this->aCustomer->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aOrderAddressRelatedByAddressInvoice) {
                $result['OrderAddressRelatedByAddressInvoice'] = $this->aOrderAddressRelatedByAddressInvoice->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aOrderAddressRelatedByAddressDelivery) {
                $result['OrderAddressRelatedByAddressDelivery'] = $this->aOrderAddressRelatedByAddressDelivery->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aOrderStatus) {
                $result['OrderStatus'] = $this->aOrderStatus->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collOrderProducts) {
                $result['OrderProducts'] = $this->collOrderProducts->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collCouponOrders) {
                $result['CouponOrders'] = $this->collCouponOrders->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
        $pos = OrderTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

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
                $this->setRef($value);
                break;
            case 2:
                $this->setCustomerId($value);
                break;
            case 3:
                $this->setAddressInvoice($value);
                break;
            case 4:
                $this->setAddressDelivery($value);
                break;
            case 5:
                $this->setInvoiceDate($value);
                break;
            case 6:
                $this->setCurrencyId($value);
                break;
            case 7:
                $this->setCurrencyRate($value);
                break;
            case 8:
                $this->setTransaction($value);
                break;
            case 9:
                $this->setDeliveryNum($value);
                break;
            case 10:
                $this->setInvoice($value);
                break;
            case 11:
                $this->setPostage($value);
                break;
            case 12:
                $this->setPayment($value);
                break;
            case 13:
                $this->setCarrier($value);
                break;
            case 14:
                $this->setStatusId($value);
                break;
            case 15:
                $this->setLang($value);
                break;
            case 16:
                $this->setCreatedAt($value);
                break;
            case 17:
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
        $keys = OrderTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setRef($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setCustomerId($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setAddressInvoice($arr[$keys[3]]);
        if (array_key_exists($keys[4], $arr)) $this->setAddressDelivery($arr[$keys[4]]);
        if (array_key_exists($keys[5], $arr)) $this->setInvoiceDate($arr[$keys[5]]);
        if (array_key_exists($keys[6], $arr)) $this->setCurrencyId($arr[$keys[6]]);
        if (array_key_exists($keys[7], $arr)) $this->setCurrencyRate($arr[$keys[7]]);
        if (array_key_exists($keys[8], $arr)) $this->setTransaction($arr[$keys[8]]);
        if (array_key_exists($keys[9], $arr)) $this->setDeliveryNum($arr[$keys[9]]);
        if (array_key_exists($keys[10], $arr)) $this->setInvoice($arr[$keys[10]]);
        if (array_key_exists($keys[11], $arr)) $this->setPostage($arr[$keys[11]]);
        if (array_key_exists($keys[12], $arr)) $this->setPayment($arr[$keys[12]]);
        if (array_key_exists($keys[13], $arr)) $this->setCarrier($arr[$keys[13]]);
        if (array_key_exists($keys[14], $arr)) $this->setStatusId($arr[$keys[14]]);
        if (array_key_exists($keys[15], $arr)) $this->setLang($arr[$keys[15]]);
        if (array_key_exists($keys[16], $arr)) $this->setCreatedAt($arr[$keys[16]]);
        if (array_key_exists($keys[17], $arr)) $this->setUpdatedAt($arr[$keys[17]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(OrderTableMap::DATABASE_NAME);

        if ($this->isColumnModified(OrderTableMap::ID)) $criteria->add(OrderTableMap::ID, $this->id);
        if ($this->isColumnModified(OrderTableMap::REF)) $criteria->add(OrderTableMap::REF, $this->ref);
        if ($this->isColumnModified(OrderTableMap::CUSTOMER_ID)) $criteria->add(OrderTableMap::CUSTOMER_ID, $this->customer_id);
        if ($this->isColumnModified(OrderTableMap::ADDRESS_INVOICE)) $criteria->add(OrderTableMap::ADDRESS_INVOICE, $this->address_invoice);
        if ($this->isColumnModified(OrderTableMap::ADDRESS_DELIVERY)) $criteria->add(OrderTableMap::ADDRESS_DELIVERY, $this->address_delivery);
        if ($this->isColumnModified(OrderTableMap::INVOICE_DATE)) $criteria->add(OrderTableMap::INVOICE_DATE, $this->invoice_date);
        if ($this->isColumnModified(OrderTableMap::CURRENCY_ID)) $criteria->add(OrderTableMap::CURRENCY_ID, $this->currency_id);
        if ($this->isColumnModified(OrderTableMap::CURRENCY_RATE)) $criteria->add(OrderTableMap::CURRENCY_RATE, $this->currency_rate);
        if ($this->isColumnModified(OrderTableMap::TRANSACTION)) $criteria->add(OrderTableMap::TRANSACTION, $this->transaction);
        if ($this->isColumnModified(OrderTableMap::DELIVERY_NUM)) $criteria->add(OrderTableMap::DELIVERY_NUM, $this->delivery_num);
        if ($this->isColumnModified(OrderTableMap::INVOICE)) $criteria->add(OrderTableMap::INVOICE, $this->invoice);
        if ($this->isColumnModified(OrderTableMap::POSTAGE)) $criteria->add(OrderTableMap::POSTAGE, $this->postage);
        if ($this->isColumnModified(OrderTableMap::PAYMENT)) $criteria->add(OrderTableMap::PAYMENT, $this->payment);
        if ($this->isColumnModified(OrderTableMap::CARRIER)) $criteria->add(OrderTableMap::CARRIER, $this->carrier);
        if ($this->isColumnModified(OrderTableMap::STATUS_ID)) $criteria->add(OrderTableMap::STATUS_ID, $this->status_id);
        if ($this->isColumnModified(OrderTableMap::LANG)) $criteria->add(OrderTableMap::LANG, $this->lang);
        if ($this->isColumnModified(OrderTableMap::CREATED_AT)) $criteria->add(OrderTableMap::CREATED_AT, $this->created_at);
        if ($this->isColumnModified(OrderTableMap::UPDATED_AT)) $criteria->add(OrderTableMap::UPDATED_AT, $this->updated_at);

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
        $criteria = new Criteria(OrderTableMap::DATABASE_NAME);
        $criteria->add(OrderTableMap::ID, $this->id);

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
     * @param      object $copyObj An object of \Thelia\Model\Order (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setRef($this->getRef());
        $copyObj->setCustomerId($this->getCustomerId());
        $copyObj->setAddressInvoice($this->getAddressInvoice());
        $copyObj->setAddressDelivery($this->getAddressDelivery());
        $copyObj->setInvoiceDate($this->getInvoiceDate());
        $copyObj->setCurrencyId($this->getCurrencyId());
        $copyObj->setCurrencyRate($this->getCurrencyRate());
        $copyObj->setTransaction($this->getTransaction());
        $copyObj->setDeliveryNum($this->getDeliveryNum());
        $copyObj->setInvoice($this->getInvoice());
        $copyObj->setPostage($this->getPostage());
        $copyObj->setPayment($this->getPayment());
        $copyObj->setCarrier($this->getCarrier());
        $copyObj->setStatusId($this->getStatusId());
        $copyObj->setLang($this->getLang());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getOrderProducts() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addOrderProduct($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getCouponOrders() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addCouponOrder($relObj->copy($deepCopy));
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
     * @return                 \Thelia\Model\Order Clone of current object.
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
     * Declares an association between this object and a ChildCurrency object.
     *
     * @param                  ChildCurrency $v
     * @return                 \Thelia\Model\Order The current object (for fluent API support)
     * @throws PropelException
     */
    public function setCurrency(ChildCurrency $v = null)
    {
        if ($v === null) {
            $this->setCurrencyId(NULL);
        } else {
            $this->setCurrencyId($v->getId());
        }

        $this->aCurrency = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildCurrency object, it will not be re-added.
        if ($v !== null) {
            $v->addOrder($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildCurrency object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildCurrency The associated ChildCurrency object.
     * @throws PropelException
     */
    public function getCurrency(ConnectionInterface $con = null)
    {
        if ($this->aCurrency === null && ($this->currency_id !== null)) {
            $this->aCurrency = ChildCurrencyQuery::create()->findPk($this->currency_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aCurrency->addOrders($this);
             */
        }

        return $this->aCurrency;
    }

    /**
     * Declares an association between this object and a ChildCustomer object.
     *
     * @param                  ChildCustomer $v
     * @return                 \Thelia\Model\Order The current object (for fluent API support)
     * @throws PropelException
     */
    public function setCustomer(ChildCustomer $v = null)
    {
        if ($v === null) {
            $this->setCustomerId(NULL);
        } else {
            $this->setCustomerId($v->getId());
        }

        $this->aCustomer = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildCustomer object, it will not be re-added.
        if ($v !== null) {
            $v->addOrder($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildCustomer object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildCustomer The associated ChildCustomer object.
     * @throws PropelException
     */
    public function getCustomer(ConnectionInterface $con = null)
    {
        if ($this->aCustomer === null && ($this->customer_id !== null)) {
            $this->aCustomer = ChildCustomerQuery::create()->findPk($this->customer_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aCustomer->addOrders($this);
             */
        }

        return $this->aCustomer;
    }

    /**
     * Declares an association between this object and a ChildOrderAddress object.
     *
     * @param                  ChildOrderAddress $v
     * @return                 \Thelia\Model\Order The current object (for fluent API support)
     * @throws PropelException
     */
    public function setOrderAddressRelatedByAddressInvoice(ChildOrderAddress $v = null)
    {
        if ($v === null) {
            $this->setAddressInvoice(NULL);
        } else {
            $this->setAddressInvoice($v->getId());
        }

        $this->aOrderAddressRelatedByAddressInvoice = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildOrderAddress object, it will not be re-added.
        if ($v !== null) {
            $v->addOrderRelatedByAddressInvoice($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildOrderAddress object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildOrderAddress The associated ChildOrderAddress object.
     * @throws PropelException
     */
    public function getOrderAddressRelatedByAddressInvoice(ConnectionInterface $con = null)
    {
        if ($this->aOrderAddressRelatedByAddressInvoice === null && ($this->address_invoice !== null)) {
            $this->aOrderAddressRelatedByAddressInvoice = ChildOrderAddressQuery::create()->findPk($this->address_invoice, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aOrderAddressRelatedByAddressInvoice->addOrdersRelatedByAddressInvoice($this);
             */
        }

        return $this->aOrderAddressRelatedByAddressInvoice;
    }

    /**
     * Declares an association between this object and a ChildOrderAddress object.
     *
     * @param                  ChildOrderAddress $v
     * @return                 \Thelia\Model\Order The current object (for fluent API support)
     * @throws PropelException
     */
    public function setOrderAddressRelatedByAddressDelivery(ChildOrderAddress $v = null)
    {
        if ($v === null) {
            $this->setAddressDelivery(NULL);
        } else {
            $this->setAddressDelivery($v->getId());
        }

        $this->aOrderAddressRelatedByAddressDelivery = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildOrderAddress object, it will not be re-added.
        if ($v !== null) {
            $v->addOrderRelatedByAddressDelivery($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildOrderAddress object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildOrderAddress The associated ChildOrderAddress object.
     * @throws PropelException
     */
    public function getOrderAddressRelatedByAddressDelivery(ConnectionInterface $con = null)
    {
        if ($this->aOrderAddressRelatedByAddressDelivery === null && ($this->address_delivery !== null)) {
            $this->aOrderAddressRelatedByAddressDelivery = ChildOrderAddressQuery::create()->findPk($this->address_delivery, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aOrderAddressRelatedByAddressDelivery->addOrdersRelatedByAddressDelivery($this);
             */
        }

        return $this->aOrderAddressRelatedByAddressDelivery;
    }

    /**
     * Declares an association between this object and a ChildOrderStatus object.
     *
     * @param                  ChildOrderStatus $v
     * @return                 \Thelia\Model\Order The current object (for fluent API support)
     * @throws PropelException
     */
    public function setOrderStatus(ChildOrderStatus $v = null)
    {
        if ($v === null) {
            $this->setStatusId(NULL);
        } else {
            $this->setStatusId($v->getId());
        }

        $this->aOrderStatus = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildOrderStatus object, it will not be re-added.
        if ($v !== null) {
            $v->addOrder($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildOrderStatus object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildOrderStatus The associated ChildOrderStatus object.
     * @throws PropelException
     */
    public function getOrderStatus(ConnectionInterface $con = null)
    {
        if ($this->aOrderStatus === null && ($this->status_id !== null)) {
            $this->aOrderStatus = ChildOrderStatusQuery::create()->findPk($this->status_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aOrderStatus->addOrders($this);
             */
        }

        return $this->aOrderStatus;
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
        if ('OrderProduct' == $relationName) {
            return $this->initOrderProducts();
        }
        if ('CouponOrder' == $relationName) {
            return $this->initCouponOrders();
        }
    }

    /**
     * Clears out the collOrderProducts collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addOrderProducts()
     */
    public function clearOrderProducts()
    {
        $this->collOrderProducts = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collOrderProducts collection loaded partially.
     */
    public function resetPartialOrderProducts($v = true)
    {
        $this->collOrderProductsPartial = $v;
    }

    /**
     * Initializes the collOrderProducts collection.
     *
     * By default this just sets the collOrderProducts collection to an empty array (like clearcollOrderProducts());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initOrderProducts($overrideExisting = true)
    {
        if (null !== $this->collOrderProducts && !$overrideExisting) {
            return;
        }
        $this->collOrderProducts = new ObjectCollection();
        $this->collOrderProducts->setModel('\Thelia\Model\OrderProduct');
    }

    /**
     * Gets an array of ChildOrderProduct objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildOrder is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildOrderProduct[] List of ChildOrderProduct objects
     * @throws PropelException
     */
    public function getOrderProducts($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collOrderProductsPartial && !$this->isNew();
        if (null === $this->collOrderProducts || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collOrderProducts) {
                // return empty collection
                $this->initOrderProducts();
            } else {
                $collOrderProducts = ChildOrderProductQuery::create(null, $criteria)
                    ->filterByOrder($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collOrderProductsPartial && count($collOrderProducts)) {
                        $this->initOrderProducts(false);

                        foreach ($collOrderProducts as $obj) {
                            if (false == $this->collOrderProducts->contains($obj)) {
                                $this->collOrderProducts->append($obj);
                            }
                        }

                        $this->collOrderProductsPartial = true;
                    }

                    $collOrderProducts->getInternalIterator()->rewind();

                    return $collOrderProducts;
                }

                if ($partial && $this->collOrderProducts) {
                    foreach ($this->collOrderProducts as $obj) {
                        if ($obj->isNew()) {
                            $collOrderProducts[] = $obj;
                        }
                    }
                }

                $this->collOrderProducts = $collOrderProducts;
                $this->collOrderProductsPartial = false;
            }
        }

        return $this->collOrderProducts;
    }

    /**
     * Sets a collection of OrderProduct objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $orderProducts A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildOrder The current object (for fluent API support)
     */
    public function setOrderProducts(Collection $orderProducts, ConnectionInterface $con = null)
    {
        $orderProductsToDelete = $this->getOrderProducts(new Criteria(), $con)->diff($orderProducts);


        $this->orderProductsScheduledForDeletion = $orderProductsToDelete;

        foreach ($orderProductsToDelete as $orderProductRemoved) {
            $orderProductRemoved->setOrder(null);
        }

        $this->collOrderProducts = null;
        foreach ($orderProducts as $orderProduct) {
            $this->addOrderProduct($orderProduct);
        }

        $this->collOrderProducts = $orderProducts;
        $this->collOrderProductsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related OrderProduct objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related OrderProduct objects.
     * @throws PropelException
     */
    public function countOrderProducts(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collOrderProductsPartial && !$this->isNew();
        if (null === $this->collOrderProducts || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collOrderProducts) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getOrderProducts());
            }

            $query = ChildOrderProductQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByOrder($this)
                ->count($con);
        }

        return count($this->collOrderProducts);
    }

    /**
     * Method called to associate a ChildOrderProduct object to this object
     * through the ChildOrderProduct foreign key attribute.
     *
     * @param    ChildOrderProduct $l ChildOrderProduct
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function addOrderProduct(ChildOrderProduct $l)
    {
        if ($this->collOrderProducts === null) {
            $this->initOrderProducts();
            $this->collOrderProductsPartial = true;
        }

        if (!in_array($l, $this->collOrderProducts->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddOrderProduct($l);
        }

        return $this;
    }

    /**
     * @param OrderProduct $orderProduct The orderProduct object to add.
     */
    protected function doAddOrderProduct($orderProduct)
    {
        $this->collOrderProducts[]= $orderProduct;
        $orderProduct->setOrder($this);
    }

    /**
     * @param  OrderProduct $orderProduct The orderProduct object to remove.
     * @return ChildOrder The current object (for fluent API support)
     */
    public function removeOrderProduct($orderProduct)
    {
        if ($this->getOrderProducts()->contains($orderProduct)) {
            $this->collOrderProducts->remove($this->collOrderProducts->search($orderProduct));
            if (null === $this->orderProductsScheduledForDeletion) {
                $this->orderProductsScheduledForDeletion = clone $this->collOrderProducts;
                $this->orderProductsScheduledForDeletion->clear();
            }
            $this->orderProductsScheduledForDeletion[]= clone $orderProduct;
            $orderProduct->setOrder(null);
        }

        return $this;
    }

    /**
     * Clears out the collCouponOrders collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addCouponOrders()
     */
    public function clearCouponOrders()
    {
        $this->collCouponOrders = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collCouponOrders collection loaded partially.
     */
    public function resetPartialCouponOrders($v = true)
    {
        $this->collCouponOrdersPartial = $v;
    }

    /**
     * Initializes the collCouponOrders collection.
     *
     * By default this just sets the collCouponOrders collection to an empty array (like clearcollCouponOrders());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initCouponOrders($overrideExisting = true)
    {
        if (null !== $this->collCouponOrders && !$overrideExisting) {
            return;
        }
        $this->collCouponOrders = new ObjectCollection();
        $this->collCouponOrders->setModel('\Thelia\Model\CouponOrder');
    }

    /**
     * Gets an array of ChildCouponOrder objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildOrder is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildCouponOrder[] List of ChildCouponOrder objects
     * @throws PropelException
     */
    public function getCouponOrders($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collCouponOrdersPartial && !$this->isNew();
        if (null === $this->collCouponOrders || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collCouponOrders) {
                // return empty collection
                $this->initCouponOrders();
            } else {
                $collCouponOrders = ChildCouponOrderQuery::create(null, $criteria)
                    ->filterByOrder($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collCouponOrdersPartial && count($collCouponOrders)) {
                        $this->initCouponOrders(false);

                        foreach ($collCouponOrders as $obj) {
                            if (false == $this->collCouponOrders->contains($obj)) {
                                $this->collCouponOrders->append($obj);
                            }
                        }

                        $this->collCouponOrdersPartial = true;
                    }

                    $collCouponOrders->getInternalIterator()->rewind();

                    return $collCouponOrders;
                }

                if ($partial && $this->collCouponOrders) {
                    foreach ($this->collCouponOrders as $obj) {
                        if ($obj->isNew()) {
                            $collCouponOrders[] = $obj;
                        }
                    }
                }

                $this->collCouponOrders = $collCouponOrders;
                $this->collCouponOrdersPartial = false;
            }
        }

        return $this->collCouponOrders;
    }

    /**
     * Sets a collection of CouponOrder objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $couponOrders A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildOrder The current object (for fluent API support)
     */
    public function setCouponOrders(Collection $couponOrders, ConnectionInterface $con = null)
    {
        $couponOrdersToDelete = $this->getCouponOrders(new Criteria(), $con)->diff($couponOrders);


        $this->couponOrdersScheduledForDeletion = $couponOrdersToDelete;

        foreach ($couponOrdersToDelete as $couponOrderRemoved) {
            $couponOrderRemoved->setOrder(null);
        }

        $this->collCouponOrders = null;
        foreach ($couponOrders as $couponOrder) {
            $this->addCouponOrder($couponOrder);
        }

        $this->collCouponOrders = $couponOrders;
        $this->collCouponOrdersPartial = false;

        return $this;
    }

    /**
     * Returns the number of related CouponOrder objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related CouponOrder objects.
     * @throws PropelException
     */
    public function countCouponOrders(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collCouponOrdersPartial && !$this->isNew();
        if (null === $this->collCouponOrders || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collCouponOrders) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getCouponOrders());
            }

            $query = ChildCouponOrderQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByOrder($this)
                ->count($con);
        }

        return count($this->collCouponOrders);
    }

    /**
     * Method called to associate a ChildCouponOrder object to this object
     * through the ChildCouponOrder foreign key attribute.
     *
     * @param    ChildCouponOrder $l ChildCouponOrder
     * @return   \Thelia\Model\Order The current object (for fluent API support)
     */
    public function addCouponOrder(ChildCouponOrder $l)
    {
        if ($this->collCouponOrders === null) {
            $this->initCouponOrders();
            $this->collCouponOrdersPartial = true;
        }

        if (!in_array($l, $this->collCouponOrders->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddCouponOrder($l);
        }

        return $this;
    }

    /**
     * @param CouponOrder $couponOrder The couponOrder object to add.
     */
    protected function doAddCouponOrder($couponOrder)
    {
        $this->collCouponOrders[]= $couponOrder;
        $couponOrder->setOrder($this);
    }

    /**
     * @param  CouponOrder $couponOrder The couponOrder object to remove.
     * @return ChildOrder The current object (for fluent API support)
     */
    public function removeCouponOrder($couponOrder)
    {
        if ($this->getCouponOrders()->contains($couponOrder)) {
            $this->collCouponOrders->remove($this->collCouponOrders->search($couponOrder));
            if (null === $this->couponOrdersScheduledForDeletion) {
                $this->couponOrdersScheduledForDeletion = clone $this->collCouponOrders;
                $this->couponOrdersScheduledForDeletion->clear();
            }
            $this->couponOrdersScheduledForDeletion[]= clone $couponOrder;
            $couponOrder->setOrder(null);
        }

        return $this;
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->id = null;
        $this->ref = null;
        $this->customer_id = null;
        $this->address_invoice = null;
        $this->address_delivery = null;
        $this->invoice_date = null;
        $this->currency_id = null;
        $this->currency_rate = null;
        $this->transaction = null;
        $this->delivery_num = null;
        $this->invoice = null;
        $this->postage = null;
        $this->payment = null;
        $this->carrier = null;
        $this->status_id = null;
        $this->lang = null;
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
            if ($this->collOrderProducts) {
                foreach ($this->collOrderProducts as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collCouponOrders) {
                foreach ($this->collCouponOrders as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        if ($this->collOrderProducts instanceof Collection) {
            $this->collOrderProducts->clearIterator();
        }
        $this->collOrderProducts = null;
        if ($this->collCouponOrders instanceof Collection) {
            $this->collCouponOrders->clearIterator();
        }
        $this->collCouponOrders = null;
        $this->aCurrency = null;
        $this->aCustomer = null;
        $this->aOrderAddressRelatedByAddressInvoice = null;
        $this->aOrderAddressRelatedByAddressDelivery = null;
        $this->aOrderStatus = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(OrderTableMap::DEFAULT_STRING_FORMAT);
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     ChildOrder The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[] = OrderTableMap::UPDATED_AT;

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
