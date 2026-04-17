<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use App\Repository\SeriesRepository;
use App\Repository\BookRepository;
use App\Repository\TagRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends PinakesController {

    #[Route('/collection', name: 'collection', methods: ['GET'])]
    public function list(Request $request, SeriesRepository $repository): Response {
        return $this->renderList($request, 'Collections', $repository->createTable(), [
            Link::modal('New Collection', 'collection_modal'),
        ]);
    }

    #[Route('/collection/show/{id}', name: 'collections_show', methods: ['GET'])]
    public function show(Request $request, SeriesRepository $repository, BookRepository $books): Response {
        $series = $this->getEntity($request, $repository);

        $table = $books->createTable('list_series')->addFilter('series', $series);
        return $this->renderList($request, 'Series: ' . (string) $series, $table, [
            Link::modal('Add Volume', 'book_modal', [ 'series' => $series->getId() ]),
            Link::modal('Add Tag', 'series_add_tag', [ 'id' => $series->getId() ]),
            ViewElement::separator(),
            $series->getLinkEdit(),
            $series->getLinkDelete(),
        ]);
    }

    #[Route('/collection/modal/{id?}', name: 'collection_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, SeriesRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository) ?? $repository->getTemplate();
        return $this->renderModal($request, $repository, $entity, 'series_show');
    }

    #[Route('/collection/delete/{id}', name: 'collection_delete', methods: ['DELETE'])]
    public function delete(Request $request, SeriesRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository);
        return $this->deleteEntityAndRedirect($request, $repository, $entity, 'series');
    }
}
