<?php declare(strict_types=1);

namespace App\Controller;

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

    private function parseOrderString(?string $order_by): ?array {
        if (null === $order_by) return null;

        $result = explode(' ', $order_by);
        $key = $result[0] ?? '';
        $dir = $result[1] ?? '';

        return [$key => $dir];
    }

    protected function parseOptions(Request $request): array {
        return [
            'search' => $request->get('search'),
            'order_by' => $this->parseOrderString($request->get('orderby'))
        ];
    }

    public function renderTable(PinakesRepository $repository, string $fields, array $data = null): Response {
        return $this->render('table.html.twig', [
            'name' => $repository->getEntityName(),
            'data' => $data ?? $repository->findAll(),
            'fields' => $repository->getDataFields($fields)
        ]);
    }

    public function renderSearch(PinakesRepository $repository, string $fields, ?string $search): Response {
        return $this->render('tablecontent.html.twig', [
            'name' => $repository->getEntityName(),
            'data' => $repository->search($search),
            'fields' => $repository->getDataFields($fields),
        ]);
    }

    public function renderTablecontent(PinakesRepository $repository, string $fields, array $options = array()): Response {
        return $this->render('tablecontent.html.twig', [
            'name' => $repository->getEntityName(),
            'data' => $repository->search($options['search'], $options['order_by']),
            'fields' => $repository->getDataFields($fields),
        ]);
    }

    public function redirectHx(string $route): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route)
        ]);
    }
}
