<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Tests the query builder
 */
namespace Opulence\QueryBuilders\PostgreSQL;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the query builder returns a DeleteQuery when we call delete()
     */
    public function testThatDeleteReturnsDeleteQueryBuilder()
    {
        $queryBuilder = new QueryBuilder();
        $this->assertInstanceOf(
            DeleteQuery::class,
            $queryBuilder->delete("tableName", "tableAlias")
        );
    }

    /**
     * Tests that the query builder returns a InsertQuery when we call insert()
     */
    public function testThatInsertReturnsInsertQueryBuilder()
    {
        $queryBuilder = new QueryBuilder();
        $this->assertInstanceOf(
            InsertQuery::class,
            $queryBuilder->insert("tableName", ["columnName" => "columnValue"])
        );
    }

    /**
     * Tests that the query builder returns a SelectQuery when we call select()
     */
    public function testThatSelectReturnsSelectQueryBuilder()
    {
        $queryBuilder = new QueryBuilder();
        $this->assertInstanceOf(
            SelectQuery::class,
            $queryBuilder->select("tableName", "tableAlias")
        );
    }

    /**
     * Tests that the query builder returns a UpdateQuery when we call update()
     */
    public function testThatUpdateReturnsUpdateQueryBuilder()
    {
        $queryBuilder = new QueryBuilder();
        $this->assertInstanceOf(
            UpdateQuery::class,
            $queryBuilder->update("tableName", "tableAlias", ["columnName" => "columnValue"])
        );
    }
} 