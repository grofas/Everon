<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\DataMapper\Criteria;

use Everon\Dependency;
use Everon\Helper;
use Everon\DataMapper\Exception;
use Everon\DataMapper\Interfaces;

class Builder implements Interfaces\Criteria\Builder
{
    use Dependency\Injection\Factory;
    
    use Helper\Arrays;
    use Helper\ToArray;
    use Helper\ToString;

    const GLUE_AND = 'AND';
    const GLUE_OR = 'OR';

    protected static $operator_mappers = [
        Operator::SQL_EQUAL => Operator::TYPE_EQUAL,
        Operator::SQL_NOT_EQUAL => Operator::TYPE_NOT_EQUAL,
        Operator::SQL_LIKE => Operator::TYPE_LIKE,
        Operator::SQL_IN => Operator::TYPE_IN,
        Operator::SQL_NOT_IN => Operator::TYPE_NOT_IN,
        Operator::SQL_IS => Operator::TYPE_IS,
        Operator::SQL_NOT_IS => Operator::TYPE_NOT_IS,
        Operator::SQL_GREATER_THEN => Operator::TYPE_GREATER_THEN,
        Operator::SQL_GREATER_OR_EQUAL => Operator::TYPE_GREATER_OR_EQUAL,
        Operator::SQL_SMALLER_THEN => Operator::TYPE_SMALLER_THEN,
        Operator::SQL_SMALLER_OR_EQUAL => Operator::TYPE_SMALLER_OR_EQUAL,
        Operator::SQL_BETWEEN => Operator::TYPE_BETWEEN,
        Operator::SQL_BETWEEN => Operator::TYPE_NOT_BETWEEN,
        Operator::SQL_RAW => Operator::TYPE_RAW
    ];
    
    /**
     * @var string
     */
    protected $current = -1;

    /**
     * @var \Everon\Interfaces\Collection
     */
    protected $ContainerCollection = null;

    /**
     * @var string
     */
    protected $glue = self::GLUE_AND;

    /**
     * @var int
     */
    protected $offset = null;

    /**
     * @var int
     */
    protected $limit = null;

    /**
     * @var array
     */
    protected $order_by = [];

    /**
     * @var string
     */
    protected $group_by = null;

    
    public function __construct()
    {
        $this->ContainerCollection = new Helper\Collection([]);
    }

    /**
     * @return array
     */
    protected function getToArray()
    {
        $SqlPart = $this->toSqlPart();
        return $SqlPart->getParameters();
    }

    /**
     * @return string
     */
    protected function getToString()
    {
        $SqlPart = $this->toSqlPart();
        return $SqlPart->getSql();
    }

    /**
     * @param Interfaces\Criteria\Container $Container
     * @return string
     */
    protected function criteriaToSql(Interfaces\Criteria\Container $Container)
    {
        /**
         * @var Interfaces\Criteria\Criterium $Criterium
         */
        $sql = '';
        foreach ($Container->getCriteria()->getCriteriumCollection() as $Criterium) {
            $SqlPart = $Criterium->getSqlPart();
            $sql .= ltrim($Criterium->getGlue().' '.$SqlPart->getSql().' ');
        }

        return '('.rtrim($sql).')';
    }

    /**
     * @param Interfaces\Criteria\Container $Container
     * @return array
     */
    protected function criteriaToParameters(Interfaces\Criteria\Container $Container)
    {
        /**
         * @var Interfaces\Criteria\Criterium $Criterium
         */
        $parameters = [];
        foreach ($Container->getCriteria()->getCriteriumCollection() as $Criterium) {
            $SqlPart = $Criterium->getSqlPart();
            $parameters[] = $SqlPart->getParameters();
        }

        return $parameters;
    }

