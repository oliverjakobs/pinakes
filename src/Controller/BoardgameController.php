<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Boardgame;
use App\Entity\User;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use App\Repository\BoardgameRepository;
use App\Repository\BoardgamePublisherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class BoardgameController extends PinakesController {

    #[Route('/boardgame', name: 'boardgame', methods: ['GET'])]
    public function list(Request $request, BoardgameRepository $repository): Response {
        return $this->renderList($request, 'Boardgames', $repository->createTable(), [
                Link::modal('New Game', 'boardgame_modal'),
            ],
            filters: $repository->getDataFieldsFilter()
        );
    }

    #[Route('/boardgame/show/{id}', name: 'boardgame_show', methods: ['GET'])]
    public function show(Request $request, BoardgameRepository $repository): Response {
        $boardgame = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $boardgame, 'show', 
            actions: [
                $boardgame->getLinkEdit(),
                $boardgame->getLinkDelete(),
                ViewElement::separator(),
                Link::modal('Add Extension', 'boardgame_modal', [ 'base' => $boardgame->getId() ]),
            ]
        );
    }

    #[Route('/boardgame/modal/{id?}', name: 'boardgame_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, BoardgameRepository $repository, #[MapQueryParameter] ?int $base = null): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        /** @var Boardgame */
        $boardgame = $this->getEntity($request, $repository) ?? $repository->getTemplate();

        if (null !== $base) {
            $boardgame->base_game = $repository->find($base);
        }

        return $this->renderModal($request, $repository, $boardgame, 'boardgame_show');
    }

    #[Route('/boardgame/delete/{id}', name: 'boardgame_delete', methods: ['DELETE'])]
    public function delete(Request $request, BoardgameRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository);
        return $this->deleteEntityAndRedirect($request, $repository, $entity, 'boardgame');
    }

    // Publisher
    #[Route('/boardgame/publisher', name: 'boardgamepublisher', methods: ['GET'])]
    public function listPublisher(Request $request, BoardgamePublisherRepository $repository): Response {
        return $this->renderList($request, 'Boardgame publishers', $repository->createTable());
    }

    #[Route('/boardgame/publisher/{id}', name: 'boardgamepublisher_show', methods: ['GET'])]
    public function showPublisher(Request $request, BoardgamePublisherRepository $repository, BoardgameRepository $boardgames): Response {
        $publisher = $this->getEntity($request, $repository);

        $table = $boardgames->createTable()->addFilter('publisher', $publisher);
        return $this->renderList($request, 'Publisher: ' . (string) $publisher, $table, [
            $publisher->getLinkEdit(),
            $publisher->getLinkDelete(),
        ]);
    }

    #[Route('/boardgame/publisher/modal/{id?}', name: 'boardgamepublisher_modal', methods: ['GET', 'POST'])]
    public function modalPublisher(Request $request, BoardgamePublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository) ?? $repository->getTemplate();
        return $this->renderModal($request, $repository, $entity, 'boardgamepublisher_show');
    }

    #[Route('/boardgame/publisher/delete/{id}', name: 'boardgamepublisher_delete', methods: ['DELETE'])]
    public function deletePublisher(Request $request, BoardgamePublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository);
        return $this->deleteEntityAndRedirect($request, $repository, $entity, 'boardgamepublisher');
    }
}
