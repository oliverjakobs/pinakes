<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\PublisherRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublisherController extends PinakesController {

    #[Route('/publishers', name: 'publishers', methods: ['GET'])]
    public function list(PublisherRepository $repository): Response {
        return $this->renderTable($repository, 'list');
    }

    #[Route('/publisher/{id}', name: 'publisher_show', methods: ['GET'])]
    public function show(int $id, PublisherRepository $repository, BookRepository $book_repository): Response {
        $publisher = $repository->find($id);
        return $this->renderTable($book_repository, 'list_publisher', $publisher->getBooks()->toArray());
    }
}