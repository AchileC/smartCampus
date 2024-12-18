<?php

namespace App\EventListener;

use App\Repository\NotificationRepository;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationListener
{
    private NotificationRepository $notificationRepository;
    private Environment $twig;
    private Security $security;

    public function __construct(NotificationRepository $notificationRepository, Environment $twig, Security $security)
    {
        $this->notificationRepository = $notificationRepository;
        $this->twig = $twig;
        $this->security = $security;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Vérifie si l'utilisateur est authentifié
        $user = $this->security->getUser();

        // Si l'utilisateur n'est pas connecté, ne rien faire
        if (!$user) {
            $this->twig->addGlobal('notifications', []);
            return;
        }

        // Récupère les notifications non lues de l'utilisateur
        $notifications = $this->notificationRepository->findBy([
            'recipient' => $user,
            'isRead' => false
        ]);

        // Ajoute les notifications globalement pour Twig
        $this->twig->addGlobal('notifications', $notifications);
    }
}
