<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends PinakesController {


    #[Route('/books', name: 'books', methods: ['GET'])]
    public function list(BookRepository $repository, Request $request): Response {
        return $this->render('table.html.twig', [
            'name' => 'books',
            'data' => $this->getEntityList($request, $repository),
            'fields' => $repository->getDataFields('list')
        ]);
    }

    #[Route('/books/filter', name: 'books_filter', methods: ['GET'])]
    public function filter(BookRepository $repository, Request $request): Response {
        $response = $this->render('tablecontent.html.twig', [
            'name' => 'books',
            'data' => $this->getEntityList($request, $repository),
            'fields' => $repository->getDataFields('list'),
        ]);

        $search = $request->get('search');
        $response->headers->set('HX-Push-Url', '/books?search=' . $search);

        return $response;
    }

    #[Route('/book/{id}', name: 'books_show', methods: ['GET'])]
    public function show(Request $request, BookRepository $repository): Response {

        return $this->render('show.html.twig', [
            'name' => 'books',
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }
}