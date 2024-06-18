<?php

namespace App\Mapper;

use App\DTO\ArticleDto;
use App\Entity\Article;
use App\Exception\MissingRequireFieldException;
use App\Factory\DtoFactory;
use InvalidArgumentException;

abstract class ArticleMapper
{
    /**
     * @param ArticleDto $articleDto
     * @return Article
     */
    public static function mapToArticle(ArticleDto $articleDto): Article
    {
        $article = new Article();
        self::hydrateArticleFromDto($article, $articleDto);

        return $article;
    }

    /**
     * @param Article $article
     * @return ArticleDto
     * @throws MissingRequireFieldException
     * @throws InvalidArgumentException
     */
    public static function mapToArticleDto(Article $article): ArticleDto
    {

        return DtoFactory::createArticleDto([
            "title" => $article->getTitle(),
            "description" => $article->getDescription(),
            "permalink" => $article->getPermalink(),
            "sourceName" => $article->getSourceName(),
            "publishedAt" => $article->getPublishedAt(),
            "authorName" => $article->getAuthorName(),
            "imageUrl" => $article->getImageUrl(),
            "externalRef" => $article->getExternalRef(),
            "id" => $article->getId(),
        ]);
    }

    /**
     * @param Article $article
     * @param ArticleDto $articleDto
     * @return void
     */
    public static function hydrateArticleFromDto(Article $article, ArticleDto $articleDto): void
    {
        $article->setTitle($articleDto->title);
        $article->setDescription($articleDto->description);
        $article->setSourceName($articleDto->sourceName);
        $article->setAuthorName($articleDto->authorName);
        $article->setPermalink($articleDto->permalink);
        $article->setImageUrl($articleDto->imageUrl);
        $article->setExternalRef($articleDto->externalRef);
        $article->setPublishedAt($articleDto->publishedAt);
    }
}