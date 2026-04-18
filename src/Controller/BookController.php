<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\TagRepository;
use App\Entity\Book;
use App\Entity\Series;
use App\Entity\User;
use App\Pinakes\OpenLibrary;
use App\Pinakes\Pinakes;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class BookController extends PinakesController {

    #[Route('/book', name: 'book', methods: ['GET'])]
    public function list(Request $request, BookRepository $repository, TagRepository $tags): Response {
        $table = $repository->createTable()->addFilter('ntags', [
            $tags->findOneByName('Manga'),
            $tags->findOneByName('Comic'),
        ]);

        return $this->renderList($request, 'Books', $table, [
            Link::modal('New Book', 'book_modal'),
            Link::modal('From ISBN', 'book_modal_isbn'),
            ViewElement::separator(),
            Link::create('Export Books', 'book_export')
        ]);
    }

    #[Route('/book/all', name: 'book_all', methods: ['GET'])]
    public function listAll(Request $request, BookRepository $repository): Response {
        $table = $repository->createTable();
        return $this->renderList($request, 'Books', $table, [
            Link::modal('New Book', 'book_modal'),
            Link::modal('From ISBN', 'book_modal_isbn'),
        ]);
    }

    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request, BookRepository $repository): Response {
        /** @var Book */
        $book = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $book, 'show', [
            $book->getLinkEdit(),
            $book->getLinkDelete(),
            ViewElement::separator(),
            $book->getLinkOpenLibrary(),
        ]);
    }

    #[Route('/book/modal/{id?}', name: 'book_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, BookRepository $repository, #[MapQueryParameter] ?int $series = null): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        /** @var Book */
        $book = $this->getEntity($request, $repository) ?? $repository->getTemplate();

        if (null !== $series) {
            $book->series = Pinakes::getRepository(Series::class)->find($series);
        }

        return $this->renderModal($request, $repository, $book, 'book_show');
    }

    #[Route('/book/delete/{id}', name: 'book_delete', methods: ['DELETE'])]
    public function delete(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository);
        return $this->deleteEntityAndRedirect($request, $repository, $entity, 'book');
    }

    #[Route('/book/modal-isbn', name: 'book_modal_isbn', methods: ['GET', 'POST'])]
    public function modalIsbn(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        if (Request::METHOD_POST === $request->getMethod()) {
            $isbn = $request->request->get('isbn');
            return $this->redirectToRoute('book_from_isbn', [ 'isbn' => $isbn ]);
        }

        return $this->render('modals/from_isbn.html.twig', [
            'caption' => 'Enter ISBN',
        ]);
    }

    #[Route('/book/from-isbn/{isbn}', name: 'book_from_isbn', methods: ['GET'])]
    public function fromIsbn(string $isbn): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $result = OpenLibrary::findByIsbn($isbn);

        return $this->render('searchresult.html.twig', [
            'results' => $result,
        ]);
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
