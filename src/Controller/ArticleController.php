<?php

namespace App\Controller;

use App\Exception\MissingRequireFieldException;
use App\Exception\RemovingException;
use App\Exception\ResourceNotFoundException;
use App\Exception\SavingException;
use App\Factory\DtoFactory;
use App\Service\ArticleService;
use App\Tools\PaginationProcessor;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;


#[Route(path: '/api/v1/articles', name: 'api_v1_articles_')]
class ArticleController extends AbstractController
{
    public function __construct(
        private readonly ArticleService $articleService
    )
    {
    }

    /**
     * @param RequestStack $requestStack
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    #[Route(path: '/', name: 'list', condition: "context.getMethod() in ['GET']")]
    public function articles(RequestStack $requestStack, TagAwareCacheInterface $cache): JsonResponse
    {
        $query = $requestStack->getCurrentRequest()->query->all();

        $paginatorProcessor = new PaginationProcessor(
            $query["limit"] ?? null,
            $query["offset"] ?? null,
            paginatorUrl: $this->generateUrl("api_v1_articles_list", [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $criteria = [
            "title" => $query["title"] ?? null,
            "authorName" => $query["authorName"] ?? null,
            "sourceName" => $query["sourceName"] ?? null,
        ];

        /** @var CacheItem $cacheItem */
        $cacheItem = $cache->getItem("articles.list_" . $paginatorProcessor->getSearchKey());
        $result = $cacheItem->get() ?? [];

        if (!$cacheItem->isHit()) {
            $paginatorProcessor
                ->setFilter($criteria)
                ->setCount(function (array $filter, int $limit) {
                    return $limit <= 0 ? 0 : $this->articleService->countArticles($filter);
                })
                ->setData(function (array $filter, int $limit, int $offset) {
                    return $this->articleService->retrieveArticles($filter, $limit, $offset);
                })
            ;

            $result = $paginatorProcessor->getResult();

            $cacheItem->tag("articles.list")->set($result);
            $cache->save($cacheItem);
        }

        return $this->json($result);
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @throws ResourceNotFoundException
     */
    #[Route(path: '/{id}', name: 'by_id', condition: "context.getMethod() in ['GET']")]
    public function getArticle(int $id): JsonResponse
    {
        return $this->json($this->articleService->getArticleById($id));
    }


    /**
     * @throws SavingException
     * @throws MissingRequireFieldException
     */
    #[Route(path: '/', name: 'create', condition: "context.getMethod() in ['POST']")]
    #[IsGranted(new Expression('is_granted("ROLE_USER") or is_granted("ROLE_ADMIN")'))]
    public function create(
        RequestStack $requestStack
    ): JsonResponse
    {
        $request = $requestStack->getCurrentRequest()->request;
        $articleDto = DtoFactory::createArticleDto([
            "title" => $request->get('title'),
            "description" => $request->get('description'),
            "permalink" => $request->get('permalink'),
            "sourceName" => $request->get('sourceName'),
            "publishedAt" => $request->get('publishedAt'),
            "authorName" => $request->get('authorName'),
            "imageUrl" => $request->get('imageUrl'),
            "externalRef" => $request->get('externalRef'),
        ]);

        return $this->json($this->articleService->createArticle($articleDto));
    }

    /**
     * @throws SavingException
     * @throws MissingRequireFieldException
     * @throws ResourceNotFoundException
     */
    #[Route(path: '/{id}', name: 'update', condition: "context.getMethod() in ['PUT']")]
    #[IsGranted(new Expression('is_granted("ROLE_USER") or is_granted("ROLE_ADMIN")'))]
    public function update(
        int          $id,
        RequestStack $requestStack
    ): JsonResponse
    {
        $request = $requestStack->getCurrentRequest()->request;

        $articleDto = DtoFactory::createArticleDto([
            "title" => $request->get('title'),
            "description" => $request->get('description'),
            "permalink" => $request->get('permalink'),
            "sourceName" => $request->get('sourceName'),
            "publishedAt" => $request->get('publishedAt'),
            "authorName" => $request->get('authorName'),
            "imageUrl" => $request->get('imageUrl'),
            "externalRef" => $request->get('externalRef'),
            "id" => $id,
        ]);

        return $this->json($this->articleService->updateArticle($articleDto));
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @throws RemovingException
     * @throws ResourceNotFoundException
     */
    #[Route(path: '/{id}', name: 'delete', condition: "context.getMethod() in ['DELETE']")]
    #[IsGranted("ROLE_ADMIN")]
    public function delete(
        int $id,
    ): Response
    {
        $this->articleService->deleteArticle($id);

        return new Response("Article is deleted");
    }
}