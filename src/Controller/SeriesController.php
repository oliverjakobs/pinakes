<?php declare(strict_types=1);

namespace App\Controller;

use App\Database\Database;
use App\Database\SQL\Insert;
use App\Entity\Book;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\User;
use App\Mapper\BookMapper;
use App\Mapper\PinakesMapper;
use App\Mapper\SeriesMapper;
use App\Mapper\TagMapper;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeriesController extends PinakesController {

    #[Route('/series', name: 'series', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = SeriesMapper::getInstance()->createTable('list');
        return $this->renderList($request, 'Series', $table, [
            Link::modal('New Series', 'series_modal'),
        ]);
    }

    #[Route('/series/show/{id}', name: 'series_show', methods: ['GET'])]
    public function show(Request $request): Response {
        /** @var Series */
        $series = $this->getEntity($request, SeriesMapper::getInstance());

        $table = BookMapper::getInstance()
            ->createTable('list_series')
            ->addFilter('series', $series)
            ->setOrder('series_volume');
        return $this->renderList($request, 'Series: ' . (string) $series, $table, [
            Link::modal('Add Volume', 'book_modal', [ 'series' => $series->id ]),
            Link::modal('Add Tag', 'series_add_tag', [ 'id' => $series->id ]),
            ViewElement::separator(),
            $series->getLinkEdit(),
            $series->getLinkDelete(),
        ]);
    }

    #[Route('/series/modal/{id?}', name: 'series_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = SeriesMapper::getInstance();
        $series = $this->getEntity($request, $mapper) ?? new Series();
        return $this->renderModal($request, $mapper, $series, 'series_show');
    }

    #[Route('/series/delete/{id}', name: 'series_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = SeriesMapper::getInstance();
        $series = $this->getEntity($request, $mapper);
        $mapper->delete($series);
        return $this->redirectHx('series');
    }
    
    #[Route('/series/add-tag/{id}', name: 'series_add_tag', methods: ['GET', 'POST'])]
    public function addTag(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);

        $mapper = SeriesMapper::getInstance();
        /** @var Series */
        $series = $this->getEntity($request, $mapper);

        $tag_mapper = TagMapper::getInstance();

        if (Request::METHOD_POST === $request->getMethod()) {
            $tag = $request->request->get('tag');

            Database::getInstance()->asTransaction(function () use ($tag_mapper, $tag, $series) {
                $tag_id = $tag_mapper->upsert($tag);

                $mapping = $tag_mapper->getMapping('books');
                $join_table = $mapping->getJoinTable();

                foreach ($series->volumes as $vol) {
                    $insert = new Insert($join_table, false, true);
                    $insert->add('tag_id', $tag_id);
                    $insert->add('book_id', $vol->id);
                    Database::getInstance()->execute($insert);
                }
            });
            return $this->redirectToRoute('series_show', [ 'id' => $series->id ]);
        }

        return $this->render('modals/add_tag.html.twig', [
            'caption' => 'Select tag',
            'options' => $tag_mapper->getOptions()
        ]);
    }
}
