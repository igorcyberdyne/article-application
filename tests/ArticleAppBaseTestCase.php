<?php

namespace App\Tests;

use App\Entity\User;
use App\Kernel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

abstract class ArticleAppBaseTestCase extends KernelTestCase
{
    protected static ContainerInterface $containerInterface;
    protected EntityManagerInterface $entityManager;


    protected function setUp(): void
    {
        parent::setUp();

        // (1) boot the Symfony kernel. See phpunit.xml.dist for config
        self::bootKernel();

        //static::$containerInterface = self::$kernel->getContainer();
        static::$containerInterface = new AppTestContainer(static::$kernel);
        $this->entityManager = $this->getService(EntityManagerInterface::class);
    }


    protected function getContainerInterface(): ContainerInterface
    {
        return static::$containerInterface;
    }


    protected function givenAppUrl()
    {
        return $_ENV["APP_URL"] ?? "http://localhost:8080";
    }

    protected function givenPasswordHasher(): UserPasswordHasherInterface
    {
        return $this->getService(UserPasswordHasherInterface::class);
    }

    public function givenUserWithRoleUser(
        ?string $email = null,
        ?string $password = null,
    ): User
    {
        return $this->createUser(
            !empty($email) ? $email : sprintf("user+%s@gmail.com", uniqid()),
            !empty($password) ? $password : "password",
            "ROLE_USER"
        );
    }

    public function givenUserWithRoleAdmin(
        ?string $email = null,
        ?string $password = null,
    ): User
    {
        return $this->createUser(
            !empty($email) ? $email : sprintf("admin+%s@gmail.com", uniqid()),
            !empty($password) ? $password : "password",
            "ROLE_ADMIN"
        );
    }

    /**
     * @param string $email
     * @param string $password
     * @param string $role
     * @return User
     */
    public function createUser(
        string $email,
        string $password,
        string $role
    ): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        $user->setPassword($this->givenPasswordHasher()->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    /**
     * @throws Exception
     */
    protected function getService($class): object
    {
        $service = self::getContainerInterface()->get($class);

        if (empty($service)) {
            throw new Exception("Service not found");
        }

        return $service;
    }

    protected function replaceService($serviceName, $object): static
    {
        self::getContainerInterface()->set($serviceName, $object);

        return $this;
    }



    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
    }


    /**
     * @param callable $callable
     * @return void
     * @throws Throwable
     */
    protected function wrapInRollback(callable $callable): void
    {
        $message = "Force transaction rollback";
        try {
            $this->entityManager->wrapInTransaction(function (EntityManager $entityManager) use ($message, $callable) {
                $callable($entityManager);
                throw new Exception($message);
            });
        } catch (Throwable $e) {
            if ($message === $e->getMessage()) {
                return;
            }

            throw $e;
        }
    }

}