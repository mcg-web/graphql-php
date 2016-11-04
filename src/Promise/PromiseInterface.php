<?php
namespace GraphQL\Promise;

interface PromiseInterface
{
    /**
     * Waits until the promise completes if possible.
     *
     * @return mixed
     * @throws \LogicException if the promise has no wait function.
     */
    public function wait();

    /**
     * Appends fulfillment and rejection handlers to the promise, and returns
     * a new promise resolving to the return value of the called handler.
     *
     * @param callable $onFulfilled Invoked when the promise fulfills
     * @param callable $onRejected  Invoked when the promise is rejected
     *
     * @return PromiseInterface
     *
     * @throws \LogicException if the promise has no then function.
     */
    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    );
}
