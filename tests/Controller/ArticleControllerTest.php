<?php

namespace App\Tests\Controller;

use App\Entity\Article;
use App\Factory\DtoFactory;
use App\Service\ArticleService;
use App\Service\Impl\ArticleServiceImpl;
use App\Stubs\ArticleStub;
use App\Tests\ArticleAppBaseTestCase;
use JWT\Authentication\JWT;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEmpty;

class ArticleControllerTest extends ArticleAppBaseTestCase
{
    const BASE_API = "api/v1/articles";
    private ArticleService $articleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articleService = $this->getService(ArticleServiceImpl::class);
    }

    public static function securedEndpointDataProvider(): array
    {
        return [
            "Create article" => [
                "action" => self::BASE_API . "/",
                "method" => "POST",
                "data" => $data,
            ],
            "Update article" => [
                "action" => self::BASE_API . "/1",
                "method" => "PUT",
                "data" => $data,
            ],
            "Delete article" => [
                "action" => self::BASE_API . "/1",
                "method" => "DELETE",
                "data" => $data,
            ],
        ];
    }

    public static function dataWithSecuredEndpointDataProvider(): array
    {
        $data = [
            "title" => "title",
            "description" => "description",
            "permalink" => "permalink",
            "publishedAt" => "2020-06-12 16:40:15",
            "sourceName" => "sourceName",
            "authorName" => "authorName",
            "imageUrl" => "imageUrl",
            "externalRef" => "externalRef",
        ];

        return [
            "Create article" => [
                "action" => self::BASE_API . "/",
                "method" => "POST",
                "data" => $data,
            ],
            "Update article" => [
                "action" => self::BASE_API . "/",
                "method" => "PUT",
                "data" => $data,
            ],
            "Delete article" => [
                "action" => self::BASE_API . "/",
                "method" => "DELETE",
                "data" => $data,
            ],
        ];
    }
    public static function noSecuredEndpointDataProvider(): array
    {
        return [
            "Articles list" => [
                "action" => self::BASE_API . "/",
                "method" => "GET",
                "createArticle" => false,
            ],
            "Article by ID" => [
                "action" => self::BASE_API . "/",
                "method" => "GET",
                "createArticle" => true,
            ],
        ];
    }

    /**
     * @dataProvider securedEndpointDataProvider
     * @param string $action
     * @param string $method
     * @return void
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function test_article_secured_endpoint_WITHOUT_login_to_api_THEN_expected_error(string $action, string $method): void
    {
        $endpoint = sprintf("%s/%s", $this->givenAppUrl(), $action);
        $response = HttpClient::create()->request($method, $endpoint);

        assertEquals(401, $response->getStatusCode());
        assertEquals(["error" => "Full authentication is required to access this resource."], $response->toArray(false));
    }


    /**
     * @dataProvider noSecuredEndpointDataProvider
     * @param string $action
     * @param string $method
     * @param bool $createArticle
     * @return void
     * @throws Throwable
     */
    public function test_article_no_secured_endpoint_WITHOUT_login_to_api_THEN_expected_200_status(string $action, string $method, bool $createArticle): void
    {
        if ($createArticle) {
            $articleDto = ArticleStub::loadArticles()[0];
            $articleDto = $this->articleService->createArticle($articleDto);
            assertNotEmpty($articleDto->id);

            $action .= $articleDto->id;
        }

        $endpoint = sprintf("%s/%s", $this->givenAppUrl(), $action);
        $response = HttpClient::create()->request($method, $endpoint);

        assertEquals(200, $response->getStatusCode());
    }


    /**
     * @dataProvider dataWithSecuredEndpointDataProvider
     * @param string $action
     * @param string $method
     * @param array $data
     * @return void
     * @throws Throwable
     */
    public function test_article_secured_endpoint_WITH_login_to_api_THEN_expected_200_status_AND_CUD_success(
        string $action,
        string $method,
        array $data,
    ): void
    {
        $articleExpected = DtoFactory::createArticleDto($data);

        // Create the user and generate accessToken
        if ($method == "DELETE") {
            $user = $this->givenUserWithRoleAdmin();
        } else {
            $user = $this->givenUserWithRoleUser();
        }
        $user->setAccessToken(JWT::encode(["email" => $user->getEmail(), "roles" => $user->getRoles()], "fake_key"));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $endpoint = sprintf("%s/%s", $this->givenAppUrl(), $action);

        if ($method == "PUT" || $method == "DELETE") {
            // First create article to update OR to delete
            $response = HttpClient::create()->request("POST", $endpoint, [
                "headers" => ["Authorization" => "Bearer " . $user->getAccessToken()],
                "body" => $data,
            ]);
            assertEquals(200, $response->getStatusCode());
            $articleCreated = DtoFactory::createArticleDto($response->toArray(false));

            $action .= $articleCreated->id;
            $endpoint = sprintf("%s/%s", $this->givenAppUrl(), $action);

            if ($method == "PUT") {
                // And prepare the updating of the data and article expected
                $data["title"] = $articleExpected->title = "Updated success";
            }
        }


        // call secured endpoint
        $response = HttpClient::create()->request($method, $endpoint, [
            "headers" => ["Authorization" => "Bearer " . $user->getAccessToken()],
            "body" => $data,
        ]);
        assertEquals(200, $response->getStatusCode());

        if ($method != "DELETE") {
            $articleSaved = DtoFactory::createArticleDto($response->toArray(false));
            $articleSavedId = $articleSaved->id;
            $articleSaved->id = null;
            assertEquals($articleExpected, $articleSaved);

            // Remove entity
            $article = $this->entityManager->getRepository(Article::class)->find($articleSavedId);
            $this->entityManager->remove($article);
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        } else {
            assertEquals("Article is deleted", $response->getContent());
        }

    }

}
