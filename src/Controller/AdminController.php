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
        return $this->renderList($request, 'Users', $table);
    }

    #[Route('/user/modal/{id}', name: 'user_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $mapper = PinakesMapper::getMapper(User::class);
        $user = $this->getEntity($request, $mapper) ?? new User();
        return $this->renderModal($request, $mapper, $user, 'user');
    }

    #[Route('/user/delete/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $this->getEntity($request, User::class);
        PinakesMapper::getMapper(User::class)->delete($user);
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

        $component_path = 'components/icon_cards.html.twig';

        list($query, $filter_only) = $this->parseQuery($request);

        $filter = array_merge([
            'page' => 1,
            'pp' => 30,
        ], $query);

        if ($filter_only) {
            $response = $this->render($component_path, [ 
                'data' => $icons,
                'filter' => array_merge($filter, $query)
            ]);
            return $response;
            // return $this->pushFilterUrl($response, $request, $table);
        }

        return $this->render('icons.html.twig', [
            'title' => 'Icons',
            'component_path' => $component_path,
            'data' => $icons,
            'filter' => array_merge($filter, $query)
        ]);
    }

}
