<?php

namespace App\Tools;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class HttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly array                      $defaultOptions,
        private readonly HttpClientInterface        $client,
        private readonly ?LoggerInterface $logger
    )
    {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $options = array_merge($this->defaultOptions, $options);

        $this->logger?->info("[HttpRequest] --> " . json_encode([
                "method" => $method,
                "url" => $url,
                "body" => $options["body"] ?? [],
                "headers" => $options["headers"] ?? [],
            ])
        );

        return $this->client->request($method, $url, $options);
    }

    public function stream(iterable|ResponseInterface $responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client->withOptions($options);

        return $clone;
    }

}