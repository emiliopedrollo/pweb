<?php

namespace App\Controllers;

use App\Models\Comment as CommentModel;
use App\Models\Post as PostModel;
use App\Request;
use App\Response;
use App\Session;
use Exception;

class Comment extends Controller {


    public function store(Request $request, PostModel $post): Response
    {
        CommentModel::create([
            'content' => $request->post('content'),
            'post_id' => $post->id,
        ]);
        Session::flash('status','Comentário criado com sucesso');
        return Response::redirect(route('post.show',['post' => $post->id]));
    }

    /**
     * @throws Exception
     */
    public function edit(PostModel $post, CommentModel $comment): Response
    {
        return view('posts.show',[
            'post' => $post,
            'editing_comment' => $comment
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, PostModel $post, CommentModel $comment): Response
    {
        $comment->update([
            'content' => $request->post('content'),
        ]);
        Session::flash('status','Comentário alterado com sucesso');
        return Response::redirect(route('post.show',['post' => $post->id]));
    }

    public function destroy(PostModel $post, CommentModel $comment): Response
    {
        $comment->delete();
        Session::flash('status','Comentário deletado com sucesso');
        return Response::redirect(route('post.show',['post' => $post->id]));
    }

}
