<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class IndexController extends AbstractController {

    #[Route('/', name: 'pinakes')]
    public function index(): Response {
        $navigation = [
            [
                'icon' => 'book',
                'route' => 'book',
                'caption' => 'Books',
                'role' => ''
            ],
            [
                'icon' => 'vector-pen',
                'route' => 'author',
                'caption' => 'Authors',
                'role' => ''
            ],
            [
                'icon' => 'send',
                'route' => 'publisher',
                'caption' => 'Publishers',
                'role' => ''
            ],
            [
                'icon' => 'bookmark',
                'route' => 'series',
                'caption' => 'Series',
                'role' => ''
            ],
            [
                'icon' => 'bank',
                'route' => 'bookfund',
                'caption' => 'Bookfund',
                'role' => User::ROLE_LIBRARIAN
            ],
            [
                'icon' => 'database',
                'route' => 'admin',
                'caption' => 'Admin',
                'role' => User::ROLE_ADMIN
            ],
        ];
        
        return $this->render('index.html.twig', [
            'navigation' => $navigation
        ]);
    }
}
