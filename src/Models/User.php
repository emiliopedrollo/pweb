<?php

namespace App\Models;

use App\Relation;

/**
 * @property int $id
 * @property string $email
 * @property string $password
 * @property-read Post[] $posts
 */
class User extends Model
{
    /**
     * @return Relation<Post[]>
     */
    public function posts(): Relation {
        return $this->hasMany(Post::class);
    }
}
