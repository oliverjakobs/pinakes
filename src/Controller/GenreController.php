<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\GenreRepository;
use App\Entity\Genre;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GenreController extends PinakesController {

    public static function getModelName(): string {
        return 'genre';
    }

    #[Route('/genre', name: 'genre', methods: ['GET'])]
    public function list(Request $request, GenreRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Genre');
    }

    #[Route('/genre/show/{id}', name: 'genre_show', methods: ['GET'])]
    public function show(Request $request, GenreRepository $repository): Response {
        $genre = $this->getEntity($request, $repository);

        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $genre,
            'fields' => $repository->getDataFields('show'),
            'actions' => [
                $this->getActionEdit($genre),
                $this->getActionDelete($genre),
            ]
        ]);
    }

    #[Route('/genre/delete/{id}', name: 'genre_delete', methods: ['DELETE'])]
    public function delete(Request $request, GenreRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $genre = $this->getEntity($request, $repository);
        $repository->delete($genre);

        return $this->redirectHx('genre');
    }

    #[Route('/genre/form/{id}', name: 'genre_form', methods: ['GET', 'POST'])]
    public function form(Request $request, GenreRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $genre = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $genre->name = $request->request->get('name');
            $genre->color = $request->request->get('color');

            $repository->save($genre);
            return $this->redirectToRoute('genre_show', [ 'id' => $genre->getId() ]);
        }

        return $this->renderForm($repository, $genre);
    }
}
