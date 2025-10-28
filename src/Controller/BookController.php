<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\GenreRepository;
use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Publisher;
use App\Entity\Genre;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends PinakesController {

    #[Route('/book', name: 'book', methods: ['GET'])]
    public function list(Request $request, BookRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Books', params: [
            'actions' => [
                $this->createLink('New Book', 'book_create')->setHx('POST'),
                // TODO add from openlibrary isbn
                $this->createLink('Import Books', 'book_import')->addStyleClasses('button'),
            ]
        ]);
    }

    #[Route('/book/genre/{id}', name: 'book_genre', methods: ['GET'])]
    public function listGenre(Request $request, BookRepository $repository, GenreRepository $genre_rep): Response {
        $genre = $this->getEntity($request, $genre_rep);
        return $this->renderListFilter($request, $repository, 'Genre: ' . (string) $genre, filter: [
            'genre' => $genre->getId()
        ]);
    }

    #[Route('/book/create', name: 'book_create', methods: ['POST'])]
    public function create(Request $request, BookRepository $repository): Response {
        $book = new Book();
        $book->title = 'New Book';

        $repository->save($book);
        return $this->redirectHx('book_show', [ 'id' => $book->getId() ]);
    }

    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request, BookRepository $repository): Response {
        $book = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $book, 'show', [
            'actions' => [
                $this->getActionEdit($book),
                $this->getActionDelete($book),
            ]
        ]);
    }

    #[Route('/book/delete/{id}', name: 'book_delete', methods: ['DELETE'])]
    public function delete(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $book = $this->getEntity($request, $repository);
        $repository->delete($book);

        return $this->redirectHx('book');
    }

    #[Route('/book/form/{id}', name: 'book_form', methods: ['GET', 'POST'])]
    public function form(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $book = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->updateFromRequest($request, $repository, $book, );
            return $this->redirectToRoute('book_show', [ 'id' => $book->getId() ]);
        }

        return $this->renderForm($repository, $book);
    }

    #[Route('/book/import', name: 'book_import', methods: ['GET'])]
    public function import(Request $request, BookRepository $repository): Response {
        return new Response();
    }
}
