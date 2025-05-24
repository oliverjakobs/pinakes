<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorController extends PinakesController {

    public static function getModelName(): string {
        return 'author';
    }

    #[Route('/author', name: 'author', methods: ['GET'])]
    public function list(Request $request, AuthorRepository $repository): Response {
        return $this->renderList($request, $repository);
    }

    #[Route('/author/filter', name: 'author_filter', methods: ['GET'])]
    public function filter(Request $request, AuthorRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request, AuthorRepository $repository): Response {
        $author = $this->getEntity($request, $repository);
        $filter = $this->getFilter($request) + ['author' => $author->getId()];

        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $author,
            'fields' => $repository->getDataFields('show'),
            'content' => [
                'Books' => $this->renderTable(Book::class, $filter, 'list_author', 'book_filter_author')
            ]
        ]);
    }

    #[Route('/author/edit/{id}', name: 'author_edit', methods: ['GET'])]
    public function edit(Request $request, AuthorRepository $repository): Response {
        return $this->render('edit.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/author/submit/{id}', name: 'author_submit', methods: ['POST'])]
    public function submit(Request $request, AuthorRepository $repository): Response {
        $author = $this->tryGetEntity($request, $repository);
        if (null === $author) {
            $author = new Author();
        }

        $author->setName($request->request->get('name'));
        $author->setOpenlibrary($request->request->get('openlibrary'));

        $repository->save($author);
        return $this->redirectToRoute('author_show', [ 'id' => $author->getId() ]);
    }
}
