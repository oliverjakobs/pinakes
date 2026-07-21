<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Medium;
use App\Entity\Record;
use App\Entity\User;
use App\Renderable\Link;
use App\Repository\ArtistRepository;
use App\Repository\RecordLabelRepository;
use App\Repository\RecordRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class RecordController extends PinakesController {

    #[Route('/record', name: 'record', methods: ['GET'])]
    public function list(Request $request, RecordRepository $repository): Response {
        return $this->renderList($request, 'Records', $repository->createTable(), [
                Link::modal('New Record', 'record_modal'),
            ]
        );
    }

    #[Route('/record/cd', name: 'record_cd', methods: ['GET'])]
    public function cd(Request $request, RecordRepository $repository): Response {
        $medium = Medium::CD;
        return $this->renderList($request, 'Records', $repository->createTable()->addFilter('medium', $medium), [
                Link::modal('New CD', 'record_modal', [ 'medium' => $medium->value ]),
            ]
        );
    }

    #[Route('/record/vinyl', name: 'record_vinyl', methods: ['GET'])]
    public function vinyl(Request $request, RecordRepository $repository): Response {
        $medium = Medium::Vinyl;
        return $this->renderList($request, 'Records', $repository->createTable()->addFilter('medium', $medium), [
                Link::modal('New Vinyl', 'record_modal', [ 'medium' => $medium->value ]),
            ]
        );
    }
    

    #[Route('/record/show/{id}', name: 'record_show', methods: ['GET'])]
    public function show(Request $request, RecordRepository $repository): Response {
        $record = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $record, 'show', [
            $record->getLinkEdit(),
            $record->getLinkDelete(),
        ]);
    }

    #[Route('/record/modal/{id?}', name: 'record_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, RecordRepository $repository, #[MapQueryParameter] ?string $medium = null): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        /** @var Record */
        $record = $this->getEntity($request, $repository) ?? $repository->create();

        if (null !== $medium) {
            $record->setMedium($medium);
        }

        return $this->renderModal($request, $repository, $record, 'record_show');
    }

    #[Route('/record/delete/{id}', name: 'record_delete', methods: ['DELETE'])]
    public function delete(Request $request, RecordRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $entity = $this->getEntity($request, $repository);
        return $this->deleteEntityAndRedirect($request, $repository, $entity, 'record');
    }

    #[Route('/artist/show/{id}', name: 'artist_show', methods: ['GET'])]
    public function artist(Request $request, ArtistRepository $repository): Response {
        $artist = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $artist, 'show', [
            $artist->getLinkEdit(),
            $artist->getLinkDelete(),
        ]);
    }

    #[Route('/recordlabel/show/{id}', name: 'recordlabel_show', methods: ['GET'])]
    public function recordlabel(Request $request, RecordLabelRepository $repository): Response {
        $label = $this->getEntity($request, $repository);

        return $this->renderShow($repository, $label, 'show', [
            $label->getLinkEdit(),
            $label->getLinkDelete(),
        ]);
    }
}
