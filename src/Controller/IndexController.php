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
        return $this->render('index.html.twig', [
            'filter' => DataTable::DEFAULT_FILTER,
            'newest' => $books->getNewest(),
            'newest_fields' => $books->getDataFields('newest'),
            'balance' => $transactions->getBalance()
        ]);
    }

    #[Route('/bookfund', name: 'bookfund', methods: ['GET'])]
    public function bookfund(TransactionRepository $repository): Response {
        return $this->render('bookfund.html.twig', [
            'transactions' => $repository->findAll(['timestamp' => 'desc'], 6),
            'balance' => $repository->getBalance()
        ]);
    }

    #[Route('/bookfund/modal/{type}', name: 'bookfund_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request, string $type, TransactionRepository $repository): Response {
        if (Request::METHOD_POST === $request->getMethod()) {
            $amount = floatval($request->request->get('amount'));
            if ($type === 'withdrawal') $amount *= -1.0;

            $reason = $request->request->get('reason');

            $transaction = new Transaction();
            $transaction->amount = $amount;
            $transaction->reason = $reason;
            $transaction->timestamp = new \DateTime();

            $repository->save($transaction);

            return $this->redirectHx('bookfund');
        }

        return $this->render('modals/transaction.html.twig', [
            'type' => $type
        ]);
    }

    #[Route('/booksearch', name: 'booksearch', methods: ['GET'])]
    public function booksearch(): Response {
        return $this->render('booksearch.html.twig', [ ]);
    }
}
