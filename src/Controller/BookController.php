<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends PinakesController {

    private function getFromRequest(BookRepository $repository, Request $request): array {
        $search = $request->get('search');
        $order_by = null;
        
        $order_field = $request->get('order_by');
        if (null !== $order_field) {
            $order_by = [ $order_field => $request->query->get('order_dir', 'asc')];
        }
        
        return $repository->search($search, $order_by);
    }

    #[Route('/books', name: 'books', methods: ['GET'])]
    public function list(BookRepository $repository, Request $request): Response {
        return $this->render('table.html.twig', [
            'name' => 'books',
            'data' => $this->getFromRequest($repository, $request),
            'fields' => $repository->getDataFields('list')
        ]);
    }

    #[Route('/books/filter', name: 'books_filter', methods: ['GET'])]
    public function filter(BookRepository $repository, Request $request): Response {

        $response = $this->render('tablecontent.html.twig', [
            'name' => 'books',
            'data' => $this->getFromRequest($repository, $request),
            'fields' => $repository->getDataFields('list'),
        ]);

        $search = $request->get('search');
        $response->headers->set('HX-Push-Url', '/books?search=' . $search);

        return $response;
    }

    #[Route('/book/{id}', name: 'books_show', methods: ['GET'])]
    public function show(int $id, BookRepository $repository): Response {
        $entity = $repository->find($id);

        if (null === $entity) {
            throw $this->createNotFoundException('Book with id ' . $id . ' does not exist');
        }

        return $this->render('show.html.twig', [
            'name' => 'books',
            'entity' => $entity,
            'fields' => $repository->getDataFields('show'),
        ]);
    }
}