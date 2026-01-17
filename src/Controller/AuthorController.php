<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthorController extends PinakesController {

    #[Route('/author', name: 'author', methods: ['GET'])]
    public function list(Request $request, AuthorRepository $repository): Response {
        return $this->renderList($request, $repository, 'Authors');
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request, AuthorRepository $repository, BookRepository $books): Response {
        $author = $this->getEntity($request, $repository);
        return $this->renderList($request, $books, 'Author: ' . (string) $author,
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
        return $this->renderModal($request, $repository, 'author_show');
    }

    #[Route('/author/delete/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete(Request $request, AuthorRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->deleteEntityAndRedirect($request, $repository, 'author');
    }
}
