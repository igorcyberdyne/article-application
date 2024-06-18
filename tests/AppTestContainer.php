<?php

namespace App\Tests;

use ReflectionException;
use ReflectionObject;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer as BaseTestContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AppTestContainer extends BaseTestContainer
{
    private ContainerInterface $publicContainer;

    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel, "test.private_services_locator");
        $this->setPublicContainer($kernel->getContainer());
    }

    /**
     * @throws ReflectionException
     */
    public function set($id, $service): void
    {
        $r = new ReflectionObject($this->publicContainer);
        $p = $r->getProperty('services');
        $p->setAccessible(true);

        $services = $p->getValue($this->publicContainer);

        $services[$id] = $service;

        $p->setValue($this->publicContainer, $services);
    }


    private function setPublicContainer($container): void
    {
        $this->publicContainer = $container;
    }
}