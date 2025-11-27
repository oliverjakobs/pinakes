<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Publisher;
use App\Entity\User;
use App\Repository\PublisherRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublisherController extends PinakesController {

    #[Route('/publisher', name: 'publisher', methods: ['GET'])]
    public function list(Request $request, PublisherRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Publishers');
    }

    #[Route('/publisher/show/{id}', name: 'publisher_show', methods: ['GET'])]
    public function show(Request $request, PublisherRepository $repository, BookRepository $books): Response {
        $publisher = $this->getEntity($request, $repository);
        return $this->renderListFilter($request, $books, 'Publisher: ' . (string) $publisher,
            params: [ 'actions' => [
                $publisher->getLinkEdit(),
                $publisher->getLinkDelete(),
            ]],
            filter: [ 'publisher' => $publisher->getId() ]
        );
    }

    #[Route('/publisher/modal/{id?}', name: 'publisher_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, PublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $publisher = $this->getEntity($request, $repository);
        if (null === $publisher) {
            $publisher = new Publisher();
            $publisher->name = 'New Publisher';
        }

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->updateFromRequest($request, $repository, $publisher);
            return $this->redirectToRoute('publisher_show', [ 'id' => $publisher->getId() ]);
        }

        return $this->renderModal($repository, $publisher);
    }

    #[Route('/publisher/delete/{id}', name: 'publisher_delete', methods: ['DELETE'])]
    public function delete(Request $request, PublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $publisher = $this->getEntity($request, $repository);
        $repository->delete($publisher);

        return $this->redirectToRoute('publisher');
    }
}
