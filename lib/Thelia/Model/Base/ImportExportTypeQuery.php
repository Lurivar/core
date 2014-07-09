<?php

namespace Thelia\Model\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Thelia\Model\ImportExportType as ChildImportExportType;
use Thelia\Model\ImportExportTypeI18nQuery as ChildImportExportTypeI18nQuery;
use Thelia\Model\ImportExportTypeQuery as ChildImportExportTypeQuery;
use Thelia\Model\Map\ImportExportTypeTableMap;

/**
 * Base class that represents a query for the 'import_export_type' table.
 *
 *
 *
 * @method     ChildImportExportTypeQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildImportExportTypeQuery orderByUrlAction($order = Criteria::ASC) Order by the url_action column
 * @method     ChildImportExportTypeQuery orderByImportExportCategoryId($order = Criteria::ASC) Order by the import_export_category_id column
 *
 * @method     ChildImportExportTypeQuery groupById() Group by the id column
 * @method     ChildImportExportTypeQuery groupByUrlAction() Group by the url_action column
 * @method     ChildImportExportTypeQuery groupByImportExportCategoryId() Group by the import_export_category_id column
 *
 * @method     ChildImportExportTypeQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildImportExportTypeQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildImportExportTypeQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildImportExportTypeQuery leftJoinImportExportCategory($relationAlias = null) Adds a LEFT JOIN clause to the query using the ImportExportCategory relation
 * @method     ChildImportExportTypeQuery rightJoinImportExportCategory($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ImportExportCategory relation
 * @method     ChildImportExportTypeQuery innerJoinImportExportCategory($relationAlias = null) Adds a INNER JOIN clause to the query using the ImportExportCategory relation
 *
 * @method     ChildImportExportTypeQuery leftJoinImportExportTypeI18n($relationAlias = null) Adds a LEFT JOIN clause to the query using the ImportExportTypeI18n relation
 * @method     ChildImportExportTypeQuery rightJoinImportExportTypeI18n($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ImportExportTypeI18n relation
 * @method     ChildImportExportTypeQuery innerJoinImportExportTypeI18n($relationAlias = null) Adds a INNER JOIN clause to the query using the ImportExportTypeI18n relation
 *
 * @method     ChildImportExportType findOne(ConnectionInterface $con = null) Return the first ChildImportExportType matching the query
 * @method     ChildImportExportType findOneOrCreate(ConnectionInterface $con = null) Return the first ChildImportExportType matching the query, or a new ChildImportExportType object populated from the query conditions when no match is found
 *
 * @method     ChildImportExportType findOneById(int $id) Return the first ChildImportExportType filtered by the id column
 * @method     ChildImportExportType findOneByUrlAction(string $url_action) Return the first ChildImportExportType filtered by the url_action column
 * @method     ChildImportExportType findOneByImportExportCategoryId(int $import_export_category_id) Return the first ChildImportExportType filtered by the import_export_category_id column
 *
 * @method     array findById(int $id) Return ChildImportExportType objects filtered by the id column
 * @method     array findByUrlAction(string $url_action) Return ChildImportExportType objects filtered by the url_action column
 * @method     array findByImportExportCategoryId(int $import_export_category_id) Return ChildImportExportType objects filtered by the import_export_category_id column
 *
 */
