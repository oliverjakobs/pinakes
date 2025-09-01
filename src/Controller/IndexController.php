<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends PinakesController {

    public static function getModelName(): string {
        return 'book';
    }

    #[Route('/', name: 'pinakes')]
    public function index(Request $request, BookRepository $repository): Response {
        return $this->renderList($request, 'Books', [
            'filter' => $this->getFilter($request->query->all(), [ 'pp' => 20 ])
        ]);
    }

    #[Route('/filter', name: 'pinakes_filter', methods: ['GET'])]
    public function filter(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }
}
