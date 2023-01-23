<?php

namespace App\Models;

use App\Relation;
use DateTime;

/**
 * @property int $id
 * @property string $content
 * @property DateTime $schedule
 * @property-read User $user
 * @property-read Comment[] $comments
 */
class Post extends Model
{

    /**
     * @return Relation<User>
     */
    public function user(): Relation {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Relation<Comment>
     */
    public function comments(): Relation {
        return $this->hasMany(Comment::class);
    }

}
