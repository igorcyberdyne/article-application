<?php

namespace App\Tests\Controller;

use App\Tests\ArticleAppBaseTestCase;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEmpty;

class ApiLoginControllerTest extends ArticleAppBaseTestCase
{

    /**
     * @throws Throwable
     */
    public function test_login_user_THEN_expected_token()
    {
        $user = $this->givenUserWithRoleUser(password: "password");
        assertEmpty($user->getAccessToken());

        $response = HttpClient::create()
            ->withOptions(["headers" => ["Content-Type" => "application/json"]])
            ->request("POST", $this->givenAppUrl() . "/api/login", [
                "body" => json_encode(
                    [
                        "username" => $user->getEmail(),
                        "password" => "password",
                    ]
                )
            ]);

        assertEquals(200, $response->getStatusCode());
        assertNotEmpty($response->toArray(false)["token"]);

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
