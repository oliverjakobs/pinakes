<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TransactionController extends PinakesController {

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

    #[Route('/transaction', name: 'transaction', methods: ['GET'])]
    public function list(Request $request, TransactionRepository $repository): Response {
        return $this->renderList($request, 'Transactions', $repository->createTable());
    }

    #[Route('/transaction/modal/{id}', name: 'transaction_modal', methods: ['GET', 'POST'])]
    public function modalTransaction(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        return $this->renderModal($request, $repository, 'transaction');
    }

    #[Route('/transaction/delete/{id}', name: 'transaction_delete', methods: ['DELETE'])]
    public function delete(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        return $this->deleteEntityAndRedirect($request, $repository, 'transaction');
    }
}
