<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Repository\SeriesRepository;
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
        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/series/form/{id?}', name: 'series_form', methods: ['GET'])]
    public function form(Request $request, seriesRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        return $this->render('form.html.twig', [
            'name' => self::getModelName(),
            'entity' => $this->getEntity($request, $repository) ?? new Series(),
            'fields' => $repository->getDataFields('show'),
        ]);
    }

    #[Route('/series/submit/{id}', name: 'series_submit', methods: ['POST'])]
    public function submit(Request $request, seriesRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $series = $this->getEntity($request, $repository) ?? new Series();

        $series->setName($request->request->get('name'));

        $repository->save($series);
        return $this->redirectToRoute('series_show', [ 'id' => $series->getId() ]);
    }
}
