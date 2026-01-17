<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends PinakesController {

    #[Route('/admin', name: 'admin', methods: ['GET'])]
    public function admin(Request $request, UserRepository $repository): Response {
        return $this->renderList($request, $repository, 'Users');
    }

    #[Route('/user', name: 'user', methods: ['GET'])]
    public function list(Request $request, UserRepository $repository): Response {
        return $this->renderList($request, $repository, 'Users');
    }

    #[Route('/icons', name: 'icons', methods: ['GET'])]
    public function icons(Request $request): Response {
        $query = $this->getQueryFilter($request->query->all(), $filter_only);
        $filter = array_merge(self::DEFAULT_FILTER, $query);

        $icons = glob('./icons/bootstrap/*' . ($filter['search'] ?? '') . '*.svg');
        $icons = array_map(function ($path) {
            $name = basename($path, '.svg');
            return [
                'icon' => $name,
                'caption' => $name
            ];
        }, $icons);

        $params = [
            'title' =>'Icons',
            'filter' => $filter,
            'data' => $icons,
            'fields' => [],
            'allow_pagination' => true,
            'allow_ordering' => true,
            'component_path' => 'components/tiled.html.twig'
        ];

        if ($filter_only) {
            $response = $this->render('components/tiled.html.twig', $params);
            return $this->pushFilterUrl($response, $request, $filter);
        }

        return $this->render('list.html.twig', $params);
    }

}
