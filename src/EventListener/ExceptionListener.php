<?php

namespace App\EventListener;

use App\Exception\DomainException;
use App\Service\ProcessTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

#[AsEventListener]
class ExceptionListener
{
    public function __construct(
        private readonly ProcessTracker $processTracker,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(ExceptionEvent $event): void
    {

        $throwable = $event->getThrowable();

        $this->processTracker->start(function (LoggerInterface $logger) use ($event, $throwable) {

            $response = new JsonResponse();
            if ($throwable instanceof HttpExceptionInterface) {
                $response->setStatusCode($throwable->getStatusCode());
                $response->headers->replace($throwable->getHeaders());

            } else {
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $response->setData([
                "error" => $throwable->getMessage(),
            ]);

            $event->setResponse($response);

            $errors = [];
            if ($previous = $throwable->getPrevious()) {
                $errors["previous"] = [
                    "code" => $previous->getCode(),
                    "error" => $previous->getMessage(),
                    "trace" => $previous->getTraceAsString(),
                ];
            }
            $errors = array_merge($errors, [
                "code" => $throwable->getCode(),
                "error" => $throwable->getMessage(),
                "trace" => $throwable->getTraceAsString(),
            ]);

            $logger->critical("Exception : " . json_encode($errors));

        }, get_class($throwable) . " ---> " . __METHOD__);


    }

}