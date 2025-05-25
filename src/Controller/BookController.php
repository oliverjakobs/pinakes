<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
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
        return $this->renderList($request);
    }

    #[Route('/book/filter', name: 'book_filter', methods: ['GET'])]
    public function filter(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/book/filter_author', name: 'book_filter_author', methods: ['GET'])]
    public function filterAuthor(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository, 'list_author', 'book_filter_author');
    }

    #[Route('/book/filter_publisher', name: 'book_filter_publisher', methods: ['GET'])]
    public function filterPublisher(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository, 'list_publisher', 'book_filter_publisher');
    }

    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request, BookRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/book/edit/{id}', name: 'book_edit', methods: ['GET'])]
    public function edit(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->render('edit.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/book/submit/{id}', name: 'book_submit', methods: ['POST'])]
    public function submit(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $book = $this->tryGetEntity($request, $repository);
        if (null === $book) {
            $book = new Book();
        }

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
}
