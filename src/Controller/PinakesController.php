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

    abstract public static function getModelName(): string;

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

    protected function getFilter(Request $request, array $params = []): array {
        $defaults = [
            'order_by' => null,
            'order_dir' => 'desc',
            'page' => 1,
            'pp' => 30,
        ];

        return array_merge($defaults, $request->query->all(), $params);
    }

    public function renderList(Request $request, string $title, array $params = []): Response {
        $defaults = [
            'title' => $title,
            'filter' => $this->getFilter($request, $params),
        ];

        return $this->render('list.html.twig', array_merge($defaults, $params));
    }

    public function renderForm(PinakesRepository $repository, PinakesEntity $entity, string $fields = 'show'): Response {
        return $this->render('component/form.html.twig', [
            'entity' => $entity,
            'fields' => $repository->getDataFields($fields),
        ]);
    }

    public function renderTable(PinakesRepository $repository, array $filter, string $fields='list'): string {
        return $this->renderView('component/table.html.twig', [
            'data' => $repository->applyFilter($filter),
            'fields' => $repository->getDataFields($fields),
            'filter' => $filter
        ]);
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
        $filter = $this->getFilter($request);

        $response = new Response($this->renderTable($repository, $filter, $fields));
        return $this->pushFilterUrl($response, $request, $filter);
    }

    public function redirectHx(string $route, array $parameters = []): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route, $parameters)
        ]);
    }
}
