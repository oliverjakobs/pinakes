<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\PublisherRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublisherController extends PinakesController {

    public static function getModelName(): string {
        return 'publisher';
    }

    #[Route('/publisher', name: 'publisher', methods: ['GET'])]
    public function list(Request $request, PublisherRepository $repository): Response {
        return $this->renderList($request, $repository);
    }

    #[Route('/publisher/filter', name: 'publisher_filter', methods: ['GET'])]
    public function filter(Request $request, PublisherRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/publisher/show/{id}', name: 'publisher_show', methods: ['GET'])]
    public function show(Request $request, PublisherRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
            'content' => [
                'title' => 'Books',
                'filter' => ['pp' => 10] + $this->getFilter($request),
                'route' => 'publisher_show_filter'
            ]
        ]);
    }

    #[Route('/publisher/show/{id}/filter', name: 'publisher_show_filter', methods: ['GET'])]
    public function showFilter(Request $request, BookRepository $repository): Response {
        return $this->renderFilter($request, $repository, 'list_publisher');
    }

    #[Route('/publisher/form/{id?}', name: 'publisher_form', methods: ['GET'])]
    public function form(Request $request, PublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        return $this->render('form.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository) ?? new Publisher(),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/publisher/submit/{id}', name: 'publisher_submit', methods: ['POST'])]
    public function submit(Request $request, PublisherRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $publisher = $this->getEntity($request, $repository) ?? new Publisher();

        $publisher->setName($request->request->get('name'));

        $repository->save($publisher);
        return $this->redirectToRoute('publisher_show', [ 'id' => $publisher->getId() ]);
    }
}
