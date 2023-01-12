<?php

namespace App\Controllers;

use App\Actions\ExpressionResolver;
use App\DB;
use App\Repositories\UserRepository;
use App\Request;
use App\Response;
use PDO;

class Index extends Controller{

    public function index(Request $request)
    {
        $number = $request->get('number');

        if ($number) {
            $root = ExpressionResolver::resolve("$number^(1/2)");
        }

        return view('root',[
            'number' => $number,
            'root' => $root ?? null,
            'blah' => $request->isAuthenticated() ? 'authenticated' : 'guest',
        ]);

    }

    public function db() {

        $usersRepository = new UserRepository();
        $users = $usersRepository->get();


//        $stmt = (app(DB::class))->connection->prepare("select * from usuario");

        ob_start();
        foreach ($users as $user) {
            var_dump($user);
        }

//        if ($stmt->execute()) {
//            while ($row = $stmt->fetch(PDO::FETCH_LAZY + PDO::FETCH_ASSOC)) {
//                var_dump($row);
//            }
//        }

        $content = ob_get_clean();

        return (new Response($content))
            ->addHeader('FUCK','this');
    }

}
