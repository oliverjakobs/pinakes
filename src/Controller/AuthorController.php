<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\AuthorRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorController extends PinakesController {

    public function getModelName(): string {
        return 'author';
    }

    #[Route('/author', name: 'author', methods: ['GET'])]
    public function list(Request $request, AuthorRepository $repository): Response {
        return $this->renderList($request, $repository);
    }

    #[Route('/author/filter', name: 'author_filter', methods: ['GET'])]
    public function filter(Request $request, AuthorRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/author/show/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Request $request, AuthorRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => 'author',
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }
}
