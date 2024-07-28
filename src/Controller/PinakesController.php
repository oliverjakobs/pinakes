<?php

namespace App\Controller;

use App\Repository\PinakesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class PinakesController extends AbstractController {

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    private function getFields(PinakesRepository $repository, string $fields): array {
        $func = 'getDataFields' . ucwords($fields);

        assert(method_exists($repository, $func), $func . ' missing for ' . $repository::class);
        return $repository->$func();
    }

    public function renderTable(PinakesRepository $repository, string $fields, array $data = null): Response {
        return $this->render('table.html.twig', [
            'name' => $repository->getEntityName(),
            'data' => $data ?? $repository->findAll(),
            'fields' => $this->getFields($repository, $fields),
            'allow_add' => false
        ]);
    }

    public function renderTableContent(PinakesRepository $repository, string $fields, array $data = null): Response {
        return $this->render('tablecontent.html.twig', [
            'name' => $repository->getEntityName(),
            'data' => $data ?? $repository->findAll(),
            'fields' => $this->getFields($repository, $fields),
        ]);
    }

    public function renderShow(PinakesRepository $repository, int $id, string $fields): Response {
        $name = $repository->getEntityName();
        $entity = $repository->find($id);

        if (null === $entity) {
            throw $this->createNotFoundException($name . ' with id ' . $id . ' does not exist');
        }

        return $this->render('show.html.twig', [
            'name' => $name,
            'entity' => $entity,
            'fields' => $this->getFields($repository, $fields),
        ]);
    }

    public function redirectHx(string $route): Response {
        return new Response(headers: [
            'HX-Redirect' => $this->generateUrl($route)
        ]);
    }
}
