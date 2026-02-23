<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notifications_list')]
    public function list(): Response
    {
        return $this->render('notification/list.html.twig');
    }

    #[Route('/new', name: 'app_notification_new')]
    public function new()
    {
        return $this->render('notification/new.html.twig');
    }
}
