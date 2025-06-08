<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorController extends PinakesController {

    public static function getModelName(): string {
        return 'author';
    }

    #[Route('/author', name: 'author', methods: ['GET'])]
    public function list(Request $request, AuthorRepository $repository): Response {
        return $this->renderList($request);
    }

    #[Route('/author/filter', name: 'author_filter', methods: ['GET'])]
    public function filter(Request $request, AuthorRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request, AuthorRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
            'content' => [
                'title' => 'Books',
                'filter' => ['pp' => 10] + $this->getFilter($request),
                'route' => 'author_show_filter'
            ]
        ]);
    }

    #[Route('/author/show/{id}/filter', name: 'author_show_filter', methods: ['GET'])]
    public function showFilter(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository, 'list_author');
    }

    #[Route('/author/form/{id?}', name: 'author_form', methods: ['GET'])]
    public function form(Request $request, AuthorRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        return $this->render('form.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository) ?? new Author(),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/author/submit/{id}', name: 'author_submit', methods: ['POST'])]
    public function submit(Request $request, AuthorRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $author = $this->getEntity($request, $repository) ?? new Author();

        $author->setName($request->request->get('name'));
        $author->setOpenlibrary($request->request->get('openlibrary'));

        $repository->save($author);
        return $this->redirectToRoute('author_show', [ 'id' => $author->getId() ]);
    }
}
