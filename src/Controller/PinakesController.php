<?php declare(strict_types=1);

namespace App\Controller;

use App\Database\Database;
use App\Entity\Book;
use App\Entity\PinakesEntity;
use App\Mapper\PinakesMapper;
use App\Pinakes\Assert;
use App\Pinakes\DataTable;
use App\Pinakes\DataTable2;
use App\Pinakes\Helper;
use App\Pinakes\Pinakes;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use App\Repository\PinakesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class PinakesController extends AbstractController {

    protected function getEntity(Request $request, PinakesRepository $repository): ?PinakesEntity {
        $id = $request->attributes->get('id');
        if (null === $id) return null;

        $entity = $repository->find($id);
        if (null === $entity) {
            throw $this->createNotFoundException('entity with id ' . $id . ' does not exist');
        }

        return $entity;
    }

    protected function getEntityNew(Request $request, PinakesMapper $mapper, array $fields): ?PinakesEntity {
        $id = $request->attributes->get('id');
        if (null === $id) return null;

        $query = $mapper->createQuery($fields);

        $query->addWhere('book.id = :id');
        $query->setParameter(':id', $id);

        $db = Database::getInstance();
        $rows = $db->query($query);

        $entity = Database::hydrate(new Book(), array_first($rows));
        if (null === $entity) {
            throw $this->createNotFoundException('entity with id ' . $id . ' does not exist');
        }

        return $entity;
    }

    protected function pushFilterUrl(Response $response, Request $request, DataTable|DataTable2 $table): Response {
        // TODO dont push if only order changed
        $referer = $request->headers->get('referer');
        if (null !== $referer) {
            $query = $table->buildQuery();

            $url = parse_url($referer, PHP_URL_PATH);
            if (!empty($query)) $url .= '?' . $query;
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

    public function renderList(Request $request, string $title, DataTable $table, array $actions = [], array $filters = []): Response {
        $filter_only = $table->setQuery($request->query->all());
        $table->finalize();

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
    
    public function renderList2(Request $request, string $title, DataTable2 $table, array $actions = [], array $filters = []): Response {
        $filter_only = $table->setQuery($request->query->all());

        if ($filter_only) {
            $response = $this->render('components/table2.html.twig', [ 'table' => $table ]);
            return $this->pushFilterUrl($response, $request, $table);
        }

        return $this->render('list.html.twig', [
            'title' => $title,
            'table' => $table,
            'actions' => $this->processActions($actions),
            'filters' => $filters
        ]);
    }

    public function renderShow(PinakesRepository $repository, PinakesEntity $entity, string $fields = 'show', array $actions = []) {
        return $this->render('show.html.twig', [
            'entity' => $entity,
            'fields' => $repository->getDataFields($fields),
            'actions' => $this->processActions($actions),
        ]);
    }

    public function renderShow2(PinakesEntity $entity, array $fields, array $actions = []) {
        return $this->render('show.html.twig', [
            'entity' => $entity,
            'fields' => $fields,
            'actions' => $this->processActions($actions),
        ]);
    }

    public function renderModal(Request $request, PinakesRepository $repository, PinakesEntity $entity, string $redirect, string $fields = 'show'): Response {
        if (Request::METHOD_POST === $request->getMethod()) {
            return $this->updateEntityAndRedirect($request, $repository, $entity, $redirect);
        }
        
        $caption = $entity->getId() ? 'Edit ' : 'Create ';
        return $this->render('modals/entity.html.twig', [
            'caption' => $caption . $entity->getModelName(),
            'entity' => $entity,
            'fields' => $repository->getDataFields($fields),
        ]);
    }

    public function renderModal2(Request $request, PinakesMapper $mapper, PinakesEntity $entity, string $redirect, array $fields): Response {
        if (Request::METHOD_POST === $request->getMethod()) {
            $mapper->updateEntity($entity, $request->request->all());
            return $this->redirectToRoute($redirect, [ 'id' => $entity->getId() ]);
        }
        
        $caption = $entity->getId() ? 'Edit ' : 'Create ';
        return $this->render('modals/entity.html.twig', [
            'caption' => $caption . $entity->getModelName(),
            'entity' => $entity,
            'fields' => $fields,
        ]);
    }

    public function updateEntityAndRedirect(Request $request, PinakesRepository $repository, PinakesEntity $entity, string $redirect): Response {
        foreach ($request->request->all() as $name => $value) {
            if (is_array($value)) $value = Helper::filterEmpty($value);
            if (Helper::isEmpty($value)) $value = null;

            $col = $repository->getColumn($name);
            Assert::notNull($col, 'Unknown column ' . $name);

            $col->updateEntity($entity, $value);
        }
        $repository->save($entity);
        
        return $this->redirectToRoute($redirect, [ 'id' => $entity->getId() ]);
    }

    public function deleteEntityAndRedirect(Request $request, PinakesRepository $repository, PinakesEntity $entity, string $redirect): Response {
        assert(Helper::strEmpty($entity->getMessageDelete()), 'Cannot delete ' . $entity . ': ' . $entity->getMessageDelete());
        // $repository->delete($entity);

        return $this->redirectHx($redirect);
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
