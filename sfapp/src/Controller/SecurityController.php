<?php
//SecurityController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * @brief Handles user authentication and authorization.
 *
 * The SecurityController manages user login, logout, and unauthorized access responses.
 */
class SecurityController extends AbstractController
{

    /**
     * @brief Renders the login page.
     *
     * This method displays the login form and handles any authentication errors.
     *
     * @param AuthenticationUtils $authenticationUtils Provides utilities to retrieve authentication errors.
     *
     * @return Response The rendered login page.
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * @brief Handles user logout.
     *
     * This method is intended to be intercepted by the Symfony security system.
     * It should never be executed directly. If reached, an exception is thrown.
     *
     * @throws \Exception Always thrown to indicate the method should not be called.
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout()
    {
        throw new \Exception('logout() should never be reached');
    }

    /**
     * @brief Renders the unauthorized access page.
     *
     * This method displays a message indicating that the user does not have permission to access a specific page.
     *
     * @return Response The rendered unauthorized access page.
     */
    #[Route('/unauthorized', name: 'app_unauthorized')]
    public function unauthorized(): Response
    {
        return $this->render('security/unauthorized.html.twig', [
            'message' => 'You do not have permission to access this page.',
        ]);
    }
}
