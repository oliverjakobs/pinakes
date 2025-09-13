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
        return $this->renderListFilter($request, $repository, 'Users');
    }

    #[Route('/icons', name: 'icons', methods: ['GET'])]
    public function icons(Request $request): Response {
        [ $query, $filter_only ] = $this->getQueryFilter($request->query->all());
        $filter = array_merge(self::DEFAULT_FILTER, $query);

        $icons = glob('./icons/bootstrap/*' . ($filter['search'] ?? '') . '*.svg');
        $icons = array_map(fn ($path) => basename($path, '.svg'), $icons);

        if ($filter_only) {
            $response = $this->render('icons.html.twig', [
                'data' => $icons,
                'filter' => $filter
            ]);
            return $this->pushFilterUrl($response, $request, $filter);
        }

        return $this->render('list.html.twig', [
            'title' => 'Icons',
            'filter' => $filter,
            'navigation' => $this->getNavigationItems(),
            'data' => $icons,
            'component_path' => 'component/icons.html.twig'
        ]);
    }

}
