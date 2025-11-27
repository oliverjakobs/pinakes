<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends PinakesController {

    #[Route('/tag', name: 'tag', methods: ['GET'])]
    public function list(Request $request, TagRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Tags', params: [
            'actions' => [
                $this->createButtonModal('New Tag', 'tag_modal'),
            ]
        ]);
    }

    #[Route('/tag/show/{id}', name: 'tag_show', methods: ['GET'])]
    public function show(Request $request, TagRepository $repository, BookRepository $books): Response {
        $tag = $this->getEntity($request, $repository);
        return $this->renderListFilter($request, $books, 'Tag: ' . (string) $tag,
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

        $tag = $this->getEntity($request, $repository);
        if (null === $tag) {
            $tag = $repository->getTemplate();
        }

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->updateFromRequest($request, $repository, $tag);
            return $this->redirectToRoute('tag_show', [ 'id' => $tag->getId() ]);
        }

        return $this->renderModal($repository, $tag);
    }

    #[Route('/tag/delete/{id}', name: 'tag_delete', methods: ['DELETE'])]
    public function delete(Request $request, TagRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $tag = $this->getEntity($request, $repository);
        $repository->delete($tag);

        return $this->redirectHx('tag');
    }
}
