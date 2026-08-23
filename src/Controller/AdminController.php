<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Mapper\PinakesMapper;
use App\Pinakes\DataTable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends PinakesController {

    #[Route('/admin', name: 'admin', methods: ['GET'])]
    public function admin(Request $request): Response {
        return $this->list($request);
    }

    #[Route('/user', name: 'user', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PinakesMapper::getMapper(User::class)->createTable('list');
        return $this->renderList2($request, 'Users', $table);
    }

    #[Route('/user/modal/{id}', name: 'user_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $mapper = PinakesMapper::getMapper(User::class);
        $user = $this->getEntityNew($request, $mapper) ?? new User();
        return $this->renderModal2($request, $mapper, $user, 'user');
    }

    #[Route('/user/delete/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $this->getEntityNew($request, User::class);
        PinakesMapper::getMapper(User::class)->deleteEntity($user);
        return $this->redirectHx('user');
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
