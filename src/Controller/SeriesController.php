<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Pinakes\ViewElement;
use App\Repository\SeriesRepository;
use App\Repository\BookRepository;
use App\Repository\TagRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SeriesController extends PinakesController {

    #[Route('/series', name: 'series', methods: ['GET'])]
    public function list(Request $request, SeriesRepository $repository): Response {
        return $this->renderListFilter($request, $repository, 'Series', params: [
            'actions' => [
                $this->createButtonModal('New Series', 'series_modal'),
            ]
        ]);
    }

    #[Route('/series/show/{id}', name: 'series_show', methods: ['GET'])]
    public function show(Request $request, SeriesRepository $repository, BookRepository $books): Response {
        $series = $this->getEntity($request, $repository);
        return $this->renderListFilter($request, $books, 'Series: ' . (string) $series,
            fields: 'list_series',
            params: [ 'actions' => [
                $this->createLinkHx('Add Volume', 'POST', '', 'book_create', [ 'series' => $series->getId() ]),
                $this->createButtonModal('Add Tag', 'series_add_tag', [ 'id' => $series->getId() ]),
                ViewElement::separator(),
                $series->getLinkEdit(),
                $series->getLinkDelete(),
            ]],
            filter: [ 'series' => $series->getId() ]
        );
    }

    #[Route('/series/modal/{id?}', name: 'series_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, SeriesRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $series = $this->getEntity($request, $repository);
        if (null === $series) {
            $series = new Series();
            $series->name = 'New Series';
        }

        if (Request::METHOD_POST === $request->getMethod()) {
            $this->updateFromRequest($request, $repository, $series);
            return $this->redirectToRoute('series_show', [ 'id' => $series->getId() ]);
        }

        return $this->renderModal($repository, $series);
    }

    #[Route('/series/delete/{id}', name: 'series_delete', methods: ['DELETE'])]
    public function delete(Request $request, SeriesRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $series = $this->getEntity($request, $repository);
        $repository->delete($series);

        return $this->redirectHx('series');
    }

    #[Route('/series/add-tag/{id}', name: 'series_add_tag', methods: ['GET', 'POST'])]
    public function addTag(Request $request, SeriesRepository $repository, TagRepository $tags): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $series = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $tag = $tags->getOrCreate($request->request->get('tag'));
            foreach ($series->volumes as $vol) {
                $vol->addTag($tag);
                $this->em->persist($vol);
            }
            $this->em->flush();
            return $this->redirectToRoute('series_show', [ 'id' => $series->getId() ]);
        }

        return $this->render('modals/add_tag.html.twig', [
            'caption' => 'Select tag',
            'options' => $tags->getOptions()
        ]);
    }
}
