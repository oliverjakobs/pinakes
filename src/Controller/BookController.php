<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\TagRepository;
use App\Repository\AuthorRepository;
use App\Repository\SeriesRepository;
use App\Repository\PublisherRepository;
use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Publisher;
use App\Entity\Tag;
use App\Entity\Series;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class BookController extends PinakesController {

    #[Route('/book', name: 'book', methods: ['GET'])]
    public function list(Request $request, BookRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Books', params: [
            'actions' => [
                $this->createLinkHx('New Book', 'POST', '', 'book_create'),
                // TODO add from openlibrary isbn
                $this->createLink('Import Books', 'book_import')->addClasses(['button']),
                $this->createLink('Export Books', 'book_export')->addClasses(['button']),
            ]
        ]);
    }

    #[Route('/book/tag/{id}', name: 'book_tag', methods: ['GET'])]
    public function listTag(Request $request, BookRepository $repository, TagRepository $tag_rep): Response {
        $tag = $this->getEntity($request, $tag_rep);
        return $this->renderListFilter($request, $repository, 'Tag: ' . (string) $tag,
            params: [ 'actions' => [
                $tag->getLinkShow()->addClasses(['button'])
            ]],
            filter: [ 'tag' => $tag->getId() ]
        );
    }

    #[Route('/book/author/{id}', name: 'book_author', methods: ['GET'])]
    public function listAuthor(Request $request, BookRepository $repository, AuthorRepository $author_rep): Response {
        $author = $this->getEntity($request, $author_rep);
        return $this->renderListFilter($request, $repository, 'Author: ' . (string) $author,
            fields: 'list_author',
            params: [ 'actions' => [
                $author->getLinkShow()->addClasses(['button'])
            ]],
            filter: [ 'author' => $author->getId() ]
        );
    }

    #[Route('/book/series/{id}', name: 'book_series', methods: ['GET'])]
    public function listSeries(Request $request, BookRepository $repository, SeriesRepository $series_rep): Response {
        $series = $this->getEntity($request, $series_rep);
        return $this->renderListFilter($request, $repository, 'Series: ' . (string) $series,
            fields: 'list_series',
            params: [ 'actions' => [
                $series->getLinkShow()->addClasses(['button']),
                $this->createLinkHx('New Book', 'POST', '', 'book_create', [ 'series' => $series->getId() ]),
            ]],
            filter: [ 'series' => $series->getId() ]
        );
    }

    #[Route('/book/publisher/{id}', name: 'book_publisher', methods: ['GET'])]
    public function listPublisher(Request $request, BookRepository $repository, PublisherRepository $publisher_rep): Response {
        $publisher = $this->getEntity($request, $publisher_rep);
        return $this->renderListFilter($request, $repository, 'Publisher: ' . (string) $publisher,
            params: [ 'actions' => [
                $publisher->getLinkShow()->addClasses(['button'])
            ]],
            filter: [ 'publisher' => $publisher->getId() ]
        );
    }

    #[Route('/book/create', name: 'book_create', methods: ['POST'])]
    public function create(Request $request, BookRepository $repository, #[MapQueryParameter] ?int $series = null): Response {
        $book = new Book();
        $book->title = 'New Book';
        $book->created_at = new \DateTime();

        if (null !== $series) {
            $book->series = $this->em->getRepository(Series::class)->find($series);
        }

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
            $this->updateFromRequest($request, $repository, $book);
            return $this->redirectToRoute('book_show', [ 'id' => $book->getId() ]);
        }

        return $this->renderForm($repository, $book);
    }

    #[Route('/book/import', name: 'book_import', methods: ['GET'])]
    public function import(Request $request, BookRepository $repository): Response {
        return new Response();
    }

    #[Route('/book/export', name: 'book_export', methods: ['GET'])]
    public function export(Request $request, BookRepository $repository): Response {
        $response = $this->render('export.csv.twig', [
            'data' => $repository->findAll(),
            'fields' => $this->getDataFields($repository, 'export'),
        ]);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');
        return $response;
    }
}
