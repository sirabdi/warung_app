<?php

namespace App\Presentation\Http\Controllers\Payment;

use App\Application\Subscription\UseCase\ConfirmPayment;
use App\Application\Subscription\UseCase\ExpirePayment;
use App\Domain\Subscription\Exception\PaymentAmountMismatch;
use App\Domain\Subscription\Exception\PaymentNotFound;
use App\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Xendit's invoice callback. Only a request carrying our callback token is
 * believed; the redirect back to the app proves nothing.
 *
 * Unknown invoices (the dashboard's "test" button, a store removed in the
 * meantime) are acknowledged so Xendit stops retrying.
 *
 * @see https://developers.xendit.co/api-reference/#invoice-callback
 */
class XenditWebhookController extends Controller
{
    public function __invoke(Request $request, ConfirmPayment $confirm, ExpirePayment $expire): JsonResponse
    {
        $token = (string) config('services.xendit.callback_token');

        if ($token === '' || ! hash_equals($token, (string) $request->header('x-callback-token'))) {
            return response()->json(['message' => 'Invalid callback token.'], 401);
        }

        $externalId = (string) $request->input('external_id');

        try {
            match (strtoupper((string) $request->input('status'))) {
                'PAID', 'SETTLED' => $confirm->execute($externalId, (int) $request->input('amount')),
                'EXPIRED' => $expire->execute($externalId),
                default => null,
            };
        } catch (PaymentNotFound) {
            return response()->json(['status' => 'ignored']);
        } catch (PaymentAmountMismatch $e) {
            Log::error($e->getMessage(), ['payload' => $request->all()]);

            return response()->json(['status' => 'rejected']);
        }

        return response()->json(['status' => 'ok']);
    }
}
