<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Renderable\Link;
use App\Repository\TagRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TagController extends PinakesController {

    #[Route('/tag', name: 'tag', methods: ['GET'])]
    public function list(Request $request, TagRepository $repository): Response {
        return $this->renderList($request, $repository, 'Tags', params: [
            'actions' => [
                Link::modal('New Tag', 'tag_modal'),
            ]
        ]);
    }

    #[Route('/tag/show/{id}', name: 'tag_show', methods: ['GET'])]
    public function show(Request $request, TagRepository $repository, BookRepository $books): Response {
        $tag = $this->getEntity($request, $repository);
        return $this->renderList($request, $books, 'Tag: ' . (string) $tag,
            params: [ 'actions' => [
                $tag->getLinkEdit(),
                $tag->getLinkDelete(),
            ]],
            filter: [ 'tag' => $tag->getId() ]
        );
    }

    #[Route('/tag/modal/{id?}', name: 'tag_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, TagRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->renderModal($request, $repository, 'tag_show');
    }

    #[Route('/tag/delete/{id}', name: 'tag_delete', methods: ['DELETE'])]
    public function delete(Request $request, TagRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        return $this->deleteEntityAndRedirect($request, $repository, 'tag');
    }
}
