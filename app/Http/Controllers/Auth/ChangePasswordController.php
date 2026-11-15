<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.passwords.change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $passwordHash = Hash::make($validated['password']);

        DB::transaction(function () use ($user, $passwordHash): void {
            $user->update(['password' => $passwordHash]);
            $user->cuentas()->update(['password' => $passwordHash]);
        });

        return redirect()
            ->route('account.password.edit')
            ->with('success', 'La contraseña se actualizó correctamente.');
    }
}
