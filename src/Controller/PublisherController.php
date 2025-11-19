<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\PublisherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublisherController extends PinakesController {

    #[Route('/publisher', name: 'publisher', methods: ['GET'])]
    public function list(Request $request, PublisherRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Publishers');
    }

    #[Route('/publisher/show/{id}', name: 'publisher_show', methods: ['GET'])]
    public function show(Request $request, PublisherRepository $repository): Response {
        $publisher = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $publisher, 'show', [
            'actions' => [
                $this->getActionEdit($publisher),
                $this->getActionDelete($publisher),
            ],
            // 'content' => [
            //     'title' => 'Books',
            //     'filter' => $this->getFilter($request->query->all(), ['pp' => 10, 'publisher' => $publisher->getId()]),
            //     'route' => 'publisher_show_filter'
            // ]
        ]);
    }

    #[Route('/publisher/delete/{id}', name: 'publisher_delete', methods: ['DELETE'])]
    public function delete(Request $request, PublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $publisher = $this->getEntity($request, $repository);
        $repository->delete($publisher);

        return $this->redirectToRoute('publisher');
    }

    #[Route('/publisher/form/{id}', name: 'publisher_form', methods: ['GET', 'POST'])]
    public function form(Request $request, PublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $publisher = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->updateFromRequest($request, $repository, $publisher);
            return $this->redirectToRoute('publisher_show', [ 'id' => $publisher->getId() ]);
        }

        return $this->renderForm($repository, $publisher);
    }
}
