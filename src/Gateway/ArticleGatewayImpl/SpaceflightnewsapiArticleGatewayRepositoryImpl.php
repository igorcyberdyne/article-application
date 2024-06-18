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

class SpaceflightnewsapiArticleGatewayRepositoryImpl implements ArticleGatewayRepository
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
        }, "[Space flight API]");
    }


    /**
     * @throws MissingRequireFieldException
     * @throws InvalidArgumentException
     */
    private function extractArticle($items): array
    {
        if (empty($items) || !is_array($items)) {
            return [];
        }

        $fieldsToCheck = [
            'id',
            'title',
            'summary',
            'url',
            'imageUrl',
            'publishedAt',
            'newsSite',
        ];
        $articles = [];
        foreach ($items as $item) {
            if (Tools::hasEmptyField($item, $fieldsToCheck)) {
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
                "description" => $item["summary"],
                "permalink" => $item["url"],
                "sourceName" => $item["newsSite"],
                "publishedAt" => $publishedAt,
                "imageUrl" => $item["imageUrl"],
                "externalRef" => $item["id"],
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
                ->request(Request::METHOD_GET, "https://api.spaceflightnewsapi.net/v3/articles");

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