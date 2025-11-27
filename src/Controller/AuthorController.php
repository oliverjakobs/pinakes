<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Author;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorController extends PinakesController {

    #[Route('/author', name: 'author', methods: ['GET'])]
    public function list(Request $request, AuthorRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Authors');
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request, AuthorRepository $repository, BookRepository $books): Response {
        $author = $this->getEntity($request, $repository);
        return $this->renderListFilter($request, $books, 'Author: ' . (string) $author,
            fields: 'list_author',
            params: [ 'actions' => [
                $author->getLinkEdit(),
                $author->getLinkDelete(),
            ]],
            filter: [ 'author' => $author->getId() ]
        );
    }

    #[Route('/author/modal/{id?}', name: 'author_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, AuthorRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $author = $this->getEntity($request, $repository);
        if (null === $author) {
            $author = new Author();
            $author->name = 'New Author';
        }

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->updateFromRequest($request, $repository, $author);
            return $this->redirectToRoute('author_show', [ 'id' => $author->getId() ]);
        }

        return $this->renderModal($repository, $author);
    }

    #[Route('/author/delete/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete(Request $request, AuthorRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $author = $this->getEntity($request, $repository);
        // TODO check if books exist
        $repository->delete($author);

        return $this->redirectToRoute('author');
    }
}
