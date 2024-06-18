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
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;

class LemondeArticleGatewayRepositoryImpl implements ArticleGatewayRepository
{
    const SOURCE = "Lemonde";

    public function __construct(
        private readonly ProcessTracker $processTracker,
    )
    {
    }

    public function loadArticles(): array
    {
        return $this->processTracker->start(function () {
            return $this->extractArticle($this->getArticles());
        }, "[Lemonde API]");
    }

    /**
     * @throws MissingRequireFieldException
     * @throws InvalidArgumentException
     */
    private function extractArticle($documentXml): array
    {
        if (empty($documentXml)) {
            return [];
        }

        try {
            $encoders = [new XmlEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $documentXml = $serializer->decode($documentXml, "xml")["channel"]["item"] ?? [];
        } catch (Exception) {
            return [];
        }


        $fieldsToCheck = [
            'title',
            'description',
            'link',
            'pubDate',
            'media:content',
        ];
        $articles = [];
        foreach ($documentXml as $item) {
            if (Tools::hasEmptyField($item, $fieldsToCheck)) {
                continue;
            }

            // Check to extract ImageUrl value from '@url' key
            if (Tools::hasEmptyField($item["media:content"], ["@url", "media:credit"])) {
                continue;
            }

            // Check to extract Author value from '#' key
            if (Tools::hasEmptyField($item["media:content"]["media:credit"], ["#"])) {
                continue;
            }

            /** @var DateTime|null $publishedAt */
            $publishedAt = null;
            try {
                $publishedAt = new DateTime($item['pubDate']);
            } catch (Exception) {
                continue;
            }

            $articles[] = DtoFactory::createArticleDto([
                "title" => $item["title"],
                "description" => $item["description"],
                "permalink" => $item["link"],
                "sourceName" => self::SOURCE,
                "publishedAt" => $publishedAt,
                "authorName" => $item["media:content"]["media:credit"]["#"],
                "imageUrl" => $item["media:content"]["@url"],
            ]);
        }

        return $articles;
    }

    private function getArticles(): string
    {
        try {
            $request = AbstractHttpClient::create($this->processTracker->getLogger())
                ->request(Request::METHOD_GET, "https://www.lemonde.fr/rss/une.xml");

            if (200 !== $request->getStatusCode()) {
                throw new Exception("Request status code: " . $request->getStatusCode());
            }

            return $request->getContent();

        } catch (Throwable $throwable) {
            $this->processTracker->getLogger()->error($throwable->getMessage());
        }

        return "";
    }
}