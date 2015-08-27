#!/usr/bin/env php
<?php
require dirname(__DIR__).'/vendor/autoload.php';

use Icicle\Concurrent\Worker\HelloTask;
use Icicle\Concurrent\Worker\WorkerThread;
use Icicle\Coroutine;
use Icicle\Loop;

Coroutine\create(function () {
    $worker = new WorkerThread();
    $worker->start();

    $returnValue = (yield $worker->enqueue(new HelloTask()));
    yield $worker->shutdown();
})->done();

Loop\run();