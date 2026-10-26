<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\PinakesEntity;
use App\Mapper\PinakesMapper;
use App\Pinakes\DataTable;
use App\Pinakes\Helper;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class PinakesController extends AbstractController {

    protected function getEntity(Request $request, PinakesMapper|string $mapper): ?PinakesEntity {
        $id = $request->attributes->get('id');
        if (null === $id) return null;

        if (is_string($mapper)) {
            $mapper = PinakesMapper::getMapper($mapper);
        }

        $entity = $mapper->find((int) $id);
        if (null === $entity) {
            throw $this->createNotFoundException('entity with id ' . $id . ' does not exist');
        }

        return $entity;
    }

    protected function pushFilterUrl(Response $response, Request $request, DataTable $table): Response {
        // TODO dont push if only order changed
        $referer = $request->headers->get('referer');
        if (null !== $referer) {
            $query = [];

            $search = $table->getSearch();
            if (!Helper::isEmpty($search)) {
                $query['search'] = $search;
            }
            
            $page = $table->getPage();
            if (1 < $page) {
                $query['page'] = $page;
            }

            $url = parse_url($referer, PHP_URL_PATH);
            if (!Helper::isEmpty($query)) $url .= '?' . http_build_query($query);
            $response->headers->set('HX-Push-Url', $url);
        }

        return $response;
    }

    private function processActions(array $actions): array {
        $actions = array_filter($actions);
        if (Helper::isEmpty($actions)) return $actions;

        $first = array_first($actions);
        if ($first instanceof ViewElement && $first->isSeparator()) array_shift($actions);

        $last = array_last($actions);
        if ($last instanceof ViewElement && $last->isSeparator()) array_pop($actions);

        foreach ($actions as $action) {
            if ($action instanceof Link) $action->addStyleClasses('button');
        }
        return $actions;
    }

    /** @return array<array|bool> */
    public function parseQuery(Request $request): array {
        $query = $request->query->all();

        $filter_only = $query['filter_only'] ?? false;
        unset($query['filter_only']);

        return [ $query, $filter_only ];
    }
    
    public function renderList(Request $request, string $title, DataTable $table, array $actions = [], array $filters = []): Response {
        list($query, $filter_only) = $this->parseQuery($request);
        $table->setQuery($query);

        if ($filter_only) {
            $response = $this->render($table->component_path, [ 'table' => $table ]);
            return $this->pushFilterUrl($response, $request, $table);
        }

        return $this->render('list.html.twig', [
            'title' => $title,
            'table' => $table,
            'actions' => $this->processActions($actions),
            'filters' => $filters
        ]);
    }

    public function renderShow(PinakesEntity $entity, array $fields, array $actions = []) {
        return $this->render('show.html.twig', [
            'entity' => $entity,
            'fields' => $fields,
            'actions' => $this->processActions($actions),
        ]);
    }

    public function renderModal(Request $request, PinakesMapper $mapper, PinakesEntity $entity, string $redirect, string $fields = 'show'): Response {
        if (Request::METHOD_POST === $request->getMethod()) {
            $id = $mapper->save($entity, $request->request->all());
            return $this->redirectToRoute($redirect, [ 'id' => $id ]);
        }
        
        $caption = $entity->getId() ? 'Edit ' : 'Create ';
        return $this->render('modals/entity.html.twig', [
            'caption' => $caption . $entity->getModelName(),
            'entity' => $entity,
            'fields' => $mapper->getDataFields($fields),
        ]);
    }

    public function redirectHx(string $route, array $parameters = []): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route, $parameters)
        ]);
    }

    public function exportCsv(DataTable $table, string $filename): Response {
        $response = $this->render('export/export.csv.twig', [
            'table' => $table
        ]);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
        return $response;
    }
}
