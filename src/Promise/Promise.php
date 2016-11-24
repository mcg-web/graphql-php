<?php

namespace GraphQL\Promise;

class Promise implements PromiseInterface
{

    private $promiseOrValues;

    private $onFulfilledCallbacks = [];
    private $onRejectCallbacks = [];

    /**
     * @param $promiseOrValues
     */
    protected function __construct($promiseOrValues)
    {
        $this->promiseOrValues = $promiseOrValues;
    }

    /**
     * @param $promiseOrValues
     * @return self
     */
    public static function all($promiseOrValues)
    {
        return new static($promiseOrValues);
    }

    public static function isThenable($value)
    {
        return ($value instanceof PromiseInterface);
    }

    public static function completePromiseIfNeeded($promiseOrValues)
    {
        if (self::isThenable($promiseOrValues)) {
            $results = static::completePromiseIfNeeded($promiseOrValues->wait());
            return $results;
        } elseif (is_array($promiseOrValues) || $promiseOrValues instanceof \Traversable) {
            $results = [];
            foreach ($promiseOrValues as $key => $promiseOrValue) {
                $results[$key] = static::completePromiseIfNeeded($promiseOrValue);
            }
            return $results;
        }

        return $promiseOrValues;
    }

    /**
     * Waits until the promise completes if possible.
     * @return mixed
     * @throws
     */
    public function wait()
    {
        try {
            $values = static::completePromiseIfNeeded($this->promiseOrValues);
            foreach ($this->onFulfilledCallbacks as $onFulfilled) {
                $values = $onFulfilled($values);
            }
            return $values;
        } catch (\Exception $reason) {
            foreach ($this->onRejectCallbacks as $onReject) {
                $reason = $onReject($reason);
            }
            if ($reason instanceof \Exception) {
                throw $reason;
            }
        }
        return null;
    }

    /**
     * Appends fulfillment and rejection handlers to the promise, and returns
     * a new promise resolving to the return value of the called handler.
     *
     * @param callable $onFulfilled Invoked when the promise fulfills
     * @param callable $onRejected Invoked when the promise is rejected
     *
     * @return self
     */
    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    )
    {
        if (null !== $onFulfilled) {
            $this->onFulfilledCallbacks[] = $onFulfilled;
        }

        if (null !== $onRejected) {
            $this->onRejectCallbacks[] = $onRejected;
        }

        return $this;
    }
}
