<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transaction;
use App\Pinakes\DataTable;
use App\Repository\BookRepository;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends PinakesController {

    #[Route('/', name: 'pinakes')]
    public function index(BookRepository $books, TransactionRepository $transactions): Response {
        $newest = $books->createTable('newest')->setData($books->getNewest());
        $newest->allow_ordering = false;
        $newest->allow_pagination = false;

        return $this->render('index.html.twig', [
            'newest' => $newest,
            'balance' => $transactions->getBalance()
        ]);
    }

    #[Route('/booksearch', name: 'booksearch', methods: ['GET'])]
    public function booksearch(): Response {
        return $this->render('booksearch.html.twig', [ ]);
    }
}
