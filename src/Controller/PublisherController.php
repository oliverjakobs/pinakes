<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Publisher;
use App\Entity\User;
use App\Repository\PublisherRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublisherController extends PinakesController {

    #[Route('/publisher', name: 'publisher', methods: ['GET'])]
    public function list(Request $request, PublisherRepository $repository): Response {
        return $this->renderList($request, 'Publishers', $repository->createTable());
    }

    #[Route('/publisher/show/{id}', name: 'publisher_show', methods: ['GET'])]
    public function show(Request $request, PublisherRepository $repository, BookRepository $books): Response {
        $publisher = $this->getEntity($request, $repository);

        $table = $books->createTable()->applyFilter([ 'publisher' => $publisher->getId() ]);

        return $this->renderList($request, 'Publisher: ' . (string) $publisher, $table,
            actions: [
                $publisher->getLinkEdit(),
                $publisher->getLinkDelete(),
            ],
        );
    }

    #[Route('/publisher/modal/{id?}', name: 'publisher_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, PublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->renderModal($request, $repository, 'publisher_show');
    }

    #[Route('/publisher/delete/{id}', name: 'publisher_delete', methods: ['DELETE'])]
    public function delete(Request $request, PublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->deleteEntityAndRedirect($request, $repository, 'publisher');
    }
}
