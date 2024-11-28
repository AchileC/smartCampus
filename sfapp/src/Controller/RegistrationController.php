<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/', name: 'app_register')]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $user->setEmail('example@example.com');
        $plaintextPassword = 'securepassword123';

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);

        // Définir les rôles de l'utilisateur (par exemple, ROLE_USER par défaut)
        $user->setRoles(['ROLE_USER']);

        // Enregistrez l'utilisateur dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();

        // Redirigez vers la page de connexion après l'inscription
        return $this->redirectToRoute('app_login');
    }
}
