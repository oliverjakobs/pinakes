<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Author;
use App\Entity\User;
use App\Mapper\AuthorMapper;
use App\Mapper\BookMapper;
use App\Renderable\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthorController extends PinakesController {

    #[Route('/author', name: 'author', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = AuthorMapper::getInstance()->createTable('list');
        return $this->renderList($request, 'Authors', $table);
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request): Response {
        /** @var Author */
        $author = $this->getEntity($request, AuthorMapper::getInstance());

        $table = BookMapper::getInstance()
            ->createTable('list_author')
            ->addFilter('authors', $author);
        return $this->renderList($request, 'Author: ' . (string) $author, $table, [
            $author->getLinkEdit(),
            $author->getLinkDelete(),
            ViewElement::separator(),
            $author->getLinkOpenLibrary(),
        ]);
    }

    #[Route('/author/modal/{id?}', name: 'author_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = AuthorMapper::getInstance();
        $author = $this->getEntity($request, $mapper) ?? new Author();
        return $this->renderModal($request, $mapper, $author, 'author_show');
    }

    #[Route('/author/delete/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = AuthorMapper::getInstance();
        $author = $this->getEntity($request, $mapper);
        $mapper->delete($author);
        return $this->redirectHx('author');
    }
}
