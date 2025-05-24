<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Repository\PublisherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublisherController extends PinakesController {

    public static function getModelName(): string {
        return 'publisher';
    }

    #[Route('/publisher', name: 'publisher', methods: ['GET'])]
    public function list(Request $request, PublisherRepository $repository): Response {
        return $this->renderList($request, $repository);
    }

    #[Route('/publisher/filter', name: 'publisher_filter', methods: ['GET'])]
    public function filter(Request $request, PublisherRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/publisher/show/{id}', name: 'publisher_show', methods: ['GET'])]
    public function show(Request $request, PublisherRepository $repository): Response {
        $publisher = $this->getEntity($request, $repository);
        $filter = $this->getFilter($request) + ['publisher' => $publisher->getId()];

        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $publisher,
            'fields' => $repository->getDataFields('show'),
            'content' => [
                'Books' => $this->renderTable(Book::class, $filter, 'list_publisher', 'book_filter_publisher')
            ]
        ]);
    }
}
