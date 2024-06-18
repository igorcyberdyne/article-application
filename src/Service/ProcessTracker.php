<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Throwable;

final class ProcessTracker
{
    /** @var mixed|null */
    protected mixed $callBackResponse = null;

    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param callable $callable
     * @param string $processId
     * @return mixed
     * @throws Throwable
     */
    public function start(callable $callable, string $processId): mixed
    {
        if (!is_callable($callable)) {
            return null;
        }

        $this->logger->info("[process] START <<<<<<<<<<< [$processId]");

        try {
            $this->callBackResponse = $callable($this->logger);
        } catch (Throwable $t) {
            $this->logger->critical("Uncaught PHP Exception " . get_class($t) . ' : "' . $t->getMessage());
            $this->logger->critical($t->getTraceAsString());

            $throwable = $t;
        }

        $this->logger->info("[process] END >>>>>>>>>>> [$processId]");

        if (!empty($throwable)) {
            throw $throwable;
        }

        return $this->callBackResponse;
    }
}