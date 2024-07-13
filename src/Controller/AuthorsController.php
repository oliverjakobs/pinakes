<?php

namespace App\Controller;

use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorsController extends PinakesController {

    #[Route('/authors', name: 'authors', methods: ['GET'])]
    public function index(AuthorRepository $repository): Response {
        return $this->renderTable($repository->findAll(), $repository, 'list');
    }

    #[Route('/authors/search', name: 'author_search', methods: ['GET'])]
    public function search(Request $request, AuthorRepository $repository): Response {
        $search = $request->get('search');
        return $this->renderTableContent($repository->findLikeName($search), $repository, 'list');
    }

    #[Route('/authors/{id}', name: 'author_show', methods: ['GET'])]
    public function show($id, AuthorRepository $repository): Response {
        return $this->render('authors/show.html.twig', [
            'author' => $repository->find($id),
        ]);
    }

    #[Route('/authors/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete($id, AuthorRepository $repository, EntityManagerInterface $em): Response {
        $author = $repository->find($id);
        $em->remove($author);
        $em->flush();
        
        return $this->redirectHx('/authors');
    }
}
