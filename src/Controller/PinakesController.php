<?php declare(strict_types=1);

namespace App\Controller;

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

    public function createButtonModal(string $caption, string $route, array $parameters = []): ViewElement {
        $url = $this->generateUrl($route, $parameters);
        return ViewElement::buttonModal($caption, $url);
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
        return array_merge($query, $result);
    }

    public function renderList(Request $request, PinakesRepository $repository, string $title, string $fields = 'list', array $params = [], array $filter = []): Response {
        $query = $this->getQueryFilter(array_filter($request->query->all()), $filter_only);
        $filter = array_merge(self::DEFAULT_FILTER, $filter, $query);

        $params = array_merge([
            'title' => $title,
            'filter' => $filter,
            'data' => $repository->applyFilter($filter),
            'fields' => $this->getDataFields($repository, $fields),
            'allow_pagination' => true,
            'allow_ordering' => true,
            'component_path' => 'components/table.html.twig'
        ], $params);

        if ($filter_only) {
            $response = $this->render($params['component_path'], $params);
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
            'fields' => $this->getDataFields($repository, $fields),
        ]);
    }

    protected function updateEntityAndRedirect(Request $request, PinakesRepository $repository, PinakesEntity $entity, string $redirect): Response {
        foreach ($request->request->all() as $name => $value) {
            if (is_array($value)) $value = array_filter($value, fn($v) => !empty($v));
            //if (empty($value)) $value = null;

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
