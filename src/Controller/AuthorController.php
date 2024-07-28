<?php

namespace App\Controller;

use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        $search = $request->get('search');
        return $this->renderTableContent($repository, 'list', $repository->findLikeName($search));
    }

    #[Route('/author/{id}', name: 'author_show', methods: ['GET'])]
    public function show($id, AuthorRepository $repository): Response {
        return $this->renderShow($repository, $id, 'show');
    }

    #[Route('/author/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete($id, AuthorRepository $repository): Response {
        $repository->delete($repository->find($id));
        return $this->redirectHx('author_list');
    }
}
