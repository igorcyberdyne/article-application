<?php

namespace App\Tests\Service;

use App\Entity\Article;
use App\Enum\ArrayOrder;
use App\Exception\RemovingException;
use App\Exception\ResourceNotFoundException;
use App\Exception\SavingException;
use App\Gateway\ArticleGatewayImpl\LemondeArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayImpl\SauravArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayImpl\SpaceflightnewsapiArticleGatewayRepositoryImpl;
use App\Gateway\ArticleGatewayRepository;
use App\Repository\ArticleRepository;
use App\Service\ArticleService;
use App\Service\Impl\ArticleServiceImpl;
use App\Service\ProcessTracker;
use App\Stubs\ArticleStub;
use App\Tests\ArticleAppBaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Throwable;

class ArticleServiceTest extends ArticleAppBaseTestCase
{
    private ArticleService $articleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articleService = $this->getService(ArticleServiceImpl::class);
    }

    /**
     * @return mixed
     * @throws ResourceNotFoundException
     * @throws SavingException
     * @throws Exception
     */
    function givenArticle(): mixed
    {
        $articleDto = ArticleStub::loadArticles()[0];
        $this->articleService->createArticle($articleDto);

        $articleDto = $this->articleService->retrieveArticles(limit: 1)[0];

        $article = $this->articleService->getArticleById($articleDto->id);
        self::assertEquals($articleDto, $article);

        return $articleDto;
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function test_save_newArticle_from_stub_data_source()
    {
        $this->wrapInRollback(function () {

            $articles = ArticleStub::loadArticles();
            foreach ($articles as $article) {
                $this->articleService->createArticle($article);
            }

            $articlesSaved = $this->articleService->retrieveArticles(limit: count($articles));
            self::assertNotEmpty($articlesSaved);
            krsort($articlesSaved);

            $i = 0;
            foreach ($articlesSaved as $articleSaved) {
                self::assertNotEmpty($articleSaved->id);
                $articleSaved->id = null;
                self::assertEquals($articles[$i], $articleSaved);
                $i++;
            }
        });
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function test_update_existing_article_from_db()
    {
        $this->wrapInRollback(function () {
            $article = ArticleStub::loadArticles()[0];
            $articleId = $this->articleService->createArticle($article)->id;

            $oldArticle = $this->articleService->getArticleById($articleId);
            $oldArticle->permalink = "https://google.com";

            $this->articleService->updateArticle($oldArticle);

            $articleUpdated = $this->articleService->getArticleById($oldArticle->id);
            self::assertEquals($oldArticle, $articleUpdated);
        });
    }

    /**
     * @throws Throwable
     */
    public function test_find_article_by_id_THEN_article_found()
    {
        $this->wrapInRollback(function () {
            $this->givenArticle();
        });
    }

    /**
     * @throws Throwable
     */
    public function test_delete_article_THEN_article_deleted()
    {
        $this->wrapInRollback(function () {
            $articleDto = $this->givenArticle();

            // Test delete
            $this->articleService->deleteArticle($articleDto->id);

            $this->expectException(ResourceNotFoundException::class);
            $this->articleService->getArticleById($articleDto->id);
        });
    }

    /**
     * @throws Throwable
     */
    public function test_delete_article_THEN_expected_RemovingException()
    {
        $this->wrapInRollback(function () {
            $articleDto = $this->givenArticle();

            /** @var ArticleRepository $articleRepository */
            $articleRepository = $this->entityManager->getRepository(Article::class);

            /** @var EntityManagerInterface|MockObject $entityManager */
            $entityManager = $this->createMock(EntityManagerInterface::class);
            $entityManager
                ->method('getRepository')
                ->with(Article::class)->willReturn($articleRepository);
            $entityManager
                ->expects($this->once())
                ->method('flush')
                ->willThrowException(new Exception("Error removing article."));

            /** @var ArticleService|MockObject $articleService */
            $articleService = $this
                ->getMockBuilder(get_class($this->articleService))
                ->setConstructorArgs([$entityManager, $this->getService(ProcessTracker::class), $this->getService(TagAwareCacheInterface::class)])
                ->enableProxyingToOriginalMethods()
                ->getMock();

            // Test delete
            $this->expectException(RemovingException::class);
            $articleService->deleteArticle($articleDto->id);
        });
    }

    /**
     * @return void
     * @throws ResourceNotFoundException
     * @throws Exception
     */
    public function test_find_not_exist_article_THEN_expected_exception()
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->articleService->getArticleById(9999999);
    }


    /**
     * @return array[]
     * @throws Exception
     */
    public static function articlesProvider(): array
    {
        return [
            [
                "Gateway to load articles from Saurav" => SauravArticleGatewayRepositoryImpl::class,
            ],
            [
                "Gateway to load articles from Spaceflightnewsapi" => SpaceflightnewsapiArticleGatewayRepositoryImpl::class,
            ],
            [
                "Gateway to load articles from Lemonde" => LemondeArticleGatewayRepositoryImpl::class,
            ],
        ];
    }

    /**
     * @dataProvider articlesProvider
     * @throws Throwable
     */
    public function test_save_newArticle_from_gateway_api($gatewayServiceClassName)
    {
        /** @var ArticleGatewayRepository $articleGatewayRepository */
        $articleGatewayRepository = $this->getService($gatewayServiceClassName);

        $this->wrapInRollback(function () use ($articleGatewayRepository) {

            $articles = $articleGatewayRepository->loadArticles();
            if (empty($articles)) {
                self::expectNotToPerformAssertions();
                return;
            }

            foreach ($articles as $article) {
                $this->articleService->createArticle($article);
            }

            $articlesSaved = $this->articleService->retrieveArticles(limit: count($articles));
            self::assertNotEmpty($articlesSaved);
            krsort($articlesSaved);

            $i = 0;
            foreach ($articlesSaved as $articleSaved) {
                self::assertNotEmpty($articleSaved->id);
                $articleSaved->id = null;
                self::assertEquals($articles[$i], $articleSaved);
                $i++;
            }
        });
    }

}
