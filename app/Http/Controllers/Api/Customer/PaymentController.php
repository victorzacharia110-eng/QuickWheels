<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\ClickPesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected ClickPesaService $clickPesa;

    public function __construct(ClickPesaService $clickPesa)
    {
        $this->clickPesa = $clickPesa;
    }

    public function index(Request $request)
    {
        $payments = Payment::whereHas('booking', fn($q) => $q->where('customer_id', $request->user()->id))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $payments]);
    }

    public function show(Request $request, $id)
    {
        $payment = Payment::where('id', $id)
            ->whereHas('booking', fn($q) => $q->where('customer_id', $request->user()->id))
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $payment]);
    }

    public function accounts()
    {
        $accounts = config('payment.accounts', [
            [
                'provider' => 'Airtel Money',
                'number' => '0678 XXX XXX',
                'instructions' => 'Send via Airtel Money to the number above',
            ],
            [
                'provider' => 'Mixx By Yas',
                'number' => '0678 XXX XXX',
                'instructions' => 'Send via Mixx to the number above',
            ],
            [
                'provider' => 'M-Pesa',
                'number' => '0714 XXX XXX',
                'instructions' => 'Send via M-Pesa to the number above',
            ],
            [
                'provider' => 'Halopesa',
                'number' => '0622 XXX XXX',
                'instructions' => 'Send via Halopesa to the number above',
            ],
            [
                'provider' => 'NMB',
                'number' => 'XXXXXXX',
                'instructions' => 'Bank transfer to NMB account',
            ],
            [
                'provider' => 'CRDB',
                'number' => 'XXXXXXX',
                'instructions' => 'Bank transfer to CRDB account',
            ],
            [
                'provider' => 'NBC',
                'number' => 'XXXXXXX',
                'instructions' => 'Bank transfer to NBC account',
            ],
        ]);

        return response()->json(['success' => true, 'data' => $accounts]);
    }

    public function clickPesaInit(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'phone' => 'required|string',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('customer_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $reference = 'QW-' . strtoupper(uniqid());
        $amount = (float) $booking->total_amount;

        $result = $this->clickPesa->initiatePayment(
            $amount,
            $request->phone,
            $reference,
            'QuickWheels Booking #' . $booking->id
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Payment initiation failed'], 500);
        }

        Payment::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'method' => 'clickpesa',
            'status' => 'pending',
            'transaction_id' => $result['data']['transaction_id'] ?? null,
            'reference_number' => $reference,
            'payment_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated',
            'data' => $result['data'],
        ]);
    }

    public function confirmManual(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'amount' => 'required|numeric|min:0',
            'provider' => 'required|string',
            'reference_number' => 'required|string',
            'transaction_id' => 'nullable|string',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('customer_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $request->amount,
            'method' => 'manual',
            'payment_method' => $request->provider,
            'status' => 'pending',
            'reference_number' => $request->reference_number,
            'transaction_id' => $request->transaction_id,
            'payment_date' => now(),
            'notes' => 'Manual payment submitted by customer',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded. Awaiting approval.',
            'data' => $payment,
        ], 201);
    }

    public function webhook(Request $request)
    {
        Log::info('ClickPesa webhook received', $request->all());

        $result = $this->clickPesa->handleWebhook($request->all());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $payment = Payment::where('reference_number', $result['reference'])->first();

        if (!$payment) {
            Log::warning('ClickPesa webhook: payment not found', ['reference' => $result['reference']]);
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        DB::transaction(function () use ($payment, $result) {
            $payment->update([
                'status' => $result['status'] === 'success' ? 'approved' : 'failed',
                'transaction_id' => $result['transaction_id'],
                'paid_at' => now(),
            ]);

            if ($result['status'] === 'success' && $payment->booking) {
                $booking = $payment->booking;
                $totalPaid = Payment::where('booking_id', $booking->id)
                    ->where('status', 'approved')
                    ->sum('amount');

                $remaining = max(0, $booking->total_amount - $totalPaid);

                $booking->update([
                    'deposit_paid' => $totalPaid,
                    'balance' => $remaining,
                    'payment_status' => $totalPaid >= $booking->total_amount ? 'paid' : 'partial',
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Webhook processed']);
    }
}
