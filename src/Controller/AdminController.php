<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends PinakesController {

    public static function getModelName(): string {
        return 'admin';
    }

    #[Route('/admin', name: 'admin', methods: ['GET'])]
    public function list(Request $request, UserRepository $repository): Response {
        return $this->renderList($request);
    }

    #[Route('/admin/filter', name: 'admin_filter', methods: ['GET'])]
    public function filter(Request $request, UserRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }
}
