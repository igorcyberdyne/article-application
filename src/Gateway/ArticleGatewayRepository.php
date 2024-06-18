<?php

namespace App\Gateway;

use App\DTO\ArticleDto;
use Throwable;

interface ArticleGatewayRepository
{
    /**
     * @return ArticleDto[]|array
     * @throws Throwable
     */
    public function loadArticles(): array;

}