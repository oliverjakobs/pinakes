<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\TagRepository;
use App\Entity\Tag;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends PinakesController {

    #[Route('/tag', name: 'tag', methods: ['GET'])]
    public function list(Request $request, TagRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Tags');
    }

    #[Route('/tag/show/{id}', name: 'tag_show', methods: ['GET'])]
    public function show(Request $request, TagRepository $repository): Response {
        $tag = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $tag, 'show', [
            'actions' => [
                $this->getActionEdit($tag),
                $this->getActionDelete($tag),
            ]
        ]);
    }

    #[Route('/tag/delete/{id}', name: 'tag_delete', methods: ['DELETE'])]
    public function delete(Request $request, TagRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $tag = $this->getEntity($request, $repository);
        $repository->delete($tag);

        return $this->redirectHx('tag');
    }

    #[Route('/tag/form/{id}', name: 'tag_form', methods: ['GET', 'POST'])]
    public function form(Request $request, TagRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $tag = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->updateFromRequest($request, $repository, $tag);
            return $this->redirectToRoute('tag_show', [ 'id' => $tag->getId() ]);
        }

        return $this->renderForm($repository, $tag);
    }
}
