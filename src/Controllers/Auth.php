<?php

namespace App\Controllers;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Request;
use App\Response;
use App\Session;
use Exception;

class Auth extends Controller{

    const HOME= '/posts';

    /**
     * @throws Exception
     */
    public function login(Request $request)
    {
        return view('auth.login');
    }

    public function authenticate(Request $request) {
        $email = $request->post('email');

        $user = (new UserRepository())->findByEmail($email);

        try {

            if (
                !$user or
                !password_verify($request->post('password'), $user->password)
            ) {
                throw new InvalidCredentialsException;
            }

            Session::add('user', $user->id);

        } catch (InvalidCredentialsException) {
            Session::flash('status', 'Credenciais inválidas');
            Session::flash('old.email', $email);
            return Response::redirect('/login');
        }

        return Response::redirect(self::HOME);
    }

    public function register(Request $request) {
        return view('auth.register');
    }

    /**
     * @throws Exception
     */
    public function signup(Request $request) {

        $email = $request->input('email');
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');
        $errors = false;

        if (User::query()->where('email','=',$email)->exists()) {
            $errors = true;
            Session::flash('errors.email', ['Email already registered']);
        }
        if ($password !== $password_confirmation) {
            $errors = true;
            Session::flash('errors.password', ['Password confirmation does not match']);
        }

        if ($errors) {
            Session::flash('old.email', $email);
            return Response::redirect('/register');
        }

        $user = User::create([
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);

        Session::add('user', $user->id);

        return Response::redirect(self::HOME);
    }

    public function logout() {
        Session::remove('user');
        return Response::redirect('/');
    }

}
