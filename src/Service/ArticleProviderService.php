<?php

namespace App\Service;

use App\Gateway\ArticleGatewayImpl\LemondeArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayImpl\SauravArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayImpl\SpaceflightnewsapiArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ArticleProviderService
{
    /** @var ArticleGatewayRepository[]|array */
    protected array $providers = [];
    public function __construct(
        private readonly ContainerInterface $container,
    )
    {
        array_push(
            $this->providers,
            $this->container->get(LemondeArticleGatewayRepositoryImpl::class),
            $this->container->get(SauravArticleGatewayRepositoryImpl::class),
            $this->container->get(SpaceflightnewsapiArticleGatewayRepositoryImpl::class),
        );
    }

    /**
     * @return array|ArticleGatewayRepository[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}