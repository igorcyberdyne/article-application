<?php

namespace App\Gateway\ArticleGatewayImpl;

use App\Exception\MissingRequireFieldException;
use App\Factory\DtoFactory;
use App\Gateway\ArticleGatewayRepository;
use App\Service\ProcessTracker;
use App\Tools\AbstractHttpClient;
use App\Tools\Tools;
use DateTime;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class SauravArticleGatewayRepositoryImpl implements ArticleGatewayRepository
{
    public function __construct(
        private readonly ProcessTracker $processTracker,
    )
    {
    }

    public function loadArticles(): array
    {
        return $this->processTracker->start(function () {
            return $this->extractArticle($this->getArticles());
        }, "[saurav.tech API]");
    }

    /**
     * @throws MissingRequireFieldException
     * @throws InvalidArgumentException
     */
    private function extractArticle($items): array
    {
        if (empty($items) || !is_array($items) || !is_array($items["articles"])) {
            return [];
        }

        $fieldsToCheck = [
            'title',
            'description',
            'url',
            'urlToImage',
            'publishedAt',
            'source',
            'author',
        ];
        $articles = [];
        $items = $items["articles"];
        foreach ($items as $item) {

            if (Tools::hasEmptyField($item, $fieldsToCheck)) {
                continue;
            }

            if (empty($item["source"]["name"])) {
                continue;
            }

            /** @var DateTime|null $publishedAt */
            $publishedAt = null;
            try {
                $publishedAt = new DateTime($item['publishedAt']);
            } catch (Exception) {
                continue;
            }

            $articles[] = DtoFactory::createArticleDto([
                "title" => $item["title"],
                "description" => $item["description"],
                "permalink" => $item["url"],
                "sourceName" => $item["source"]["name"],
                "publishedAt" => $publishedAt,
                "authorName" => $item["author"],
                "imageUrl" => $item["urlToImage"],
            ]);
        }

        return $articles;
    }

    /**
     * @return array
     */
    private function getArticles(): array
    {
        try {
            $request = AbstractHttpClient::create($this->processTracker->getLogger())
                ->request(Request::METHOD_GET, "https://saurav.tech/NewsAPI/top-headlines/category/health/fr.json");

            if (200 !== $request->getStatusCode()) {
                throw new Exception("Request status code: " . $request->getStatusCode());
            }

            return $request->toArray();

        } catch (Throwable $throwable) {
            $this->processTracker->getLogger()->error($throwable->getMessage());
        }

        return [];
    }
}