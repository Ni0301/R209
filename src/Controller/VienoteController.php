<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VienoteController extends AbstractController
{
    #[Route('/vienote', name: 'app_vienote')]
    public function index(): Response
    {
        return $this->render('vienote/index.html.twig', [
            'controller_name' => 'VienoteController',
        ]);
    }
}
