<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationship: Project has many users (many-to-many)
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    // Relationship: Project has many tasks
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // Get only members (not admin) assigned to this project
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')
                    ->where('role', 'member');
    }

    // Helper methods
    public function isPlanned()
    {
        return $this->status === 'planned';
    }

    public function isOngoing()
    {
        return $this->status === 'ongoing';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    // Get project progress based on completed tasks
    public function getProgressAttribute()
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }
        
        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    // Get overdue tasks count
    public function getOverdueTasksCountAttribute()
    {
        return $this->tasks()
                    ->where('due_date', '<', now())
                    ->where('status', '!=', 'completed')
                    ->count();
    }

    // Scope for filtering
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('users', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}
