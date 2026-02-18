<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\PinakesEntity;
use App\Pinakes\DataTable;
use App\Pinakes\Helper;
use App\Repository\PinakesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class PinakesController extends AbstractController {

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    protected function getEntity(Request $request, PinakesRepository $repository): ?PinakesEntity {
        $id = $request->attributes->get('id');
        if (null === $id) return null;

        $entity = $repository->find($id);
        if (null === $entity) {
            throw $this->createNotFoundException('entity with id ' . $id . ' does not exist');
        }

        return $entity;
    }

    protected function pushFilterUrl(Response $response, Request $request, array $filter): Response {
        $diff = [
            'order_dir' => null,
            'order_by' => null,
            'pp' => null
        ];

        if (1 === intval($filter['page'])) {
            $diff['page'] = null;
        }

        $query = array_diff_key($filter, $diff);

        // TODO dont push if only order changed
        $referer = $request->headers->get('referer');
        if (null !== $referer) {
            $url = parse_url($referer, PHP_URL_PATH);
            if (!empty($query)) $url .= '?' . http_build_query($query);
            $response->headers->set('HX-Push-Url', $url);
        }

        return $response;
    }

    protected function getQueryFilter(array $query, &$filter_only = false): array {
        $filter = $query['filter'] ?? null;
        if (null === $filter) return $query;

        unset($query['filter']);
        parse_str($filter, $result);
        $filter_only = true;
        return array_merge($result, $query);
    }

    public function renderList(Request $request, string $title, DataTable $table, array $actions = [], array $filter_form = []): Response {
        $query = $this->getQueryFilter(array_filter($request->query->all()), $filter_only);
        
        $table->applyFilter($query);

        $params = [
            'title' => $title,
            // TODO only table as param
            'filter' => $table->getFilter(),
            'repository' => $table->getRepository(),
            'data' => $table->getData(),
            'fields' => $table->getDataFields(),
            'allow_pagination' => true,
            'allow_ordering' => true,
            'component_path' => 'components/table.html.twig',
            'actions' => $actions,
            'filter_form' => $filter_form
        ];

        if ($filter_only) {
            $response = $this->render($params['component_path'], $params);
            return $this->pushFilterUrl($response, $request, $table->getFilter());
        }

        return $this->render('list.html.twig', $params);
    }

    public function renderShow(PinakesRepository $repository, PinakesEntity $entity, string $fields = 'show', array $params = []) {
        $defaults = [
            'entity' => $entity,
            'fields' => $repository->getDataFields($fields)
        ];

        return $this->render('show.html.twig', array_merge($defaults, $params));
    }

    public function renderModal(Request $request, PinakesRepository $repository, string $redirect, string $fields = 'show'): Response {
        $entity = $this->getEntity($request, $repository);
        if (null === $entity) {
            $entity = $repository->getTemplate();
        }

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

    protected function updateEntityAndRedirect(Request $request, PinakesRepository $repository, PinakesEntity $entity, string $redirect): Response {
        foreach ($request->request->all() as $name => $value) {
            if (is_array($value)) $value = Helper::filterEmpty($value);
            if (Helper::isEmpty($value)) $value = null;
            $repository->update($entity, $name, $value);
        }
        $repository->save($entity);
        
        return $this->redirectToRoute($redirect, [ 'id' => $entity->getId() ]);
    }

    protected function deleteEntityAndRedirect(Request $request, PinakesRepository $repository, string $redirect): Response {
        $entity = $this->getEntity($request, $repository);

        // TODO check if delete is allowed (e.g. author still has books)
        $repository->delete($entity);

        return $this->redirectHx($redirect);
    }

    public function redirectHx(string $route, array $parameters = []): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route, $parameters)
        ]);
    }
}
