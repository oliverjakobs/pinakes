<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class IndexController extends AbstractController {

    private function renderNavigationItems(?string $parent = null): Response {
        $content = file_get_contents('../data/navigation.json');
        $items = json_decode($content, true);

        $filtered = array_filter($items, fn ($item) => $parent === ($item['parent'] ?? null));

        if (null !== $parent) {
            assert(array_key_exists($parent, $items), 'Unkown navigation item: ' . $parent);
            $parent_item = array_find($items, fn ($item) => $parent === $item['route']);
            $parent_item['route'] = $parent_item['parent'] ?? 'pinakes';
            $parent_item['icon'] = 'backspace';
            $parent_item['caption'] = 'Back';

            $filtered = [$parent_item, ...$filtered];
        }

        return $this->render('index.html.twig', [
            'navigation' => $filtered,
        ]);
    }

    #[Route('/', name: 'pinakes')]
    public function index(): Response {
        return $this->renderNavigationItems();
    }

    #[Route('/admin', name: 'admin')]
    public function admin(): Response {
        return $this->renderNavigationItems('admin');
    }
}
