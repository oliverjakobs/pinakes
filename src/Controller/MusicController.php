<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Pinakes\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MusicController extends PinakesController {

    #[Route('/music', name: 'music', methods: ['GET'])]
    public function list(Request $request): Response {
        return $this->render('_base.html.twig');
    }

    #[Route('/music/vinyl', name: 'music_vinyl', methods: ['GET'])]
    public function vinyl(Request $request): Response {
        return $this->render('_base.html.twig');
    }

    #[Route('/music/cd', name: 'music_cd', methods: ['GET'])]
    public function cd(Request $request): Response {
        return $this->render('_base.html.twig');
    }
}
