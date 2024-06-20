<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JWT\Authentication\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(UserInterface $userObject, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $userObject;

        if (empty($user->getAccessToken())) {
            $user->setAccessToken(JWT::encode(["email" => $user->getEmail(), "roles" => $user->getRoles()], "fake_key"));

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->json(["token" => $user->getAccessToken()]);
    }
}