<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use App\Repository\TagRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TagController extends PinakesController {

    #[Route('/tag', name: 'tag', methods: ['GET'])]
    public function list(Request $request, TagRepository $repository): Response {
        return $this->renderList($request, 'Tags', $repository->createTable(), [
            Link::modal('New Tag', 'tag_modal'),
            ViewElement::separator(),
            Link::create('Export Tags', 'tag_export')
        ]);
    }

    #[Route('/tag/show/{id}', name: 'tag_show', methods: ['GET'])]
    public function show(Request $request, TagRepository $repository, BookRepository $books): Response {
        $tag = $this->getEntity($request, $repository);

        $table = $books->createTable()->addFilter('tags', $tag->getId());
        return $this->renderList($request, 'Tag: ' . (string) $tag, $table, [
            $tag->getLinkEdit(),
            $tag->getLinkDelete(),
        ]);
    }

    #[Route('/tag/modal/{id?}', name: 'tag_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, TagRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository) ?? $repository->getTemplate();
        return $this->renderModal($request, $repository, $entity, 'tag_show');
    }

    #[Route('/tag/delete/{id}', name: 'tag_delete', methods: ['DELETE'])]
    public function delete(Request $request, TagRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository);
        return $this->deleteEntityAndRedirect($request, $repository, $entity, 'tag');
    }

    #[Route('/tag/export', name: 'tag_export', methods: ['GET'])]
    public function export(TagRepository $repository): Response {
        $table = $repository->createTable('export');
        return $this->exportCsv($table, 'tags');
    }
}
