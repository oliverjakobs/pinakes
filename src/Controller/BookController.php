<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends PinakesController {

    #[Route('/book', name: 'book_list', methods: ['GET'])]
    public function index(BookRepository $repository): Response {
        return $this->renderTable($repository, 'list');
    }

    #[Route('/book/search', name: 'book_search', methods: ['GET'])]
    public function search(Request $request, BookRepository $repository): Response {
        return $this->renderSearch($repository, 'list', $request->get('search'));
    }

    #[Route('/book/{id}', name: 'book_show', methods: ['GET'])]
    public function show(int $id, BookRepository $repository): Response {
        return $this->renderShow($repository, $id, 'show');
    }

    #[Route('/book/{id}', name: 'book_delete', methods: ['DELETE'])]
    public function delete(int $id, BookRepository $repository): Response {
        $repository->delete($repository->find($id));
        return $this->redirectHx('book_list');
    }
}
