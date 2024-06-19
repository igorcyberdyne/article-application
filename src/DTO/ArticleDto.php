<?php

namespace App\DTO;

use DateTime;
use Stringable;

class ArticleDto implements Stringable
{
    /**
     * @param int|null $id
     * @param string $title
     * @param string $description
     * @param string $permalink
     * @param DateTime|null $publishedAt
     * @param string $sourceName
     * @param string|null $authorName
     * @param string|null $imageUrl
     * @param string|null $externalRef
     */
    public function __construct(
        public string    $title,
        public string    $description,
        public string    $permalink,
        public string    $sourceName,
        public ?DateTime $publishedAt = null,
        public ?string   $authorName = null,
        public ?string   $imageUrl = null,
        public ?string   $externalRef = null,
        public ?int      $id = null,
    )
    {
    }

    public function __toString()
    {
        return json_encode([
                "title" => $this->title,
                "description" => $this->description,
                "permalink" => $this->permalink,
                "sourceName" => $this->sourceName,
                "publishedAt" => $this->publishedAt?->format("Y-m-d H:i:s"),
                "authorName" => $this->authorName,
                "imageUrl" => $this->imageUrl,
                "externalRef" => $this->externalRef,
                "id" => $this->id,
            ]
        );
    }
}