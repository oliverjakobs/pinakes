<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Repository\SeriesRepository;
use App\Repository\SeriesVolumeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SeriesController extends PinakesController {

    public static function getModelName(): string {
        return 'series';
    }

    #[Route('/series', name: 'series', methods: ['GET'])]
    public function list(Request $request, SeriesRepository $repository): Response {
        return $this->renderList($request);
    }

    #[Route('/series/filter', name: 'series_filter', methods: ['GET'])]
    public function filter(Request $request, SeriesRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/series/show/{id}', name: 'series_show', methods: ['GET'])]
    public function show(Request $request, SeriesRepository $repository): Response {
        $series = $this->getEntity($request, $repository);

        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $series,
            'fields' => $repository->getDataFields('show'),
            'actions' => [
                $this->getActionEdit($series),
                $this->getActionDelete($series),
            ],
            'content' => [
                'title' => 'Volumes',
                'filter' => ['pp' => 10] + $this->getFilter($request),
                'route' => 'series_show_filter'
            ]
        ]);
    }

    #[Route('/series/show/{id}/filter', name: 'series_show_filter', methods: ['GET'])]
    public function showFilter(Request $request, SeriesVolumeRepository $repository): Response {
        return $this->renderFilter($request, $repository, 'list');
    }

    #[Route('/series/delete/{id}', name: 'series_delete', methods: ['DELETE'])]
    public function delete(Request $request, BookRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $series = $this->getEntity($request, $repository);
        $repository->delete($series);

        return $this->redirectHx('series');
    }

    #[Route('/series/form/{id}', name: 'series_form', methods: ['GET', 'POST'])]
    public function form(Request $request, seriesRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $series = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $series->name = $request->request->get('name');

            $repository->save($series);
            return $this->redirectToRoute('series_show', [ 'id' => $series->getId() ]);
        }

        return $this->renderForm($repository, $series);
    }
}
