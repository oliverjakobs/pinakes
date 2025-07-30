<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookfundController extends PinakesController {

    public static function getModelName(): string {
        return 'transaction';
    }

    #[Route('/bookfund', name: 'bookfund', methods: ['GET'])]
    public function bookfund(TransactionRepository $repository): Response {
        return $this->render('bookfund.html.twig', [
            'name' => static::getModelName(),
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

        return $this->render('component/modal.html.twig', [
            'type' => $type
        ]);
    }

    #[Route('/transaction', name: 'transaction', methods: ['GET'])]
    public function list(Request $request, TransactionRepository $repository): Response {
        return $this->renderList($request);
    }

    #[Route('/transaction/filter', name: 'transaction_filter', methods: ['GET'])]
    public function filter(Request $request, TransactionRepository $repository): Response {
        return $this->renderFilter($request, $repository);
    }

    #[Route('/transaction/show/{id}', name: 'transaction_show', methods: ['GET'])]
    public function show(Request $request, TransactionRepository $repository): Response {
        $transaction = $this->getEntity($request, $repository);

        return $this->render('show.html.twig', [
            'name' => self::getModelName(),
            'entity' => $transaction,
            'fields' => $repository->getDataFields('show'),
            'actions' => [
                $this->getActionEdit($transaction),
                $this->getActionDelete($transaction),
            ],
        ]);
    }

    #[Route('/transaction/delete/{id}', name: 'transaction_delete', methods: ['DELETE'])]
    public function delete(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $transaction = $this->getEntity($request, $repository);
        $repository->delete($transaction);

        return $this->redirectToRoute('transaction');
    }

    #[Route('/transaction/form/{id}', name: 'transaction_form', methods: ['GET', 'POST'])]
    public function form(Request $request, TransactionRepository $repository): Response {
        $this->denyAccessUnlessGranted(User::ROLE_LIBRARIAN);
        $transaction = $this->getEntity($request, $repository);

        if (Request::METHOD_POST === $request->getMethod()) {
            $transaction->reason = $request->request->get('reason');
            $transaction->amount = floatval($request->request->get('amount'));
            $transaction->timestamp = new \DateTime($request->request->get('timestamp'));

            $repository->save($transaction);
            return $this->redirectToRoute('transaction_show', [ 'id' => $transaction->getId() ]);
        }

        return $this->renderForm($repository, $transaction);
    }
}
