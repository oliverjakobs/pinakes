<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Medium;
use App\Entity\Record;
use App\Entity\RecordLabel;
use App\Entity\User;
use App\Mapper\PinakesMapper;
use App\Renderable\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class RecordController extends PinakesController {

    #[Route('/record', name: 'record', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PinakesMapper::getMapper(Record::class)->createTable('list');
        return $this->renderList($request, 'Records', $table, [
            Link::modal('New Book', 'record_modal'),
        ]);
    }

    #[Route('/record/cd', name: 'record_cd', methods: ['GET'])]
    public function cd(Request $request): Response {
        $medium = Medium::CD;
        $table = PinakesMapper::getMapper(Record::class)->createTable('list')->addFilter('medium', $medium);
        return $this->renderList($request, 'Records', $table, [
            Link::modal('New CD', 'record_modal', [ 'medium' => $medium->value ]),
        ]);
    }

    #[Route('/record/vinyl', name: 'record_vinyl', methods: ['GET'])]
    public function vinyl(Request $request): Response {
        $medium = Medium::Vinyl;
        $table = PinakesMapper::getMapper(Record::class)->createTable('list')->addFilter('medium', $medium);
        return $this->renderList($request, 'Records', $table, [
            Link::modal('New Vinyl', 'record_modal', [ 'medium' => $medium->value ]),
        ]);
    }

    #[Route('/record/show/{id}', name: 'record_show', methods: ['GET'])]
    public function show(Request $request): Response {
        $mapper = PinakesMapper::getMapper(Record::class);
        /** @var Record */
        $record = $this->getEntity($request, $mapper);
        return $this->renderShow($record, $mapper->getDataFields('show'), [
            $record->getLinkEdit(),
            $record->getLinkDelete(),
        ]);
    }

    #[Route('/record/modal/{id?}', name: 'record_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, #[MapQueryParameter] ?string $medium = null): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Record::class);
        
        /** @var Record */
        $record = $this->getEntity($request, $mapper);
        if (null === $record) {
            $record = new Record();
        }

        if (null !== $medium) {
            $record->setMedium($medium);
        }

        return $this->renderModal($request, $mapper, $record, 'record_show');
    }

    #[Route('/record/delete/{id}', name: 'record_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $record = $this->getEntity($request, Record::class);
        PinakesMapper::getMapper(Record::class)->deleteEntity($record);
        return $this->redirectHx('record');
    }

    #[Route('/artist', name: 'artist', methods: ['GET'])]
    public function artistList(Request $request): Response {
        $table = PinakesMapper::getMapper(Artist::class)->createTable('list');
        return $this->renderList($request, 'Artists', $table);
    }

    #[Route('/artist/show/{id}', name: 'artist_show', methods: ['GET'])]
    public function artistShow(Request $request): Response {
        /** @var Artist */
        $artist = $this->getEntity($request, Artist::class);

        $table = PinakesMapper::getMapper(Record::class)
            ->createTable('list')
            ->addFilter('artist', $artist);
        return $this->renderList($request, 'Artist: ' . (string) $artist, $table, [
            $artist->getLinkEdit(),
            $artist->getLinkDelete(),
        ]);
    }

    #[Route('/artist/modal/{id}', name: 'artist_modal', methods: ['GET'])]
    public function artistModal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Artist::class);
        $artist = $this->getEntity($request, $mapper) ?? new Artist();
        return $this->renderModal($request, $mapper, $artist, 'artist_show');
    }

    #[Route('/artist/delete/{id}', name: 'artist_delete', methods: ['DELETE'])]
    public function artistDelete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $artist = $this->getEntity($request, Artist::class);
        PinakesMapper::getMapper(Artist::class)->deleteEntity($artist);
        return $this->redirectHx('publisher');
    }

    #[Route('/recordlabel', name: 'recordlabel', methods: ['GET'])]
    public function labelList(Request $request): Response {
        $table = PinakesMapper::getMapper(RecordLabel::class)->createTable('list');
        return $this->renderList($request, 'Labels', $table);
    }

    #[Route('/recordlabel/show/{id}', name: 'recordlabel_show', methods: ['GET'])]
    public function labelShow(Request $request): Response {
        /** @var RecordLabel */
        $label = $this->getEntity($request, RecordLabel::class);

        $table = PinakesMapper::getMapper(Record::class)
            ->createTable('list')
            ->addFilter('label', $label);
        return $this->renderList($request, 'Label: ' . (string) $label, $table, [
            $label->getLinkEdit(),
            $label->getLinkDelete(),
        ]);
    }

    #[Route('/recordlabel/modal/{id}', name: 'recordlabel_modal', methods: ['GET'])]
    public function labelModal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(RecordLabel::class);
        $label = $this->getEntity($request, $mapper) ?? new RecordLabel();
        return $this->renderModal($request, $mapper, $label, 'artist_show');
    }

    #[Route('/recordlabel/delete/{id}', name: 'recordlabel_delete', methods: ['DELETE'])]
    public function labelDelete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $label = $this->getEntity($request, RecordLabel::class);
        PinakesMapper::getMapper(Artist::class)->deleteEntity($label);
        return $this->redirectHx('publisher');
    }
}
