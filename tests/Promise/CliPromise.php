<?php
namespace GraphQL\Tests\Promise;

class CliPromise
{
    private $pid;
    private $outputFile;
    private $onFulfilledCallbacks = [];
    private $onRejectedCallbacks = [];

    public function __construct($cmd)
    {
        $outputFile = sys_get_temp_dir() . '/PromiseTest.' . time() . '.output';

        exec(sprintf('(%s) > %s 2>&1 & echo $!', $cmd, $outputFile), $pidArr);

        $this->pid = $pidArr[0];
        $this->outputFile = $outputFile;
    }

    public function wait()
    {
        try {
            while (!$this->isTerminated()) { usleep(1000); }

            $log = new \stdClass();
            $log->pid = $this->pid;
            $log->output = shell_exec(sprintf('cat %s', $this->outputFile));

            foreach ($this->onFulfilledCallbacks as $onFulfilled) {
                $log = call_user_func($onFulfilled, $log);
            }
            return $log;
        } catch (\Exception $e) {
            foreach ($this->onRejectedCallbacks as $onRejected) {
                call_user_func($onRejected, $e);
            }
        }
        return null;
    }

    private function isTerminated()
    {
        try {
            $result = shell_exec(sprintf('ps %d', $this->pid));
            return count(preg_split("/\n/", $result)) <= 2;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * Appends fulfillment and rejection handlers to the promise, and returns
     * a new promise resolving to the return value of the called handler.
     *
     * @param callable $onFulfilled Invoked when the promise fulfills
     * @param callable $onRejected Invoked when the promise is rejected
     *
     * @return $this
     */
    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    )
    {
        if (null !== $onFulfilled) {
            $this->onFulfilledCallbacks[] = $onFulfilled;
        }

        if (null !== $onFulfilled) {
            $this->onRejectedCallbacks[] = $onRejected;
        }

        return $this;
    }
}
