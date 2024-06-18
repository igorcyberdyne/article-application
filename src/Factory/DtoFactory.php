<?php

namespace App\Factory;

use App\DTO\ArticleDto;
use App\Exception\MissingRequireFieldException;
use App\Tools\Tools;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;

abstract class DtoFactory
{
    /**
     * @throws MissingRequireFieldException
     * @throws InvalidArgumentException
     */
    public static function createArticleDto(array $item): ArticleDto {

        if (Tools::hasEmptyField($item, ["title", "description", "permalink", "sourceName",])) {
            throw new MissingRequireFieldException();
        }

        try {
            $publishedAt = !empty($item['publishedAt']) ? ($item['publishedAt'] instanceof DateTimeInterface  ? $item['publishedAt'] : new DateTime($item['publishedAt'])) : null;
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return new ArticleDto(
            $item["title"],
            $item["description"],
            $item["permalink"],
            $item["sourceName"],
            $publishedAt,
            $item["authorName"] ?? null,
            $item["imageUrl"] ?? null,
            $item["externalRef"] ?? null,
            $item["id"] ?? null,
        );
    }
}