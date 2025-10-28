<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends PinakesController {

    #[Route('/user', name: 'user', methods: ['GET'])]
    public function list(Request $request, UserRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Users');
    }

    #[Route('/icons', name: 'icons', methods: ['GET'])]
    public function icons(Request $request): Response {
        [ $query, $filter_only ] = $this->getQueryFilter($request->query->all());
        $filter = array_merge(self::DEFAULT_FILTER, $query);

        $icons = glob('./icons/bootstrap/*' . ($filter['search'] ?? '') . '*.svg');
        $icons = array_map(function ($path) {
            $name = basename($path, '.svg');
            return [
                'icon' => $name,
                'caption' => $name
            ];
        }, $icons);

        if ($filter_only) {
            $response = $this->render('components/tiled.html.twig', [
                'data' => $icons,
                'filter' => $filter
            ]);
            return $this->pushFilterUrl($response, $request, $filter);
        }

        return $this->render('list.html.twig', [
            'title' => 'Icons',
            'filter' => $filter,
            'data' => $icons,
            'component_path' => 'components/tiled.html.twig'
        ]);
    }

}
