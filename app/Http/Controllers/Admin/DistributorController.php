<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use Illuminate\Http\Request;

class DistributorController extends Controller
{
    use \App\Http\Controllers\Concerns\SortsRecords;

    public function index(Request $request)
    {
        $query = Distributor::withCount('purchaseOrders');

        if ($request->filled('q')) {
            $query->where(function ($w) use ($request) {
                $w->where('name', 'like', "%{$request->q}%")
                    ->orWhere('company', 'like', "%{$request->q}%")
                    ->orWhere('email', 'like', "%{$request->q}%");
            });
        }

        if ($request->filled('sort')) {
            $this->applySort($query, $request, ['name', 'email', 'purchase_orders_count', 'active'], 'name', 'asc');
        } else {
            $query->orderByDesc('is_default')->orderBy('name');
        }

        $distributors = $query->paginate(20)->withQueryString();

        return view('admin.distributors.index', compact('distributors'));
    }

    /** Delete several distributors at once. */
    public function bulkDestroy(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $n = Distributor::whereIn('id', $ids)->delete();

        return back()->with('status', $n.' distributor(s) deleted.');
    }

    public function create()
    {
        return view('admin.distributors.form', ['distributor' => new Distributor(['active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $distributor = Distributor::create($data);
        $this->syncDefault($distributor, $request);

        return redirect()->route('admin.distributors.index')->with('status', 'Distributor added.');
    }

    public function edit(Distributor $distributor)
    {
        return view('admin.distributors.form', compact('distributor'));
    }

    public function update(Request $request, Distributor $distributor)
    {
        $distributor->update($this->validated($request));
        $this->syncDefault($distributor, $request);

        return redirect()->route('admin.distributors.index')->with('status', 'Distributor updated.');
    }

    public function destroy(Distributor $distributor)
    {
        $distributor->delete();

        return back()->with('status', 'Distributor deleted.');
    }

    /** Make this the single default distributor (used for auto-assign + one-click approve). */
    public function makeDefault(Distributor $distributor)
    {
        Distributor::where('id', '!=', $distributor->id)->update(['is_default' => false]);
        $distributor->update(['is_default' => true, 'active' => true]);

        return back()->with('status', $distributor->name.' is now the default distributor.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]) + ['active' => $request->boolean('active')];
    }

    /** Ensure only one default distributor exists. */
    private function syncDefault(Distributor $distributor, Request $request): void
    {
        if ($request->boolean('is_default')) {
            Distributor::where('id', '!=', $distributor->id)->update(['is_default' => false]);
            $distributor->update(['is_default' => true]);
        } elseif ($distributor->is_default) {
            $distributor->update(['is_default' => false]);
        }
    }
}
