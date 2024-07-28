<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController {

    #[Route('/', name: 'pinakes')]
    public function index(): Response {
        $cards = array(
            'paper' => [
                'title' => 'Papers',
                'icon' => '&#128463;'
            ],
            'book' => [
                'title' => 'Books',
                'icon' => '&#128366;'
            ],
            'author' => [
                'title' => 'Authors',
                'icon' => '&#9997;'
            ],
        );

        return $this->render('index.html.twig', [
            'cards' => $cards
        ]);
    }
}
