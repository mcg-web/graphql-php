<?php

namespace GraphQL\Promise;

class PromiseWrapper implements PromiseInterface
{
    private $wrappedPromise;

    /**
     * PromiseWrapper constructor.
     * @param $promise
     */
    public function __construct($promise = null)
    {
        if (null !== $promise) {
            $this->setWrappedPromise($promise);
        }
    }

    /**
     * @param $promise
     * @return self
     */
    public static function wrap($promise)
    {
        return new static($promise);
    }

    /**
     * Waits until the promise completes if possible.
     *
     * @return mixed
     */
    public function wait()
    {
        if ($this->objectHasMethod($this->getWrappedPromise(), 'wait')) {
            return $this->getWrappedPromise()->wait();
        }
        $result = null;
        /** @var \Exception|null $exception */
        $exception = null;
        $this->getWrappedPromise()->then(
            function ($value) use (&$result) {
                $result = $value;
                return $value;
            },
            function ($reason) use (&$exception) {
                $exception = $reason;
                return $reason;
            }
        );
        if (null !== $exception) {
            throw $exception;
        }

        return $result;
    }

    /**
     * Appends fulfillment and rejection handlers to the promise, and returns
     * a new promise resolving to the return value of the called handler.
     *
     * @param callable $onFulfilled Invoked when the promise fulfills
     * @param callable $onRejected Invoked when the promise is rejected
     *
     * @return self
     *
     * @throws \LogicException if the promise has no then function.
     */
    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    )
    {
        if (!$this->getWrappedPromise()) {
            throw new \LogicException('No wrapped promise found!');
        }

        if (null === $onFulfilled && null === $onRejected) {
            return $this;
        }

        return $this->wrap($this->getWrappedPromise()->then($onFulfilled, $onRejected));
    }

    public function getWrappedPromise()
    {
        return $this->wrappedPromise;
    }

    public function setWrappedPromise($wrappedPromise)
    {
        if (!$this->objectHasMethod($wrappedPromise, 'then')) {
            throw new \LogicException('Promise does not implement "then" method');
        }

        $this->wrappedPromise = $wrappedPromise;

        return $this;
    }

    protected function objectHasMethod($object, $method)
    {
        return is_object($object) && is_callable([$object, $method]);
    }
}
