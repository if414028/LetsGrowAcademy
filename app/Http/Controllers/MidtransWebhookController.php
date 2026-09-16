<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\MidtransPaymentUpdater;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransWebhookController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly MidtransPaymentUpdater $paymentUpdater,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required', 'string'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required'],
            'signature_key' => ['required', 'string'],
        ]);

        $expectedSignature = hash('sha512',
            $request->string('order_id')->toString()
            .$request->string('status_code')->toString()
            .$request->input('gross_amount')
            .config('midtrans.server_key')
        );

        abort_unless(hash_equals($expectedSignature, $request->string('signature_key')->toString()), 403);

        // Midtrans Dashboard sends a signed notification with a synthetic order
        // that intentionally does not exist in the Transaction Status API.
        if (str_starts_with($request->string('order_id')->toString(), 'payment_notif_test_')) {
            return response()->json(['message' => 'Midtrans notification endpoint is ready.']);
        }

        try {
            // Use Midtrans's status endpoint as the source of truth, not browser callbacks.
            $status = $this->midtrans->transactionStatus($request->string('order_id')->toString());
        } catch (Throwable $exception) {
            Log::warning('Unable to verify Midtrans transaction status.', [
                'order_id' => $request->input('order_id'),
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Status verification failed.'], 503);
        }

        $orderId = (string) ($status->order_id ?? '');
        $grossAmount = (int) round((float) ($status->gross_amount ?? 0));

        $subscription = Subscription::where('midtrans_order_id', $orderId)->firstOrFail();
        abort_unless($grossAmount === (int) $subscription->amount, 422, 'Payment amount does not match.');

        $this->paymentUpdater->update($subscription, $status);

        return response()->json(['message' => 'Notification processed.']);
    }
}
