<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BooksController extends PinakesController {
    
    protected function getName(): string {
        return 'books';
    }

    #[Route('/books', name: 'books', methods: ['GET'])]
    public function index(BookRepository $repository): Response {
        return $this->renderTable($repository->findAll(), $repository->getFields());
    }

    #[Route('/books/search', name: 'book_search', methods: ['GET'])]
    public function search(Request $request, BookRepository $repository): Response {
        $title = $request->get('search');
        return $this->renderTableContent($repository->findLikeTitle($title), $repository->getFields());
    }
}
