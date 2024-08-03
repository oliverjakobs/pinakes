<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\AuthorRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorController extends PinakesController {

    #[Route('/author', name: 'author_list', methods: ['GET'])]
    public function index(AuthorRepository $repository): Response {
        return $this->renderTable($repository, 'list');
    }

    #[Route('/author/search', name: 'author_search', methods: ['GET'])]
    public function search(Request $request, AuthorRepository $repository): Response {
        return $this->renderSearch($repository, 'list', $request->get('search'));
    }

    #[Route('/author/{id}', name: 'author_show', methods: ['GET'])]
    public function show(int $id, AuthorRepository $repository): Response {
        return $this->renderShow($repository, $id, 'show');
    }

    #[Route('/author/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete(int $id, AuthorRepository $repository): Response {
        $repository->delete($repository->find($id));
        return $this->redirectHx('author_list');
    }
}
