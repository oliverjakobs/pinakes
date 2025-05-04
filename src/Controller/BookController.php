<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends PinakesController {

    public function getModelName(): string {
        return 'book';
    }

    #[Route('/book', name: 'book', methods: ['GET'])]
    public function list(Request $request, BookRepository $repository): Response {
        return $this->renderList($request);
    }

    #[Route('/book/filter', name: 'book_filter', methods: ['GET'])]
    public function filter(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request, BookRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => 'book',
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }
}
