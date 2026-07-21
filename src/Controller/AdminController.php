<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
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

    #[Route('/user/modal/{id}', name: 'user_modal', methods: ['GET', 'POST'])]
    public function modalTransaction(Request $request, UserRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        $entity = $this->getEntity($request, $repository) ?? $repository->create();
        return $this->renderModal($request, $repository, $entity, 'user');
    }

    #[Route('/user/delete/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request, UserRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        $entity = $this->getEntity($request, $repository);
        return $this->deleteEntityAndRedirect($request, $repository, $entity, 'user');
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

        $table = DataTable::fromData($icons, []);
        $table->component_path = 'components/icons.html.twig';
        return $this->renderList($request, 'Icons', $table);
    }

}
