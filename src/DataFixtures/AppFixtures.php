<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Exception\SavingException;
use App\Service\ArticleService;
use App\Stubs\ArticleStub;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use JWT\Authentication\JWT;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly TagAwareCacheInterface $tagAwareCache
    )
    {
    }

    /**
     * @param ObjectManager $manager
     * @return void
     * @throws InvalidArgumentException
     * @throws SavingException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        // Create users
        $user = new User();
        $user->setEmail("user@gmail.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setAccessToken(JWT::encode(["email" => $user->getEmail(), "roles" => $user->getRoles()], "fake_key"));
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "user"));
        $manager->persist($user);

        $userAdmin = new User();
        $userAdmin->setEmail("admin@gmail.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setAccessToken(JWT::encode(["email" => $userAdmin->getEmail(), "roles" => $userAdmin->getRoles()], "fake_key"));
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "admin"));
        $manager->persist($userAdmin);

        $manager->flush();


        // Create articles
        $articles = ArticleStub::loadArticles(500);
        foreach ($articles as $articleDto) {
            $this->articleService->createArticle($articleDto);
        }

        try {
            $this->tagAwareCache->invalidateTags([$this->articleService->getCacheTag()]);
        } catch (Exception) {}
    }
}
