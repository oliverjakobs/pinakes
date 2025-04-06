<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends PinakesController {

    #[Route('/books', name: 'books', methods: ['GET'])]
    public function list(BookRepository $repository): Response {
        return $this->render('table.html.twig', [
            'name' => 'books',
            'data' => $repository->findAll(),
            'fields' => $repository->getDataFields('list')
        ]);
    }

    #[Route('/books/filter', name: 'books_filter', methods: ['GET'])]
    public function filter(Request $request, BookRepository $repository): Response {

        $search = $request->get('search');
        $orderby = null;
        
        $order_field = $request->get('orderby');
        if (null !== $order_field) {
            $orderby = [ $order_field => $request->query->get('order_dir', 'asc')];
        }

        return $this->render('tablecontent.html.twig', [
            'name' => 'books',
            'data' => $repository->search($search, $orderby),
            'fields' => $repository->getDataFields('list'),
        ]);
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