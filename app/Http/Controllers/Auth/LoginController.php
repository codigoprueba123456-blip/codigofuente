<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function username()
    {
        return 'usuario';
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'usuario' => ['required_without:email', 'nullable', 'string'],
            'email' => ['required_without:usuario', 'nullable', 'email'],
            'contrasena' => ['required_without:password', 'nullable', 'string'],
            'password' => ['required_without:contrasena', 'nullable', 'string'],
        ]);
    }

    protected function credentials(Request $request)
    {
        $usuario = $request->input('usuario', $request->input('email', $request->input('username')));
        $password = $request->input('contrasena', $request->input('password'));

        return [
            'usuario' => $usuario,
            'password' => $password,
        ];
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
