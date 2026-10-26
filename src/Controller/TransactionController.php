<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Mapper\PinakesMapper;
use App\Mapper\TransactionMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TransactionController extends PinakesController {

    #[Route('/bookfund', name: 'bookfund', methods: ['GET'])]
    public function bookfund(Request $request): Response {
        $mapper = PinakesMapper::getMapper(Transaction::class);
        $table = $mapper->createTable('list');
        $table->setOrder('timestamp', 'DESC');
        $table->setPP(6);
        $table->allow_ordering = false;
        $table->allow_pagination = false;
        return $this->render('bookfund.html.twig', [
            'transactions' => $table->currentPage(),
            'balance' => Transaction::getBalance()
        ]);
    }

    private function create_transaction(TransactionMapper $mapper, float $amount, string $reason) {
        // TODO validate transaction
        $transaction = new Transaction();
        $id = $mapper->save($transaction, array(
            'amount' => $amount,
            'reason' => $reason,
            'timestamp' => new \DateTime()
        ));
    }

    #[Route('/bookfund/deposit', name: 'bookfund_deposit', methods: ['POST'])]
    public function deposit(Request $request): Response {
        $amount = floatval($request->request->get('amount'));
        $reason = $request->request->get('reason');
        
        $mapper = PinakesMapper::getMapper(Transaction::class);
        $this->create_transaction($mapper, $amount, $reason);
        return $this->redirectHx('bookfund');
    }

    #[Route('/bookfund/withdrawal', name: 'bookfund_withdrawal', methods: ['POST'])]
    public function withdrawal(Request $request): Response {
        $amount = floatval($request->request->get('amount'));
        $reason = $request->request->get('reason');
        
        $mapper = PinakesMapper::getMapper(Transaction::class);
        $this->create_transaction($mapper, $amount * -1.0, $reason);
        return $this->redirectHx('bookfund');
    }

    #[Route('/transaction', name: 'transaction', methods: ['GET'])]
    public function list(Request $request): Response {
        $table = PinakesMapper::getMapper(Transaction::class)->createTable('list');
        return $this->renderList($request, 'Transactions', $table);
    }

    #[Route('/transaction/modal/{id}', name: 'transaction_modal', methods: ['GET', 'POST'])]
    public function modal(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $mapper = PinakesMapper::getMapper(Transaction::class);
        $transaction = $this->getEntity($request, $mapper) ?? new Transaction();
        return $this->renderModal($request, $mapper, $transaction, 'transaction');
    }

    #[Route('/transaction/delete/{id}', name: 'transaction_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $transaction = $this->getEntity($request, Transaction::class);
        PinakesMapper::getMapper(Transaction::class)->delete($transaction);
        return $this->redirectHx('transaction');
    }

    #[Route('/transaction/export', name: 'transaction_export', methods: ['GET'])]
    public function export(Request $request): Response {
        return new Response();
    }
}
