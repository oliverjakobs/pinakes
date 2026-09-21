<?php declare(strict_types=1);

namespace App\Controller;

use App\Database\Database;
use App\Database\SQL\Delete;
use App\Entity\Book;
use App\Entity\Series;
use App\Entity\User;
use App\Mapper\PinakesMapper;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class BookController extends PinakesController {

    #[Route('/book', name: 'book', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PinakesMapper::getMapper(Book::class)->createTable('list');
        return $this->renderList($request, 'Books', $table, [
            Link::modal('New Book', 'book_modal'),
            ViewElement::separator(),
            Link::create('Export Books', 'book_export')
        ]);
    }

    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request): Response {
        $mapper = PinakesMapper::getMapper(Book::class);
        /** @var Book */
        $book = $this->getEntity($request, $mapper);
        return $this->renderShow($book, $mapper->getDataFields('show'), [
            $book->getLinkEdit(),
            $book->getLinkDelete(),
            ViewElement::separator(),
            $book->getLinkOpenLibrary(),
        ]);
    }

    #[Route('/book/modal/{id?}', name: 'book_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, #[MapQueryParameter] ?int $series = null): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Book::class);

        /** @var Book */
        $book = $this->getEntity($request, $mapper);
        if (null === $book) {
            $book = new Book();
        }

        if (null !== $series) {
            $book->series = PinakesMapper::getMapper(Series::class)->find($series);
        }

        return $this->renderModal($request, $mapper, $book, 'book_show', 'show');
    }

    #[Route('/book/delete/{id}', name: 'book_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $book = $this->getEntity($request, Book::class);
        PinakesMapper::getMapper(Book::class)->deleteEntity($book);

        return $this->redirectHx('book');
    }

    #[Route('/book/import', name: 'book_import', methods: ['GET'])]
    public function import(Request $request): Response {
        return new Response();
    }

    #[Route('/book/export', name: 'book_export', methods: ['GET'])]
    public function export(Request $request): Response {
        return new Response();
        // $table = $repository->createTable('export');
        // return $this->exportCsv($table, 'books');
    }
}
