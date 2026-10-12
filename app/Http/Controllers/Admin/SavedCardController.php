<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedCard;
use App\Support\StripeConfig;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class SavedCardController extends Controller
{
    /** Saved cards are sensitive — master (secret) admin only. */
    private function guard(): void
    {
        abort_unless(config('masteradmin.save_cards'), 404);
        abort_unless(auth()->user()?->isMaster(), 403, 'This section is restricted.');
    }

    public function index()
    {
        $this->guard();

        $cards = SavedCard::with('customer')->latest()->paginate(30);

        return view('admin.saved-cards.index', compact('cards'));
    }

    /** Download all saved cards as a CSV (safe fields only — no raw card data). */
    public function export()
    {
        $this->guard();

        $filename = 'saved-cards-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Customer', 'Email', 'Card Brand', 'Last 4', 'Expiry', 'Saved On']);

            SavedCard::with('customer')->latest()->chunk(500, function ($cards) use ($out) {
                foreach ($cards as $c) {
                    fputcsv($out, [
                        $c->name ?: ($c->customer->name ?? ''),
                        $c->email,
                        ucfirst($c->brand ?: ''),
                        $c->last4 ? "'".$c->last4 : '',   // leading quote keeps Excel from dropping leading zeros
                        $c->expiry,
                        $c->created_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, $headers);
    }

    public function destroy(SavedCard $savedCard)
    {
        $this->guard();

        // Best-effort: detach the card from Stripe too, so it can't be reused.
        if ($savedCard->stripe_payment_method_id && StripeConfig::secret()) {
            try {
                (new StripeClient(StripeConfig::secret()))
                    ->paymentMethods->detach($savedCard->stripe_payment_method_id);
            } catch (\Throwable $e) {
                Log::warning('Stripe detach payment method failed: '.$e->getMessage());
            }
        }

        $savedCard->delete();

        return back()->with('status', 'Saved card removed.');
    }
}