    /**
     * @inheritdoc
     */
    public function where($column, $operator, $value)
    {
        $this->current++;
        $Criterium = $this->getFactory()->buildDataMapperCriterium($column, $operator, $value);
        $this->getCurrentContainer()->getCriteria()->where($Criterium);
        $this->getCurrentContainer()->setGlue(null);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function andWhere($column, $operator, $value)
    {
        $Criterium = $this->getFactory()->buildDataMapperCriterium($column, $operator, $value);
        $this->getCurrentContainer()->getCriteria()->andWhere($Criterium);
        $this->getCurrentContainer()->setGlue(self::GLUE_AND);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhere($column, $operator, $value)
    {
        $Criterium = $this->getFactory()->buildDataMapperCriterium($column, $operator, $value);
        $this->getCurrentContainer()->getCriteria()->orWhere($Criterium);
        $this->getCurrentContainer()->setGlue(self::GLUE_OR);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function whereRaw($sql)
    {
        $this->current++;
        $Criterium = $this->getFactory()->buildDataMapperCriterium($sql, 'raw', null);
        $this->getCurrentContainer()->getCriteria()->where($Criterium);
        $this->getCurrentContainer()->setGlue(null);
        return $this;
    }
    
    public function andWhereRaw($sql)
    {
        $Criterium = $this->getFactory()->buildDataMapperCriterium($sql, 'raw', null);
        $this->getCurrentContainer()->getCriteria()->andWhere($Criterium);
        $this->getCurrentContainer()->setGlue(self::GLUE_AND);
        return $this;
    }

    public function orWhereRaw($sql)
    {
        $Criterium = $this->getFactory()->buildDataMapperCriterium($sql, 'raw', null);
        $this->getCurrentContainer()->getCriteria()->orWhere($Criterium);
        $this->getCurrentContainer()->setGlue(self::GLUE_OR);
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function getCurrentContainer()
    {
        if ($this->ContainerCollection->has($this->current) === false) {
            $Criteria = $this->getFactory()->buildDataMapperCriteria();
            $Container = $this->getFactory()->buildCriteriaContainer($Criteria, null);
            $this->ContainerCollection[$this->current] = $Container; 
        }
        
        return $this->ContainerCollection[$this->current];
    }

    /**
     * @inheritdoc
     */
    public function setCurrentCriteria(Interfaces\Criteria\Container $Container)
    {
        $this->ContainerCollection[$this->current] = $Container;
    }

    /**
     * @inheritdoc
     */
    public function getContainerCollection()
    {
        return $this->ContainerCollection;
    }

    /**
     * @inheritdoc
     */
    public function setContainerCollection(\Everon\Interfaces\Collection $ContainerCollection)
    {
        $this->ContainerCollection = $ContainerCollection;
    }

    /**
     * @inheritdoc
     */
    public function getGlue()
    {
        return $this->getCurrentContainer()->getGlue();
    }

    /**
     * @inheritdoc
     */
    public function resetGlue()
    {
        $this->getCurrentContainer()->setGlue(null);
    }

    /**
     * @inheritdoc
     */
    public function glueByAnd()
    {
        $this->getCurrentContainer()->setGlue(self::GLUE_AND);
    }

    /**
     * @inheritdoc
     */
    public function glueByOr()
    {
        $this->getCurrentContainer()->setGlue(self::GLUE_OR);
    }

    /**
     * @return string
     */
    public function getGroupBy()
    {
        return $this->group_by;
    }

    /**
     * @param string $group_by
     */
    public function setGroupBy($group_by)
    {
        $this->group_by = $group_by;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return array
     */
    public function getOrderBy()
    {
        return $this->order_by;
    }

    /**
     * @param array $order_by
     */
    public function setOrderBy(array $order_by)
    {
        $this->order_by = $order_by;
    }

    /**
     * @return string
     */
    protected function getOffsetLimitSql()
    {
        if ((int) $this->getLimit() === 0 && $this->getOffset() === null) {
            return '';
        }

        if ((int) $this->getLimit() === 0 && $this->getOffset() !== null) {
            return 'OFFSET '.$this->offset;
        }

        if ((int) $this->getLimit() !== 0 && $this->getOffset() === null) {
            return 'LIMIT '.$this->getLimit();
        }

        return 'LIMIT '.$this->getLimit(). ' OFFSET '.$this->getOffset();
    }

    /**
     * @return string
     */
    protected function getOrderByAndSortSql()
    {
        if (is_array($this->getOrderBy()) === false || empty($this->getOrderBy())) {
            return '';
        }

        $order_by = '';
        foreach ($this->getOrderBy() as $name => $sort) {
            $order_by .= "${name} ".$sort.',';
        }

        if ($order_by !== '') {
            $order_by = trim($order_by, ',');
            $order_by = 'ORDER BY '.$order_by;
        }

        return $order_by;
    }

    protected function getGroupBySql()
    {
        if ($this->getGroupBy() === null) {
            return '';
        }

        return 'GROUP BY '.$this->getGroupBy();
    }
    
    /**
     * @inheritdoc
     */
    public function toSqlPart()
    {
        $sql = [];
        $parameters = [];
        $glue = null;

        /**
         * @var Interfaces\Criteria $Container
         */
        foreach ($this->getContainerCollection() as $Container) {
            $glue = $Container->getGlue();
            $sql[] = $this->criteriaToSql($Container).' '.$glue;
            $criteria_parameters = $this->criteriaToParameters($Container);
            $tmp = [];
            
            foreach ($criteria_parameters as $cp_value) {
                $tmp = $this->arrayMergeDefault($tmp, $cp_value);
            }

            $parameters = $this->arrayMergeDefault($tmp, $parameters);
        }

        $sql = implode("\n", $sql);
        $sql = rtrim($sql, $glue.' ');
        
        $sql .= ' '.trim($this->getGroupBySql().' '.
            $this->getOrderByAndSortSql().' '.
            $this->getOffsetLimitSql())
        ;
        
        return $this->getFactory()->buildDataMapperSqlPart(trim($sql), $parameters);
    }

    /**
     * @inheritdoc
     */
    public static function getOperatorClassNameBySqlOperator($operator)
    {
        $operator = strtoupper(trim($operator));
        if (isset(static::$operator_mappers[$operator]) === false) {
            throw new Exception\CriteriaBuilder('Unknown operator type: "%s"', $operator);
        }
        
        return static::$operator_mappers[$operator];
    }

    /**
     * @inheritdoc
     */
    public static function randomizeParameterName($name)
    {
        return $name.'_'.mt_rand(100, time());
    }
    
}