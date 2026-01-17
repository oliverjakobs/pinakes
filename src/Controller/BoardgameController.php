<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Boardgame;
use App\Repository\BoardgameRepository;
use App\Repository\BoardgamePublisherRepository;
use App\Pinakes\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class BoardgameController extends PinakesController {

    #[Route('/boardgame', name: 'boardgame', methods: ['GET'])]
    public function list(Request $request, BoardgameRepository $repository): Response {
        return $this->renderList($request, $repository, 'Boardgames', params: [
            'actions' => [
                $this->createLinkHx('New Game', 'POST', '', 'boardgame_create'),
            ]
        ]);
    }

    #[Route('/boardgame/create', name: 'boardgame_create', methods: ['POST'])]
    public function create(BoardgameRepository $repository, #[MapQueryParameter] ?int $base = null): Response {
        $boardgame = $repository->getTemplate();

        if (null !== $base) {
            $boardgame->base_game = $repository->find($base);
        }

        $repository->save($boardgame);
        return $this->redirectHx('boardgame_show', [ 'id' => $boardgame->getId() ]);
    }

    #[Route('/boardgame/show/{id}', name: 'boardgame_show', methods: ['GET'])]
    public function show(Request $request, BoardgameRepository $repository): Response {
        $boardgame = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $boardgame, 'show', [
            'actions' => [
                $this->createLinkHx('Add Extension', 'POST', '', 'boardgame_create', [ 'base' => $boardgame->getId() ]),
                ViewElement::separator(),
                $boardgame->getLinkEdit(),
                $boardgame->getLinkDelete(),
            ]
        ]);
    }

    #[Route('/boardgame/modal/{id}', name: 'boardgame_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, BoardgameRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->renderModal($request, $repository, 'boardgame_show');
    }

    #[Route('/boardgame/delete/{id}', name: 'boardgame_delete', methods: ['DELETE'])]
    public function delete(Request $request, BoardgameRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->deleteEntityAndRedirect($request, $repository, 'boardgame');
    }


    // Publisher
    #[Route('/boardgame/publisher', name: 'boardgamepublisher', methods: ['GET'])]
    public function listPublisher(Request $request, BoardgamePublisherRepository $repository): Response {
        return $this->renderList($request, $repository, 'Boardgame publishers');
    }

    #[Route('/boardgame/publisher/{id}', name: 'boardgamepublisher_show', methods: ['GET'])]
    public function showPublisher(Request $request, BoardgamePublisherRepository $repository): Response {
        $publisher = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $publisher, 'show', [
            'actions' => [
                $publisher->getLinkEdit(),
                $publisher->getLinkDelete(),
            ]
        ]);
    }

    #[Route('/boardgame/publisher/modal/{id?}', name: 'boardgamepublisher_modal', methods: ['GET', 'POST'])]
    public function modalPublisher(Request $request, BoardgamePublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->renderModal($request, $repository, 'boardgamepublisher_show');
    }

    #[Route('/boardgame/publisher/delete/{id}', name: 'boardgamepublisher_delete', methods: ['DELETE'])]
    public function deletePublisher(Request $request, BoardgamePublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->deleteEntityAndRedirect($request, $repository, 'boardgamepublisher');
    }
}
