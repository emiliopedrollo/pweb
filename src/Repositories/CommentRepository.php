<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Post;
use Exception;

/**
 * @template T of Comment
 * @template-extends Repository<T>
 */
class CommentRepository extends Repository
{

    protected string $table = 'comentario';
    protected string $model = Comment::class;
    public static array $columnMap = [
        'codigo' => 'id',
        'conteudo' => 'content',
        'codigoPublicacao' => 'post_id',
    ];

    /**
     * @param Post|int $post
     * @return Comment[]
     * @throws Exception
     */
    public function getByPost(Post|int $post): array
    {
        $post_id = $post instanceof Post ? $post->id : $post;
        return $this->getByColumn('post_id', $post_id);
    }
}
