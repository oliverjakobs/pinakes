<?php

namespace App\Controller;

use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorsController extends AbstractController
{
    #[Route('/authors', name: 'authors', methods: ['GET'])]
    public function index(AuthorRepository $repository): Response
    {
        return $this->render('content.html.twig', [
            'name' => 'authors',
            'fields' => [ "Name", "Papers" ],
            'content' => $repository->findAll(),
            'content_template' => '/authors/list.html.twig',
            'allow_add' => false
        ]);
    }

    #[Route('/authors/search', name: 'author_search', methods: ['GET'])]
    public function search(Request $request, AuthorRepository $repository): Response
    {
        $search = $request->get('search');
        return $this->render('/authors/list.html.twig', [
            'content' => $repository->findLikeName($search)
        ]);
    }

    #[Route('/authors/{id}', name: 'author_show', methods: ['GET'])]
    public function show($id, AuthorRepository $repository): Response
    {
        return $this->render('authors/show.html.twig', [
            'author' => $repository->find($id),
        ]);
    }

    #[Route('/authors/{id}', name: 'author_delete', methods: ['DELETE'])]
    public function delete($id, AuthorRepository $repository, EntityManagerInterface $em): Response
    {
        $author = $repository->find($id);
        $em->remove($author);
        $em->flush();
        
        return new Response(headers: [
            'HX-Redirect' => '/authors'
        ]);
    }
}
