<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends PinakesController {

    public static function getModelName(): string {
        return 'user';
    }

    #[Route('/user', name: 'user', methods: ['GET'])]
    public function list(Request $request, UserRepository $repository): Response {
        return $this->renderList($request, 'Users');
    }

    #[Route('/user/filter', name: 'user_filter', methods: ['GET'])]
    public function filter(Request $request, UserRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }


    #[Route('/icons', name: 'icons', methods: ['GET'])]
    public function icons(Request $request): Response {
        return $this->renderList($request, 'Icons');
    }

    #[Route('/icons/filter', name: 'icons_filter', methods: ['GET'])]
    public function filterIcons(Request $request): Response {
        $filter = $this->getFilter($request);

        $icons = glob('./icons/bootstrap/*' . ($filter['search'] ?? '') . '*.svg');
        $icons = array_map(fn ($path) => basename($path, '.svg'), $icons);

        $response = $this->render('icons.html.twig', [
            'data' => $icons,
            'filter' => $filter
        ]);
        return $this->pushFilterUrl($response, $request, $filter);
    }

}
