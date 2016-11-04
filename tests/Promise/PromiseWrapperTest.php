<?php
namespace GraphQL\Tests\Promise;

use GraphQL\GraphQL;
use GraphQL\Promise\PromiseWrapper;
use GraphQL\Schema;
use GraphQL\Tests\Promise\InvalidPromise\NoThenMethod;
use GraphQL\Tests\Promise\InvalidPromise\NoWaitMethod;
use GraphQL\Tests\Promise\InvalidPromise\ThenReturnsInvalidPromiseMethod;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PromiseWrapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Promise does not implement "then" method
     */
    public function testWrapNotObject()
    {
        PromiseWrapper::wrap([]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Promise does not implement "then" method
     */
    public function testWrapObjectWithoutThenMethod()
    {
        PromiseWrapper::wrap(new NoThenMethod());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Promise does not implement "wait" method
     */
    public function testWrapObjectWithoutWaitMethod()
    {
        $promise = PromiseWrapper::wrap(new NoWaitMethod());
        $promise->wait();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Promise does not implement "then" method
     */
    public function testWrapObjectThenReturnsInvalidPromise()
    {
        $promise = PromiseWrapper::wrap(new ThenReturnsInvalidPromiseMethod());
        $promise->then(function() {});
    }
}
