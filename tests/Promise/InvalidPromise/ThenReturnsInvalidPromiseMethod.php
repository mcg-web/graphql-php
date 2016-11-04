<?php
namespace GraphQL\Tests\Promise\InvalidPromise;

class ThenReturnsInvalidPromiseMethod
{
    public function then()
    {
        return new NoThenMethod();
    }
}
