<?php declare(strict_types=1);

namespace App\Controller;

use App\Database\Database;
use App\Database\SQL\Delete;
use App\Repository\BookRepository;
use App\Entity\Book;
use App\Entity\User;
use App\Mapper\PinakesMapper;
use App\Pinakes\OpenLibrary;
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
        return $this->renderList2($request, 'Books', $table, [
            Link::modal('New Book', 'book_modal'),
            ViewElement::separator(),
            Link::create('Export Books', 'book_export')
        ]);
    }

    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request): Response {

        $mapper = PinakesMapper::getMapper(Book::class);
        $fields = $mapper->getDataFields('show');
        /** @var Book */
        $book = $this->getEntityNew($request, $mapper, $fields);

        return $this->renderShow2($book, $fields, [
            $book->getLinkEdit(),
            $book->getLinkDelete(),
            ViewElement::separator(),
            $book->getLinkOpenLibrary(),
        ]);
    }

    #[Route('/book/modal/{id?}', name: 'book_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, BookRepository $repository, #[MapQueryParameter] ?int $series = null): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Book::class);
        $fields = $mapper->getDataFields('show');
        /** @var Book */
        $book = $this->getEntityNew($request, $mapper, $fields) ?? new Book();

        // if (null !== $series) {
        //     $book->series = Pinakes::getRepository(Series::class)->find($series);
        // }

        return $this->renderModal2($request, $mapper, $book, 'book_show', $fields);
    }

    #[Route('/book/delete/{id}', name: 'book_delete', methods: ['DELETE'])]
    public function delete(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository);

        $mapper = PinakesMapper::getMapper(Book::class);
        $delete = new Delete($mapper->getTableName(), "id = :id");
        $delete->setParameter(':id', $entity->getId());
        Database::getInstance()->delete($delete);

        return $this->redirectHx('book');
    }

    #[Route('/book/import', name: 'book_import', methods: ['GET'])]
    public function import(Request $request, BookRepository $repository): Response {
        return new Response();
    }

    #[Route('/book/export', name: 'book_export', methods: ['GET'])]
    public function export(BookRepository $repository): Response {
        $table = $repository->createTable('export');
        return $this->exportCsv($table, 'books');
    }
}
