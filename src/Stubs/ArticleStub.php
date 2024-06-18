<?php

namespace App\Stubs;

use App\DTO\ArticleDto;
use App\Factory\DtoFactory;
use Exception;

abstract class ArticleStub
{

    /**
     * @return ArticleDto[]|array
     * @throws Exception
     */
    public static function loadArticles($limit = 2): array
    {
        $article = [
            "title" => "title",
            "description" => "description",
            "permalink" => "permalink",
            "publishedAt" => "2020-06-12 16:40:15",
            "sourceName" => "sourceName",
            "authorName" => "authorName",
            "imageUrl" => "imageUrl",
            "externalRef" => "externalRef",
        ];

        $list = [];
        for ($i = 1; $i <= $limit; $i++) {
            $list[] = DtoFactory::createArticleDto([
                "title" => "$i + " . $article["title"],
                "description" => "$i + " . $article["description"],
                "permalink" => "$i + " . $article["permalink"],
                "sourceName" => "$i + " . $article["sourceName"],
                "publishedAt" => $article["publishedAt"],
                "authorName" => "$i + " . $article["authorName"],
                "imageUrl" => "$i + " . $article["imageUrl"],
                "externalRef" => "$i + " . $article["externalRef"],
            ]);
        }

        return $list;
    }
}