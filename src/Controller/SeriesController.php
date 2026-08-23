<?php declare(strict_types=1);

namespace App\Controller;

use App\Database\Database;
use App\Database\SQL\InsertJoin;
use App\Entity\Book;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\User;
use App\Mapper\PinakesMapper;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeriesController extends PinakesController {

    #[Route('/series', name: 'series', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PinakesMapper::getMapper(Series::class)->createTable('list');
        return $this->renderList2($request, 'Series', $table, [
            Link::modal('New Series', 'series_modal'),
        ]);
    }

    #[Route('/series/show/{id}', name: 'series_show', methods: ['GET'])]
    public function show(Request $request): Response {
        /** @var Series */
        $series = $this->getEntityNew($request, Series::class);

        $table = PinakesMapper::getMapper(Book::class)
            ->createTable('list_series')
            ->addFilter('series', $series)
            ->setOrder('series_volume');
        return $this->renderList2($request, 'Series: ' . (string) $series, $table, [
            Link::modal('Add Volume', 'book_modal', [ 'series' => $series->getId() ]),
            Link::modal('Add Tag', 'series_add_tag', [ 'id' => $series->getId() ]),
            ViewElement::separator(),
            $series->getLinkEdit(),
            $series->getLinkDelete(),
        ]);
    }

    #[Route('/series/modal/{id?}', name: 'series_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Series::class);
        $series = $this->getEntityNew($request, $mapper) ?? new Series();
        return $this->renderModal2($request, $mapper, $series, 'series_show');
    }

    #[Route('/series/delete/{id}', name: 'series_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $series = $this->getEntityNew($request, Series::class);
        PinakesMapper::getMapper(Series::class)->deleteEntity($series);
        return $this->redirectHx('series');
    }
    
    #[Route('/series/add-tag/{id}', name: 'series_add_tag', methods: ['GET', 'POST'])]
    public function addTag(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = PinakesMapper::getMapper(Series::class);
        /** @var Series */
        $series = $this->getEntityNew($request, $mapper);

        $tag_mapper = PinakesMapper::getMapper(Tag::class);

        if (Request::METHOD_POST === $request->getMethod()) {
            $tag = $request->request->get('tag');

            Database::getInstance()->asTransaction(function () use ($tag_mapper, $tag, $series) {
                $tag_id = $tag_mapper->upsert($tag);

                $field = $tag_mapper->getDataField('books');
                $join_table = $field->mapping->getJoinTable();

                foreach ($series->volumes as $vol) {
                    $insert = new InsertJoin($join_table, [
                        'tag_id' => $tag_id,
                        'book_id' => $vol->getId(),
                    ]);

                    Database::getInstance()->execute($insert);
                }
            });
            return $this->redirectToRoute('series_show', [ 'id' => $series->getId() ]);
        }

        return $this->render('modals/add_tag.html.twig', [
            'caption' => 'Select tag',
            'options' => $tag_mapper->getOptions()
        ]);
    }
}
