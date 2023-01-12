<?php

namespace App\Controllers;

use App\Actions\ExpressionResolver;
use App\DB;
use App\Exceptions\InvalidCredentialsException;
use App\Repositories\UserRepository;
use App\Request;
use App\Response;
use App\Session;
use Exception;
use PDO;

class Auth extends Controller{

    /**
     * @throws Exception
     */
    public function login()
    {
        return view('login',[
            'error' => Session::get('error') ?? null
        ]);
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
            Session::flash('error', 'Credenciais inválidas');
            return Response::redirect('/login');
        }

        return Response::redirect('/');
    }

    public function register(Request $request) {
        return 'stuff';
    }

    public function logout() {
        Session::remove('user');
        return Response::redirect('/');
    }

}
