<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Publisher;
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
        return $this->renderList($request, [
            $this->createLink('Import Books', 'book_import'),
            $this->createLink('New Book', 'book_form'),
        ]);
    }

    #[Route('/book/filter', name: 'book_filter', methods: ['GET'])]
    public function filter(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request, BookRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/book/form/{id?}', name: 'book_form', methods: ['GET'])]
    public function form(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        return $this->render('form.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository) ?? new Book(),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/book/submit/{id?}', name: 'book_submit', methods: ['POST'])]
    public function submit(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $book = $this->getEntity($request, $repository) ?? new Book();

        $book->setTitle($request->request->get('title'));

        $author_rep = $this->em->getRepository(Author::class);
        $book->clearAuthors();
        $authors = $request->request->all('authors');
        foreach ($authors as $author) {
            if (empty($author)) continue;
            $book->addAuthor($author_rep->getOrCreate($author), false);
        }

        $publisher_rep = $this->em->getRepository(Publisher::class);
        $book->setPublisher($publisher_rep->getOrCreate($request->request->get('publisher')));

        $book->setPublished(intval($request->request->get('published')));
        $book->setFirstPublished(intval($request->request->get('first_published')));
        $book->setIsbn($request->request->get('isbn'));

        $repository->save($book);
        return $this->redirectToRoute('book_show', [ 'id' => $book->getId() ]);
    }

    #[Route('/book/import', name: 'book_import', methods: ['GET'])]
    public function import(Request $request, BookRepository $repository): Response {
        return new Response();
    }
}
