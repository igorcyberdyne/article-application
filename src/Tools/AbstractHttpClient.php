<?php

namespace App\Tools;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;

abstract class AbstractHttpClient
{
    /**
     * @param array $defaultOptions Default request's options
     * @param int $maxHostConnections The maximum number of connections to a single host
     * @param int $maxPendingPushes The maximum number of pushed responses to accept in the queue
     *
     * @see HttpClientInterface::OPTIONS_DEFAULTS for available options
     * @throws Exception
     */
    public static function create(
        ?LoggerInterface $logger,
        array $defaultOptions = [],
        int $maxHostConnections = 6,
        int $maxPendingPushes = 50,
    ): HttpClientInterface
    {
        return new HttpClient(
            $defaultOptions,
            SymfonyHttpClient::create($defaultOptions, $maxHostConnections, $maxPendingPushes),
            $logger
        );
    }
}