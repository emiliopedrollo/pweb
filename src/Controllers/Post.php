<?php

namespace App\Controllers;

use App\Models\Post as PostModel;
use App\Request;
use App\Response;
use App\Session;
use Exception;

class Post extends Controller {

    /**
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $user = $request->getUser();
        $posts = $user->posts()
            ->with('comments')
            ->orderBy('schedule')
            ->get();

        return view('posts.index',[
            'posts' => $posts
        ]);
    }

    /**
     * @throws Exception
     */
    public function show(PostModel $post): Response
    {
        return view('posts.show',[
            'post' => $post
        ]);
    }

    /**
     * @throws Exception
     */
    public function create(): Response
    {
        return view('posts.edit');
    }
    public function store(Request $request): Response
    {

        $schedule = \DateTime::createFromFormat('d/m/Y H:i:s',$request->post('schedule'));

        if ($schedule === false) {
            Session::flash('error.schedule', 'Formato de data inválida');
            Session::flash('old.schedule', $request->post('schedule'));
            Session::flash('old.content', $request->post('content'));
            return Response::back();
        }

        PostModel::create([
            'schedule' => $schedule,
            'content' => $request->post('content'),
            'user_id' => $request->getUser()->id,
        ]);
        Session::flash('status','Publicação criada com sucesso');
        return Response::redirect(route('posts'));
    }

    /**
     * @throws Exception
     */
    public function edit(PostModel $post): Response
    {
        return view('posts.edit',[
            'post' => $post
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, PostModel $post): Response
    {
        $post->update([
            'schedule' => $request->post('schedule'),
            'content' => $request->post('content'),
        ]);
        Session::flash('status','Publicação alterada com sucesso');
        return Response::redirect(route('posts'));
    }

    public function destroy(PostModel $post): Response
    {
        $post->delete();
        Session::flash('status','Publicação deletada com sucesso');
        return Response::redirect(route('posts'));
    }

}
