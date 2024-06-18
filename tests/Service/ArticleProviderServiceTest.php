<?php

namespace App\Tests\Service;

use App\Gateway\ArticleGatewayImpl\LemondeArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayImpl\SauravArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayImpl\SpaceflightnewsapiArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayRepository;
use App\Service\ArticleProviderService;
use App\Tests\ArticleAppBaseTestCase;

class ArticleProviderServiceTest extends ArticleAppBaseTestCase
{
    public function test_provide_article_provider()
    {
        /** @var ArticleProviderService $service */
        $service = $this->getService(ArticleProviderService::class);

        $providers = $service->getProviders();

        $classNames = [];
        self::assertCount(3, $providers);
        foreach ($providers as $provider) {
            self::assertInstanceOf(ArticleGatewayRepository::class, $provider);
            $name = get_class($provider);
            $classNames[$name] = $name;
        }

        self::assertCount(3, $classNames, "Expected a distinct className, see your provider service");
        $classes = [LemondeArticleGatewayRepositoryImpl::class, SauravArticleGatewayRepositoryImpl::class, SpaceflightnewsapiArticleGatewayRepositoryImpl::class];
        foreach ($classNames as $name) {
            self::assertTrue(in_array($name, array_values($classes)));
        }

    }
}
