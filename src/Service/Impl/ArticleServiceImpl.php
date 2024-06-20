<?php

namespace App\Service\Impl;

use App\DTO\ArticleDto;
use App\Entity\Article;
use App\Enum\ArrayOrder;
use App\Exception\DomainException;
use App\Exception\MissingRequireFieldException;
use App\Exception\RemovingException;
use App\Exception\ResourceNotFoundException;
use App\Exception\SavingException;
use App\Exception\SearchException;
use App\Mapper\ArticleMapper;
use App\Repository\ArticleRepository;
use App\Service\ArticleService;
use App\Service\ProcessTracker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Throwable;

class ArticleServiceImpl implements ArticleService
{
    private EntityRepository|ArticleRepository $articleRepository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProcessTracker         $processTracker,
        private readonly TagAwareCacheInterface $tagAwareCache
    )
    {
        $this->articleRepository = $this->entityManager->getRepository(Article::class);
    }

    public function createArticle(ArticleDto $articleDto): ArticleDto
    {
        $article = ArticleMapper::mapToArticle($articleDto);

        return $this->save($article);
    }

    public function updateArticle(ArticleDto $articleDto): ArticleDto
    {
        $oldArticle = $this->findArticle($articleDto->id ?? 0);

        ArticleMapper::hydrateArticleFromDto($oldArticle, $articleDto);

        return $this->save($oldArticle);
    }

    public function deleteArticle(int $articleId): void
    {
        $article = $this->findArticle($articleId);

        try {
            $this->entityManager->remove($article);
            $this->entityManager->flush();

            # TODO dispatch event
        } catch (Exception $exception) {
            throw new RemovingException("Error on deleting article", $exception->getCode(), $exception);
        }
    }


    /**
     * @param array|null $criteria
     * @param int|null $limit
     * @param int|null $offset
     * @param ArrayOrder $order
     * @return ArticleDto[]|array
     * @throws MissingRequireFieldException
     * @throws InvalidArgumentException
     * @throws SearchException
     */
    public function retrieveArticles(?array $criteria = [], ?int $limit = null, ?int $offset = null, ArrayOrder $order = ArrayOrder::DESC): array
    {
        if ($limit === 0) {
            return [];
        }

        try {
            $articles = $this->articleRepository->findAllBy($criteria, $limit, $offset, $order->value);
        } catch (Throwable $exception) {
            if ($exception instanceof DomainException) {
                throw $exception;
            }

            throw new SearchException(code: $exception->getCode(), previous: $exception);
        }

        return array_map(function (Article $article) {
            return ArticleMapper::mapToArticleDto($article);
        }, $articles);
    }


    /**
     * @param array|null $criteria
     * @return int
     * @throws SearchException
     */
    public function countArticles(?array $criteria): int
    {
        try {
            return $this->articleRepository->countAllBy($criteria);
        } catch (Throwable $exception) {
            if ($exception instanceof DomainException) {
                throw $exception;
            }

            throw new SearchException(code: $exception->getCode(), previous: $exception);
        }
    }

    /**
     * @param int $id
     * @return ArticleDto
     * @throws ResourceNotFoundException
     * @throws MissingRequireFieldException
     * @throws InvalidArgumentException
     */
    public function getArticleById(int $id): ArticleDto
    {
        return ArticleMapper::mapToArticleDto($this->findArticle($id));
    }

    /**
     * @throws SavingException
     */
    private function save(Article $article): ArticleDto
    {
        try {
            $this->entityManager->persist($article);
            $this->entityManager->flush();


            try {
                $this->tagAwareCache->invalidateTags(["articles.list"]);
            } catch (Throwable) {}

            return ArticleMapper::mapToArticleDto($article);
        } catch (Exception $exception) {
            try {
                $this->processTracker->getLogger()->critical(ArticleMapper::mapToArticleDto($article));
            } catch (Throwable $exception) {
            }
            throw new SavingException("Error on saving article", $exception->getCode(), $exception);
        }
    }

    /**
     * @throws ResourceNotFoundException
     */
    protected function findArticle(int $id): Article
    {
        $article = $this->articleRepository->find($id);

        if (empty($article)) {
            throw new ResourceNotFoundException();
        }

        return $article;
    }
}