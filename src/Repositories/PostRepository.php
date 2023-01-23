<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\User;
use Exception;

/**
 * @template T of Post
 * @template-extends Repository<T>
 */
class PostRepository extends Repository
{

    protected string $table = 'publicacao';
    protected string $model = Post::class;
    public static array $columnMap = [
        'codigo' => 'id',
        'conteudo' => 'content',
        'agendamento' => 'schedule',
        'codigoUsuario' => 'user_id',
    ];

    public array $casts = [
        'schedule' => 'datetime'
    ];

    /**
     * @param User|int $user
     * @return Post[]
     * @throws Exception
     */
    public function getByUser(User|int $user): array
    {
        $user_id = $user instanceof User ? $user->id : $user;
        return $this->getByColumn('user_id', $user_id);
    }

    /**
     * @param User|int $user
     * @return Post[]
     * @throws Exception
     */
    public function getByUserOrId(User|int $user): array
    {
        $user_id = $user instanceof User ? $user->id : $user;
        return $this->getByColumn('user_id', $user_id);
    }
}
