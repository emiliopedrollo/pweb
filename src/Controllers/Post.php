<?php

namespace App\Controllers;

use App\Actions\ExpressionResolver;
use App\DB;
use App\Models\Post as PostModel;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use App\Request;
use App\Response;
use Exception;
use PDO;

class Post extends Controller {

    /**
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $postRepository = new PostRepository();

        $user = $request->getUser();
        $posts = $postRepository->getByUser($user);


//        $stmt = (app(DB::class))->connection->prepare("select * from usuario");

        ob_start();
        foreach ($posts as $post) {
            var_dump($post);
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

    /**
     * @throws Exception
     */
    public function show(PostModel $post) {

        $comments = app(CommentRepository::class)->getByPost($post);

        ob_start();

        var_dump($post);
        foreach ($comments as $comment) {
            var_dump($comment);
        }

        $content = ob_get_clean();
        return (new Response($content))
            ->addHeader('FUCK','this');
    }

}
