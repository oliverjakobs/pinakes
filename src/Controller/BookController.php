<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends PinakesController {

    public function getModelName(): string {
        return 'book';
    }

    #[Route('/book', name: 'book', methods: ['GET'])]
    public function list(Request $request, BookRepository $repository): Response {
        return $this->renderList($request);
    }

    #[Route('/book/filter', name: 'book_filter', methods: ['GET'])]
    public function filter(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/book/show/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Request $request, BookRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => $this->getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/book/edit/{id}', name: 'book_edit', methods: ['GET'])]
    public function edit(Request $request, BookRepository $repository): Response {
        return $this->render('edit.html.twig', [
            'name' => $this->getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/book/submit/{id}', name: 'book_submit', methods: ['GET'])]
    public function submit(Request $request, BookRepository $repository): Response {
        echo $request->query->get('title') . PHP_EOL;

        for ($i = 0; ; ++$i) {
            $author = $request->query->get('authors' . $i);
            if (empty($author)) break;

            echo $author . PHP_EOL;
        }
        echo $request->query->get('publisher') . PHP_EOL;
        echo $request->query->get('published') . PHP_EOL;
        echo $request->query->get('first_published') . PHP_EOL;
        echo $request->query->get('isbn') . PHP_EOL;

        return $this->redirectHx('book');
    }
}
