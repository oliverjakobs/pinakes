<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use App\Mapper\PinakesMapper;
use App\Renderable\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthorController extends PinakesController {

    #[Route('/author', name: 'author', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PinakesMapper::getMapper(Author::class)->createTable('list');
        return $this->renderList2($request, 'Authors', $table);
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request): Response {
        /** @var Author */
        $author = $this->getEntityNew($request, Author::class);

        $table = PinakesMapper::getMapper(Book::class)
            ->createTable('list_author')
            ->addFilter('authors', $author);
        return $this->renderList2($request, 'Author: ' . (string) $author, $table, [
            $author->getLinkEdit(),
            $author->getLinkDelete(),
            ViewElement::separator(),
            $author->getLinkOpenLibrary(),
        ]);
    }

    #[Route('/author/modal/{id?}', name: 'author_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Author::class);
        $author = $this->getEntityNew($request, $mapper) ?? new Author();
        return $this->renderModal2($request, $mapper, $author, 'author_show');
    }

    #[Route('/author/delete/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $author = $this->getEntityNew($request, Author::class);
        PinakesMapper::getMapper(Author::class)->deleteEntity($author);
        return $this->redirectHx('author');
    }
}
