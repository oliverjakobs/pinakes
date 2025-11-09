<?php declare(strict_types=1);

namespace App\Controller;

use App\Pinakes\Link;
use App\Pinakes\ViewElement;
use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class PinakesController extends AbstractController {

    const DEFAULT_FILTER = [
        'order_by' => null,
        'order_dir' => 'desc',
        'page' => 1,
        'pp' => 30,
    ];

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function createLink(string $caption, string $route, array $parameters = []): ViewElement {
        $url = $this->generateUrl($route, $parameters);
        return ViewElement::anchor($caption, $url);
    }

    public function createLinkHx(string $caption, string $method, string $target, string $route, array $parameters = []): ViewElement {
        $url = $this->generateUrl($route, $parameters);
        return ViewElement::hxButton($caption, $url, $method, $target);
    }

    public function getActionShow(PinakesEntity $entity): ViewElement {
        $route = $entity->getModelName() . '_show';
        return $this->createLink('Show', $route, [ 'id' => $entity->getId() ]);
    }

    public function getActionEdit(PinakesEntity $entity): ViewElement {
        $route = $entity->getModelName() . '_form';
        return $this->createLinkHx('Edit', 'GET', '.show-main', $route, [ 'id' => $entity->getId() ]);
    }

    public function getActionDelete(PinakesEntity $entity): ViewElement {
        $route = $entity->getModelName() . '_delete';
        return $this->createLinkHx('Delete', 'DELETE', '', $route, [ 'id' => $entity->getId() ]);
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

    public function getDataFields(PinakesRepository $repository, string $fields): array {
        $result = $repository->getDataFields($fields);
        return array_filter($result, fn ($field) => isset($field['visibility']) ? $this->isGranted($field['visibility']) : true);
    }

    protected function pushFilterUrl(Response $response, Request $request, array $filter): Response {
        $page = $filter['page'];
        $query = array_filter([
            'search' => $filter['search'] ?? null,
            'page' => $page > 1 ? $page : null,
        ]);

        // TODO dont push if only order changed
        $referer = $request->headers->get('referer');
        if (null !== $referer) {
            $url = parse_url($referer, PHP_URL_PATH);
            if (!empty($query)) $url .= '?' . http_build_query($query);
            $response->headers->set('HX-Push-Url', $url);
        }

        return $response;
    }

    /**
     * @return array<array, bool>
     */
    protected function getQueryFilter(array $query): array {
        $filter = $query['filter'] ?? null;
        if (null === $filter) return [ $query, false ];

        unset($query['filter']);
        parse_str($filter, $result);
        return [ array_merge($query, $result), true ];
    }

    public function renderListFilter(Request $request, PinakesRepository $repository, string $title, string $fields = 'list', array $params = [], array $filter = []): Response {
        [ $query, $filter_only ] = $this->getQueryFilter($request->query->all());
        $filter = array_merge(self::DEFAULT_FILTER, $filter, $query);

        $params = array_merge([
            'title' => $title,
            'filter' => $filter,
            'data' => $repository->applyFilter($filter),
            'fields' => $this->getDataFields($repository, $fields),
            'allow_pagination' => true,
            'allow_ordering' => true
        ], $params);

        if ($filter_only) {
            $response = $this->render('components/table.html.twig', $params);
            return $this->pushFilterUrl($response, $request, $filter);
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

    public function renderForm(PinakesRepository $repository, PinakesEntity $entity, string $fields = 'show'): Response {
        return $this->render('components/form.html.twig', [
            'entity' => $entity,
            'fields' => $this->getDataFields($repository, $fields),
        ]);
    }

    protected function updateFromRequest(Request $request, PinakesRepository $repository, PinakesEntity $entity) {
        foreach ($request->request->all() as $key => $value) {
            $repository->update($entity, $key, $value ?? null);
        }
        $repository->save($entity);
    }

    public function redirectHx(string $route, array $parameters = []): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route, $parameters)
        ]);
    }
}
