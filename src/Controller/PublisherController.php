<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Publisher;
use App\Entity\User;
use App\Mapper\BookMapper;
use App\Mapper\PublisherMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublisherController extends PinakesController {

    #[Route('/publisher', name: 'publisher', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PublisherMapper::getInstance()->createTable('list');
        return $this->renderList($request, 'Publishers', $table);
    }

    #[Route('/publisher/show/{id}', name: 'publisher_show', methods: ['GET'])]
    public function show(Request $request): Response {
        /** @var Publisher */
        $publisher = $this->getEntity($request, PublisherMapper::getInstance());

        $table = BookMapper::getInstance()
            ->createTable('list_publisher')
            ->addFilter('publisher', $publisher);
        return $this->renderList($request, 'Publisher: ' . (string) $publisher, $table, [
            $publisher->getLinkEdit(),
            $publisher->getLinkDelete(),
        ]);
    }

    #[Route('/publisher/modal/{id?}', name: 'publisher_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PublisherMapper::getInstance();
        $publisher = $this->getEntity($request, $mapper) ?? new Publisher();
        return $this->renderModal($request, $mapper, $publisher, 'publisher_show');
    }

    #[Route('/publisher/delete/{id}', name: 'publisher_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PublisherMapper::getInstance();
        $publisher = $this->getEntity($request, $mapper);
        $mapper->delete($publisher);
        return $this->redirectHx('publisher');
    }
}
