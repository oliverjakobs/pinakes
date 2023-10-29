<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BooksController extends AbstractController
{
    #[Route('/books', name: 'books', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('content.html.twig', [
            'name' => 'books',
            'fields' => [ "Title", "Author(s)", "Release Year" ],
            'content' => [],
            'content_template' => '/books/list.html.twig',
            'allow_add' => false
        ]);
    }

    #[Route('/books/search', name: 'book_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $title = $request->get('search');
        return $this->render('/books/list.html.twig', [
            'content' => []
        ]);
    }
}
