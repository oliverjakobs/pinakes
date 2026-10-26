<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Boardgame;
use App\Entity\BoardgamePublisher;
use App\Entity\User;
use App\Mapper\PinakesMapper;
use App\Renderable\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class BoardgameController extends PinakesController {

    #[Route('/boardgame', name: 'boardgame', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PinakesMapper::getMapper(Boardgame::class)->createTable('list');
        return $this->renderList($request, 'Boardgames', $table, [
            Link::modal('New Game', 'boardgame_modal'),
        ]);
    }

    #[Route('/boardgame/show/{id}', name: 'boardgame_show', methods: ['GET'])]
    public function show(Request $request): Response {
        $mapper = PinakesMapper::getMapper(Boardgame::class);
        /** @var Boardgame */
        $boardgame = $this->getEntity($request, $mapper);
        return $this->renderShow($boardgame, $mapper->getDataFields('show'), [
            $boardgame->getLinkEdit(),
            $boardgame->getLinkDelete(),
        ]);
    }

    #[Route('/boardgame/modal/{id?}', name: 'boardgame_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, #[MapQueryParameter] ?int $base = null): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Boardgame::class);

        /** @var Boardgame */
        $boardgame = $this->getEntity($request, $mapper);
        if (null === $boardgame) {
            $boardgame = new Boardgame();
        }

        if (null !== $base) {
            $boardgame->base_game = $mapper->find($base);
        }

        return $this->renderModal($request, $mapper, $boardgame, 'boardgame_show');
    }

    #[Route('/boardgame/delete/{id}', name: 'boardgame_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $boardgame = $this->getEntity($request, Boardgame::class);
        PinakesMapper::getMapper(Boardgame::class)->delete($boardgame);
        return $this->redirectHx('boardgame');
    }

    // Publisher
    #[Route('/boardgame/publisher', name: 'boardgamepublisher', methods: ['GET'])]
    public function publisherList(Request $request): Response {
        $table = PinakesMapper::getMapper(BoardgamePublisher::class)->createTable('list');
        return $this->renderList($request, 'Publishers', $table);
    }

    #[Route('/boardgame/publisher/{id}', name: 'boardgamepublisher_show', methods: ['GET'])]
    public function publisherShow(Request $request): Response {
        /** @var BoardgamePublisher */
        $publisher = $this->getEntity($request, BoardgamePublisher::class);

        $table = PinakesMapper::getMapper(Boardgame::class)
            ->createTable('list')
            ->addFilter('publisher', $publisher);
        return $this->renderList($request, 'Publisher: ' . (string) $publisher, $table, [
            $publisher->getLinkEdit(),
            $publisher->getLinkDelete(),
        ]);
    }

    #[Route('/boardgame/publisher/modal/{id?}', name: 'boardgamepublisher_modal', methods: ['GET', 'POST'])]
    public function publisherModal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(BoardgamePublisher::class);
        $publisher = $this->getEntity($request, $mapper) ?? new BoardgamePublisher();
        return $this->renderModal($request, $mapper, $publisher, 'boardgamepublisher_show');
    }

    #[Route('/boardgame/publisher/delete/{id}', name: 'boardgamepublisher_delete', methods: ['DELETE'])]
    public function publisherDelete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $publisher = $this->getEntity($request, BoardgamePublisher::class);
        PinakesMapper::getMapper(BoardgamePublisher::class)->delete($publisher);
        return $this->redirectHx('publisher');
    }
}
