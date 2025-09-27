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

    #[Route('/author', name: 'author', methods: ['GET'])]
    public function list(Request $request, AuthorRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Authors');
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request, AuthorRepository $repository): Response {
        $author = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $author, 'show', [
            'actions' => [
                $this->getActionEdit($author),
                $this->getActionDelete($author),
            ],
            // 'content' => [
            //     'title' => 'Books',
            //     'filter' => $this->getFilter($request->query->all(), ['pp' => 10, 'author' => $author->getId()]),
            // ]
        ]);
    }

    #[Route('/author/delete/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete(Request $request, AuthorRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $author = $this->getEntity($request, $repository);
        // TODO check if books exist
        $repository->delete($author);

        return $this->redirectToRoute('author');
    }

    #[Route('/author/form/{id}', name: 'author_form', methods: ['GET', 'POST'])]
    public function form(Request $request, AuthorRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $author = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $author->name = $request->request->get('name');
            $author->openlibrary = $request->request->get('openlibrary');

            $repository->save($author);
            return $this->redirectToRoute('author_show', [ 'id' => $author->getId() ]);
        }

        return $this->renderForm($repository, $author);
    }
}
