<?php

namespace App\Tests\Controller;

use App\Controller\ArticleController;
use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArticleControllerTest extends WebTestCase
{

    public function stest_articles()
    {
/*
        $_SERVER["DATABASE_URL"] = $_ENV["DATABASE_URL"] = "mysql://root@127.0.0.1:3306/article_app";
        $client = static::createClient();

        $client->request('GET', '/articles', server: ["HTTP_HOST" => "www.article-app.local/api/v1"]);
        $response = $client->getResponse();

        //$this->assertEquals(200, $response->getStatusCode());
        */

    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }


}
