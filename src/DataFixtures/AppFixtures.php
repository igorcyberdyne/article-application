<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\ArticleService;
use App\Stubs\ArticleStub;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly UserPasswordHasherInterface $userPasswordHasher
    )
    {
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        // Create users
        $user = new User();
        $user->setEmail("user@gmail.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "user"));
        $manager->persist($user);

        $userAdmin = new User();
        $userAdmin->setEmail("admin@gmail.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "admin"));
        $manager->persist($userAdmin);

        $manager->flush();


        // Create articles
        $articles = ArticleStub::loadArticles();
        foreach ($articles as $articleDto) {
            $this->articleService->createArticle($articleDto);
        }
    }
}
