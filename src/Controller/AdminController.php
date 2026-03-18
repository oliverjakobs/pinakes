<?php declare(strict_types=1);

namespace App\Controller;

use App\Pinakes\DataTable;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends PinakesController {

    #[Route('/admin', name: 'admin', methods: ['GET'])]
    public function admin(Request $request, UserRepository $repository): Response {
        return $this->renderList($request, 'Users', $repository->createTable());
    }

    #[Route('/user', name: 'user', methods: ['GET'])]
    public function list(Request $request, UserRepository $repository): Response {
        return $this->renderList($request, 'Users', $repository->createTable());
    }

    #[Route('/icons', name: 'icons', methods: ['GET'])]
    public function icons(Request $request, #[MapQueryParameter] ?string $search = ''): Response {
        $icons = glob('./icons/bootstrap/*' . $search . '*.svg');
        $icons = array_map(function ($path) {
            $name = basename($path, '.svg');
            return [
                'icon' => $name,
                'caption' => $name
            ];
        }, $icons);

        $table = DataTable::fromData($icons)->setComponentPath('components/icons.html.twig');

        return $this->renderList($request, 'Icons', $table);
    }

}
