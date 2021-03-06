<?php

namespace Amp\Parallel\Worker;

use Amp\Coroutine;
use Amp\Parallel\Sync\Channel;
use Amp\Promise;
use function Amp\call;

class TaskRunner {
    /** @var \Amp\Parallel\Sync\Channel */
    private $channel;

    /** @var \Amp\Parallel\Worker\Environment */
    private $environment;

    public function __construct(Channel $channel, Environment $environment) {
        $this->channel = $channel;
        $this->environment = $environment;
    }

    /**
     * Runs the task runner, receiving tasks from the parent and sending the result of those tasks.
     *
     * @return \Amp\Promise
     */
    public function run(): Promise {
        return new Coroutine($this->execute());
    }

    /**
     * @coroutine
     *
     * @return \Generator
     */
    private function execute(): \Generator {
        $job = yield $this->channel->receive();

        while ($job instanceof Internal\Job) {
            try {
                $result = yield call([$job->getTask(), "run"], $this->environment);
                $result = new Internal\TaskSuccess($job->getId(), $result);
            } catch (\Throwable $exception) {
                $result = new Internal\TaskFailure($job->getId(), $exception);
            }

            $job = null; // Free memory from last job.

            yield $this->channel->send($result);

            $result = null; // Free memory from last result.

            $job = yield $this->channel->receive();
        }

        return $job;
    }
}
