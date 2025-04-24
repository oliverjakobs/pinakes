<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\PublisherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublisherController extends PinakesController {

    #[Route('/publisher', name: 'publisher', methods: ['GET'])]
    public function list(Request $request, PublisherRepository $repository): Response {
        return $this->render('table.html.twig', [
            'name' => 'publisher',
            'data' => $this->getEntityList($request, $repository),
            'fields' => $repository->getDataFields('list')
        ]);
    }

    #[Route('/publisher/filter', name: 'publisher_filter', methods: ['GET'])]
    public function filter(Request $request, PublisherRepository $repository): Response {
        return $this->renderFilter($request,
            'publisher',
            $this->getEntityList($request, $repository),
            $repository->getDataFields('list')
        );
    }

    #[Route('/publisher/show/{id}', name: 'publisher_show', methods: ['GET'])]
    public function show(Request $request, PublisherRepository $repository): Response {
        return $this->render('show.html.twig', [
            'name' => 'author',
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }
}