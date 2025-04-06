<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorController extends PinakesController {

    #[Route('/authors', name: 'authors', methods: ['GET'])]
    public function list(AuthorRepository $repository): Response {
        return $this->renderTable($repository, 'list');
    }

    #[Route('/author/{id}', name: 'author_show', methods: ['GET'])]
    public function show(int $id, AuthorRepository $repository, BookRepository $book_repository): Response {
        $author = $repository->find($id);
        return $this->renderTable($book_repository, 'list_author', $author->getBooks()->toArray());
    }
}