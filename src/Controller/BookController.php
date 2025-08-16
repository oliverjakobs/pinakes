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

    public static function getModelName(): string {
        return 'book';
    }

    #[Route('/book', name: 'book', methods: ['GET'])]
    public function list(Request $request, BookRepository $repository): Response {
        return $this->renderList($request, 'Books', [
            'actions' => [
                // TODO add from openlibrary isbn
                $this->createLink('Import Books', 'book_import'),
                $this->createLink('New Book', 'book_create')->setHx('POST'),
            ]
        ]);
    }

    #[Route('/book/filter', name: 'book_filter', methods: ['GET'])]
    public function filter(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/book/genre/{id}', name: 'book_genre', methods: ['GET'])]
    public function listGenre(Request $request, GenreRepository $repository): Response {
        $genre = $this->getEntity($request, $repository);
        return $this->renderList($request, 'Genre: ' . (string) $genre, [
            'filter' => $this->getFilter($request, [ 'genre' => $genre->getId() ])
        ]);
    }

    #[Route('/book/genre/{id}/filter', name: 'book_genre_filter', methods: ['GET'])]
    public function filterGenre(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository);
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

        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $book,
            'fields' => $repository->getDataFields('show'),
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
            $book->title = $request->request->get('title');

            $author_rep = $this->em->getRepository(Author::class);
            $book->clearAuthors();
            $authors = $request->request->all('authors');
            foreach ($authors as $author) {
                if (empty($author)) continue;
                $book->addAuthor($author_rep->getOrCreate($author, false));
            }

            $genre_rep = $this->em->getRepository(Genre::class);
            $book->clearGenre();
            $genre = $request->request->all('genre');
            foreach ($genre as $name) {
                if (empty($name)) continue;
                $book->addGenre($genre_rep->getOrCreate($name, false));
            }

            $publisher_rep = $this->em->getRepository(Publisher::class);
            $book->publisher = $publisher_rep->getOrCreate($request->request->get('publisher'));

            $published = $request->request->get('published');
            $book->published = !empty($published) ? intval($published) : null;
            $first_published = $request->request->get('first_published');
            $book->first_published = !empty($first_published) ? intval($first_published) : null;
            $book->isbn = $request->request->get('isbn');

            $repository->save($book);
            return $this->redirectToRoute('book_show', [ 'id' => $book->getId() ]);
        }

        return $this->renderForm($repository, $book);
    }

    #[Route('/book/import', name: 'book_import', methods: ['GET'])]
    public function import(Request $request, BookRepository $repository): Response {
        return new Response();
    }
}
