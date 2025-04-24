<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\PinakesEntity;
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

    public function getModelName(): string {
        return '';
    }

    protected function getEntityList( Request $request, PinakesRepository $repository): array {
        $search = $request->get('search');
        $order_by = null;
        
        $order_field = $request->get('order_by');
        if (null !== $order_field) {
            $order_by = [ $order_field => $request->query->get('order_dir', 'asc')];
        }

        return $repository->search($search, $order_by);
    }

    protected function getEntity(Request $request, PinakesRepository $repository): PinakesEntity {
        $id = $request->attributes->get('id');
        $entity = $repository->find($id);

        if (null === $entity) {
            throw $this->createNotFoundException('entity with id ' . $id . ' does not exist');
        }

        return $entity;
    }

    public function renderTable(Request $request, PinakesRepository $repository, string $fields): string {
        return $this->renderView('tablecontent.html.twig', [
            'name' => $this->getModelName(),
            'data' => $this->getEntityList($request, $repository),
            'fields' => $repository->getDataFields($fields),
            'query' => [
                'search' => $request->query->get('search'),
                'order_by' => $request->query->get('order_by'),
                'order_dir' => $request->query->get('order_dir', 'desc'),
                'page' => $request->get('page', 1),
                'pp' => 30
            ]
        ]);
    }

    public function renderFilter(Request $request, PinakesRepository $repository, string $fields): Response {
        $response = new Response();

        $response->setContent($this->renderTable($request, $repository, $fields));
        $response->setStatusCode(Response::HTTP_OK);

        $page = $request->get('page');

        $query = array_filter([
            'search' => $request->query->get('search'),
            'page' => $page > 1 ? $page : null,
        ]);

        $url = $this->generateUrl($this->getModelName());
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $response->headers->set('HX-Push-Url', $url);

        return $response;
    }

    public function redirectHx(string $route): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route)
        ]);
    }
}
