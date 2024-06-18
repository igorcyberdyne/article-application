<?php

namespace App\Tests;

use App\Kernel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

abstract class ArticleAppBaseTestCase extends KernelTestCase
{
    protected static ContainerInterface $containerInterface;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        // (1) boot the Symfony kernel. See phpunit.xml.dist for config
        $_SERVER["DATABASE_URL"] = $_ENV["DATABASE_URL"] = "mysql://root@127.0.0.1:3306/article_app";
        self::bootKernel();


        //static::$containerInterface = self::$kernel->getContainer();
        static::$containerInterface = new AppTestContainer(static::$kernel);
        $this->entityManager = $this->getService(EntityManagerInterface::class);
    }


    protected function getContainerInterface(): ContainerInterface
    {
        return static::$containerInterface;
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