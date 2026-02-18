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
        return $this->renderList($request, 'Authors', $repository->createTable());
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request, AuthorRepository $repository, BookRepository $books): Response {
        $author = $this->getEntity($request, $repository);

        $table = $books->createTable('list_author')->applyFilter([ 'author' => $author->getId() ]);

        return $this->renderList($request, 'Author: ' . (string) $author, $table,
            actions: [
                $author->getLinkEdit(),
                $author->getLinkDelete(),
            ]
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