abstract class ImportExportTypeQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Thelia\Model\Base\ImportExportTypeQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'thelia', $modelName = '\\Thelia\\Model\\ImportExportType', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildImportExportTypeQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildImportExportTypeQuery
     */
    public static function create($modelAlias = null, $criteria = null)
    {
        if ($criteria instanceof \Thelia\Model\ImportExportTypeQuery) {
            return $criteria;
        }
        $query = new \Thelia\Model\ImportExportTypeQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildImportExportType|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ImportExportTypeTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ImportExportTypeTableMap::DATABASE_NAME);
        }
        $this->basePreSelect($con);
        if ($this->formatter || $this->modelAlias || $this->with || $this->select
         || $this->selectColumns || $this->asColumns || $this->selectModifiers
         || $this->map || $this->having || $this->joins) {
            return $this->findPkComplex($key, $con);
        } else {
            return $this->findPkSimple($key, $con);
        }
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return   ChildImportExportType A model object, or null if the key is not found
     */
    protected function findPkSimple($key, $con)
    {
        $sql = 'SELECT `ID`, `URL_ACTION`, `IMPORT_EXPORT_CATEGORY_ID` FROM `import_export_type` WHERE `ID` = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $obj = new ChildImportExportType();
            $obj->hydrate($row);
            ImportExportTypeTableMap::addInstanceToPool($obj, (string) $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildImportExportType|array|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param     array $keys Primary keys to use for the query
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ObjectCollection|array|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param     mixed $key Primary key to use for the query
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ImportExportTypeTableMap::ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ImportExportTypeTableMap::ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the id column
     *
     * Example usage:
     * <code>
     * $query->filterById(1234); // WHERE id = 1234
     * $query->filterById(array(12, 34)); // WHERE id IN (12, 34)
     * $query->filterById(array('min' => 12)); // WHERE id > 12
     * </code>
     *
     * @param     mixed $id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ImportExportTypeTableMap::ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ImportExportTypeTableMap::ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ImportExportTypeTableMap::ID, $id, $comparison);
    }

    /**
     * Filter the query on the url_action column
     *
     * Example usage:
     * <code>
     * $query->filterByUrlAction('fooValue');   // WHERE url_action = 'fooValue'
     * $query->filterByUrlAction('%fooValue%'); // WHERE url_action LIKE '%fooValue%'
     * </code>
     *
     * @param     string $urlAction The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function filterByUrlAction($urlAction = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($urlAction)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $urlAction)) {
                $urlAction = str_replace('*', '%', $urlAction);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ImportExportTypeTableMap::URL_ACTION, $urlAction, $comparison);
    }

    /**
     * Filter the query on the import_export_category_id column
     *
     * Example usage:
     * <code>
     * $query->filterByImportExportCategoryId(1234); // WHERE import_export_category_id = 1234
     * $query->filterByImportExportCategoryId(array(12, 34)); // WHERE import_export_category_id IN (12, 34)
     * $query->filterByImportExportCategoryId(array('min' => 12)); // WHERE import_export_category_id > 12
     * </code>
     *
     * @see       filterByImportExportCategory()
     *
     * @param     mixed $importExportCategoryId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function filterByImportExportCategoryId($importExportCategoryId = null, $comparison = null)
    {
        if (is_array($importExportCategoryId)) {
            $useMinMax = false;
            if (isset($importExportCategoryId['min'])) {
                $this->addUsingAlias(ImportExportTypeTableMap::IMPORT_EXPORT_CATEGORY_ID, $importExportCategoryId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($importExportCategoryId['max'])) {
                $this->addUsingAlias(ImportExportTypeTableMap::IMPORT_EXPORT_CATEGORY_ID, $importExportCategoryId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ImportExportTypeTableMap::IMPORT_EXPORT_CATEGORY_ID, $importExportCategoryId, $comparison);
    }

    /**
     * Filter the query by a related \Thelia\Model\ImportExportCategory object
     *
     * @param \Thelia\Model\ImportExportCategory|ObjectCollection $importExportCategory The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function filterByImportExportCategory($importExportCategory, $comparison = null)
    {
        if ($importExportCategory instanceof \Thelia\Model\ImportExportCategory) {
            return $this
                ->addUsingAlias(ImportExportTypeTableMap::IMPORT_EXPORT_CATEGORY_ID, $importExportCategory->getId(), $comparison);
        } elseif ($importExportCategory instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ImportExportTypeTableMap::IMPORT_EXPORT_CATEGORY_ID, $importExportCategory->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByImportExportCategory() only accepts arguments of type \Thelia\Model\ImportExportCategory or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ImportExportCategory relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function joinImportExportCategory($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ImportExportCategory');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'ImportExportCategory');
        }

        return $this;
    }

    /**
     * Use the ImportExportCategory relation ImportExportCategory object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return   \Thelia\Model\ImportExportCategoryQuery A secondary query class using the current class as primary query
     */
    public function useImportExportCategoryQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinImportExportCategory($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ImportExportCategory', '\Thelia\Model\ImportExportCategoryQuery');
    }

    /**
     * Filter the query by a related \Thelia\Model\ImportExportTypeI18n object
     *
     * @param \Thelia\Model\ImportExportTypeI18n|ObjectCollection $importExportTypeI18n  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function filterByImportExportTypeI18n($importExportTypeI18n, $comparison = null)
    {
        if ($importExportTypeI18n instanceof \Thelia\Model\ImportExportTypeI18n) {
            return $this
                ->addUsingAlias(ImportExportTypeTableMap::ID, $importExportTypeI18n->getId(), $comparison);
        } elseif ($importExportTypeI18n instanceof ObjectCollection) {
            return $this
                ->useImportExportTypeI18nQuery()
                ->filterByPrimaryKeys($importExportTypeI18n->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByImportExportTypeI18n() only accepts arguments of type \Thelia\Model\ImportExportTypeI18n or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ImportExportTypeI18n relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function joinImportExportTypeI18n($relationAlias = null, $joinType = 'LEFT JOIN')
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ImportExportTypeI18n');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'ImportExportTypeI18n');
        }

        return $this;
    }

    /**
     * Use the ImportExportTypeI18n relation ImportExportTypeI18n object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return   \Thelia\Model\ImportExportTypeI18nQuery A secondary query class using the current class as primary query
     */
    public function useImportExportTypeI18nQuery($relationAlias = null, $joinType = 'LEFT JOIN')
    {
        return $this
            ->joinImportExportTypeI18n($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ImportExportTypeI18n', '\Thelia\Model\ImportExportTypeI18nQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildImportExportType $importExportType Object to remove from the list of results
     *
     * @return ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function prune($importExportType = null)
    {
        if ($importExportType) {
            $this->addUsingAlias(ImportExportTypeTableMap::ID, $importExportType->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the import_export_type table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ImportExportTypeTableMap::DATABASE_NAME);
        }
        $affectedRows = 0; // initialize var to track total num of affected rows
        try {
            // use transaction because $criteria could contain info
            // for more than one table or we could emulating ON DELETE CASCADE, etc.
            $con->beginTransaction();
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ImportExportTypeTableMap::clearInstancePool();
            ImportExportTypeTableMap::clearRelatedInstancePool();

            $con->commit();
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }

        return $affectedRows;
    }

    /**
     * Performs a DELETE on the database, given a ChildImportExportType or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or ChildImportExportType object or primary key or array of primary keys
     *              which is used to create the DELETE statement
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
     public function delete(ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ImportExportTypeTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ImportExportTypeTableMap::DATABASE_NAME);

        $affectedRows = 0; // initialize var to track total num of affected rows

        try {
            // use transaction because $criteria could contain info
            // for more than one table or we could emulating ON DELETE CASCADE, etc.
            $con->beginTransaction();


        ImportExportTypeTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ImportExportTypeTableMap::clearRelatedInstancePool();
            $con->commit();

            return $affectedRows;
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }
    }

    // i18n behavior

    /**
     * Adds a JOIN clause to the query using the i18n relation
     *
     * @param     string $locale Locale to use for the join condition, e.g. 'fr_FR'
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
     *
     * @return    ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function joinI18n($locale = 'en_US', $relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $relationName = $relationAlias ? $relationAlias : 'ImportExportTypeI18n';

        return $this
            ->joinImportExportTypeI18n($relationAlias, $joinType)
            ->addJoinCondition($relationName, $relationName . '.Locale = ?', $locale);
    }

    /**
     * Adds a JOIN clause to the query and hydrates the related I18n object.
     * Shortcut for $c->joinI18n($locale)->with()
     *
     * @param     string $locale Locale to use for the join condition, e.g. 'fr_FR'
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
     *
     * @return    ChildImportExportTypeQuery The current query, for fluid interface
     */
    public function joinWithI18n($locale = 'en_US', $joinType = Criteria::LEFT_JOIN)
    {
        $this
            ->joinI18n($locale, null, $joinType)
            ->with('ImportExportTypeI18n');
        $this->with['ImportExportTypeI18n']->setIsWithOneToMany(false);

        return $this;
    }

    /**
     * Use the I18n relation query object
     *
     * @see       useQuery()
     *
     * @param     string $locale Locale to use for the join condition, e.g. 'fr_FR'
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
     *
     * @return    ChildImportExportTypeI18nQuery A secondary query class using the current class as primary query
     */
    public function useI18nQuery($locale = 'en_US', $relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinI18n($locale, $relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ImportExportTypeI18n', '\Thelia\Model\ImportExportTypeI18nQuery');
    }

} // ImportExportTypeQuery
