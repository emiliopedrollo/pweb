<?php

namespace App\Repositories;

use App\Models\User;
use Exception;

/**
 * @template T of User
 * @template-extends Repository<T>
 */
class UserRepository extends Repository
{

    protected string $table = 'usuario';
    protected string $model = User::class;
    public static array $columnMap = [
        'codigoUsuario' => 'id',
        'email' => 'email',
        'senha' => 'password',
    ];

    /**
     * @param string $email
     * @return User|null
     * @throws Exception
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findByColumn('email', $email);
    }
}
