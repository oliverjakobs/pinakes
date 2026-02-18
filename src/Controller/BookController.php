<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\TagRepository;
use App\Entity\Book;
use App\Entity\Series;
use App\Entity\User;
use App\Pinakes\DataTable;
use App\Pinakes\OpenLibrary;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class BookController extends PinakesController {

    #[Route('/book', name: 'book', methods: ['GET'])]
    public function list(Request $request, BookRepository $repository, TagRepository $tags): Response {
        $table = $repository->createTable()->applyFilter([ 
            'ntag' => [ $tags->findOneByName('Manga'), ]
        ]);

        return $this->renderList($request, 'Books', $table,
            actions: [
                Link::post('New Book', 'book_create'),
                Link::modal('From ISBN', 'book_modal_isbn'),
                // $this->createLink('Import Books', 'book_import')->addClasses(['button']),
                // $this->createLink('Export Books', 'book_export')->addClasses(['button']),
            ]
        );
    }

    #[Route('/book/create', name: 'book_create', methods: ['POST'])]
    public function create(BookRepository $repository, #[MapQueryParameter] ?int $series = null): Response {
        $book = $repository->getTemplate();

        if (null !== $series) {
            $book->series = $this->em->getRepository(Series::class)->find($series);
        }

        $repository->save($book);
        return $this->redirectHx('book_show', [ 'id' => $book->getId() ]);
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


    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request, BookRepository $repository): Response {
        /** @var Book */
        $book = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $book, 'show', [
            'actions' => [
                $book->getLinkOpenLibrary(),
                ViewElement::separator(),
                $book->getLinkEdit(),
                $book->getLinkDelete(),
            ]
        ]);
    }

    #[Route('/book/modal/{id}', name: 'book_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->renderModal($request, $repository, 'book_show');
    }

    #[Route('/book/delete/{id}', name: 'book_delete', methods: ['DELETE'])]
    public function delete(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->deleteEntityAndRedirect($request, $repository, 'book');
    }

    #[Route('/book/import', name: 'book_import', methods: ['GET'])]
    public function import(Request $request, BookRepository $repository): Response {
        return new Response();
    }

    #[Route('/book/export', name: 'book_export', methods: ['GET'])]
    public function export(BookRepository $repository): Response {
        $response = $this->render('export.csv.twig', [
            'data' => $repository->findAll(),
            'fields' => $repository->getDataFields('export'),
        ]);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');
        return $response;
    }
}
