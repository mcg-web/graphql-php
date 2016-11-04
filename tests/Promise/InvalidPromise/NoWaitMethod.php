<?php
namespace GraphQL\Tests\Promise\InvalidPromise;

class NoWaitMethod
{
    public function then()
    {
        return $this;
    }
}
