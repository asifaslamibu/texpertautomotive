<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::orderByDesc('active')->orderBy('code')->paginate(30),
        ]);
    }

    public function create()
    {
        return view('admin.coupons.form', [
            'coupon' => new Coupon(['type' => 'fixed', 'active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        Coupon::create($this->validated($request));

        return redirect()->route('admin.coupons.index')->with('status', 'Discount code created.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validated($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('status', 'Discount code updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return back()->with('status', 'Discount code deleted.');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['active' => ! $coupon->active]);

        return back()->with('status', 'Discount code '.($coupon->active ? 'switched on.' : 'switched off.'));
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type' => ['required', 'in:fixed,percent'],
            'value' => ['required', 'numeric', 'min:0.01', $request->input('type') === 'percent' ? 'max:100' : 'max:100000'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'note' => ['nullable', 'string', 'max:190'],
        ], [
            'code.regex' => 'Use letters, numbers, dashes or underscores only — no spaces.',
            'code.unique' => 'There is already a code with that name.',
            'value.max' => 'A percentage cannot be more than 100.',
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['min_subtotal'] = $data['min_subtotal'] ?? 0;
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
