<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessProfileController extends Controller
{
    public function create(): View
    {
        $business = DB::table('businesses')->where('owner_id', auth()->id())->latest()->first();

        return view('businesses.create', compact('business'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'sector' => ['required', 'string', 'max:100'],
            'capital' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $existing = DB::table('businesses')->where('owner_id', auth()->id())->latest()->first();
        $data = [
            ...$validated,
            'owner_id' => auth()->id(),
            'status' => 'active',
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('businesses')->where('id', $existing->id)->update($data);
        } else {
            DB::table('businesses')->insert([...$data, 'created_at' => now()]);
        }

        return redirect()->route('funding.create')->with('status', 'Business profile saved. You can now complete your funding application.');
    }
}
