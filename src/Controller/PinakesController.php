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

    protected function getEntity(Request $request, PinakesRepository $repository): ?PinakesEntity {
        $id = $request->attributes->get('id');
        if (null === $id) return null;

        $entity = $repository->find($id);
        if (null === $entity) {
            throw $this->createNotFoundException('entity with id ' . $id . ' does not exist');
        }

        return $entity;
    }

    protected function getFilter(Request $request): array {
        $filter = [
            'order_by' => $request->query->get('order_by'),
            'order_dir' => $request->query->get('order_dir', 'desc'),
            'page' => $request->query->get('page', 1),
            'pp' => $request->query->get('pp', 30),
        ];

        return array_merge($request->query->all(), $filter);
    }

    public function renderList(Request $request, ?array $control = null): Response {
        return $this->render('list.html.twig', [
            'name' => static::getModelName(),
            'filter' => $this->getFilter($request),
            'control' => $control
        ]);
    }

    public function renderTable(PinakesRepository|string $repository, array $filter, string $fields='list'): string {
        if (is_string($repository)) $repository = $this->em->getRepository($repository);

        return $this->renderView('table.html.twig', [
            'data' => $repository->applyFilter($filter),
            'fields' => $repository->getDataFields($fields),
            'filter' => $filter
        ]);
    }

    public function renderFilter(Request $request, PinakesRepository $repository, string $fields='list'): Response {
        $response = new Response();

        $filter = $this->getFilter($request);

        $id = $request->attributes->get('id');
        if (null !== $id) {
            $filter[static::getModelName()] = $id;
        }

        $response->setContent($this->renderTable($repository, $filter, $fields));
        $response->setStatusCode(Response::HTTP_OK);

        $page = $filter['page'];
        $query = array_filter([
            'search' => $filter['search'] ?? null,
            'page' => $page > 1 ? $page : null,
        ]);

        if (!empty($query)) {
            $referer = $request->headers->get('referer');
            $url = parse_url($referer, PHP_URL_PATH) . '?' . http_build_query($query);
            $response->headers->set('HX-Push-Url', $url);
        }

        return $response;
    }

    public function redirectHx(string $route): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route)
        ]);
    }
}
