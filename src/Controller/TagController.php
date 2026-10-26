<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Tag;
use App\Entity\User;
use App\Mapper\PinakesMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TagController extends PinakesController {

    #[Route('/tag', name: 'tag', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PinakesMapper::getMapper(Tag::class)->createTable('list');
        return $this->renderList($request, 'Tags', $table);
    }

    #[Route('/tag/show/{id}', name: 'tag_show', methods: ['GET'])]
    public function show(Request $request): Response {
        /** @var Tag */
        $tag = $this->getEntity($request, Tag::class);

        $table = PinakesMapper::getMapper(Book::class)
            ->createTable('list_publisher')
            ->addFilter('tags', $tag);
        return $this->renderList($request, 'Tag: ' . (string) $tag, $table, [
            $tag->getLinkEdit(),
            $tag->getLinkDelete(),
        ]);
    }

    #[Route('/tag/modal/{id?}', name: 'tag_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Tag::class);
        $tag = $this->getEntity($request, $mapper) ?? new Tag();
        return $this->renderModal($request, $mapper, $tag, 'tag_show');
    }

    #[Route('/tag/delete/{id}', name: 'tag_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $tag = $this->getEntity($request, Tag::class);
        PinakesMapper::getMapper(Tag::class)->delete($tag);
        return $this->redirectHx('tag');
    }

    #[Route('/tag/export', name: 'tag_export', methods: ['GET'])]
    public function export(Request $request): Response {
        return new Response();
    }
}
