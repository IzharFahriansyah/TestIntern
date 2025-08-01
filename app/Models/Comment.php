<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];

    // Relationship: Comment belongs to a task
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Relationship: Comment belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get formatted created date
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d M Y, H:i');
    }

    // Get time ago format
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // Check if comment belongs to current user
    public function belongsToCurrentUser()
    {
        return $this->user_id === auth()->id();
    }

    // Scope for getting recent comments
    public function scopeRecent($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    // Scope for comments by user
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
