<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PinakesController extends AbstractController
{
    #[Route('/', name: 'pinakes')]
    public function index(): Response
    {
        return $this->render('pinakes.html.twig');
    }
}
