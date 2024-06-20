<?php

namespace App\Service;

use App\DTO\ArticleDto;
use App\Enum\ArrayOrder;
use App\Exception\MissingRequireFieldException;
use App\Exception\RemovingException;
use App\Exception\ResourceNotFoundException;
use App\Exception\SavingException;
use App\Exception\SearchException;
use InvalidArgumentException;

interface ArticleService
{
    /**
     * @param ArticleDto $articleDto
     * @return ArticleDto
     * @throws SavingException
     */
    public function createArticle(ArticleDto $articleDto): ArticleDto;

    /**
     * @param ArticleDto $articleDto
     * @return ArticleDto
     * @throws SavingException
     * @throws ResourceNotFoundException
     */
    public function updateArticle(ArticleDto $articleDto): ArticleDto;

    /**
     * @param int $articleId
     * @return void
     * @throws ResourceNotFoundException
     * @throws RemovingException
     */
    public function deleteArticle(int $articleId): void;

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
    public function retrieveArticles(?array $criteria = [], ?int $limit = null, ?int $offset = null, ArrayOrder $order = ArrayOrder::DESC): array;

    /**
     * @param array|null $criteria
     * @return int
     * @throws SearchException
     */
    public function countArticles(?array $criteria): int;

    /**
     * @param int $id
     * @return ArticleDto
     * @throws ResourceNotFoundException
     */
    public function getArticleById(int $id): ArticleDto;

}