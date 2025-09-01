<?php declare(strict_types=1);

namespace App\Controller;

use App\Pinakes\Link;
use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\Exception\MissingIdentifierField;

abstract class PinakesController extends AbstractController {

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public static function getModelName(): string { return ''; }

    public function createLink(string $caption, string $route, array $parameters = []): ?Link {
        return new Link($caption, $this->generateUrl($route, $parameters));
    }

    public function getActionShow(PinakesEntity $entity): Link {
        $route = static::getModelName() . '_show';
        return $this->createLink('Show', $route, [ 'id' => $entity->getId() ]);
    }

    public function getActionEdit(PinakesEntity $entity): Link {
        $route = static::getModelName() . '_form';
        return $this->createLink('Edit', $route, [ 'id' => $entity->getId() ])->setHx('GET', '.show-main');
    }

    public function getActionDelete(PinakesEntity $entity): Link {
        $route = static::getModelName() . '_delete';
        return $this->createLink('Delete', $route, [ 'id' => $entity->getId() ])->setHx('DELETE');
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

    protected function getNavigationItems(): array {
        $items = json_decode(file_get_contents('../data/navigation.json'), true);

        $items = array_filter($items, fn ($item) => isset($item['role']) ? $this->isGranted($item['role']) : true);

        return $items;
    }

    protected function getFilter(array ...$params): array {
        $defaults = [
            'order_by' => null,
            'order_dir' => 'desc',
            'page' => 1,
            'pp' => 30,
        ];

        return array_merge($defaults, ...$params);
    }

    public function renderList(Request $request, string $title, array $params = []): Response {
        $defaults = [
            'title' => $title,
            'filter' => $this->getFilter($request->query->all()),
            'navigation' => $this->getNavigationItems()
        ];

        return $this->render('list.html.twig', array_merge($defaults, $params));
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

    public function renderFilter(Request $request, PinakesRepository $repository, string $fields='list'): Response {
        $filter = $this->getFilter($request->query->all());

        $data_fields = $repository->getDataFields($fields);
        $data_fields = array_filter($data_fields, fn ($field) => isset($field['visibility']) ? $this->isGranted($field['visibility']) : true);

        $response = $this->render('component/table.html.twig', [
            'data' => $repository->applyFilter($filter),
            'fields' => $data_fields,
            'filter' => $filter
        ]);
        return $this->pushFilterUrl($response, $request, $filter);
    }

    public function renderShow(PinakesRepository $repository, PinakesEntity $entity, string $fields = 'show', array $params = []) {
        $defaults = [
            'entity' => $entity,
            'fields' => $repository->getDataFields($fields),
            'navigation' => $this->getNavigationItems()
        ];

        return $this->render('show.html.twig', array_merge($defaults, $params));
    }

    public function renderForm(PinakesRepository $repository, PinakesEntity $entity, string $fields = 'show'): Response {
        return $this->render('component/form.html.twig', [
            'entity' => $entity,
            'fields' => $repository->getDataFields($fields),
        ]);
    }

    public function redirectHx(string $route, array $parameters = []): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route, $parameters)
        ]);
    }
}
