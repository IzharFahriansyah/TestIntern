<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
        'assigned_to',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    // Relationship: Task belongs to a project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relationship: Task is assigned to a user
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Relationship: Task has many comments
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Helper methods for status
    public function isNotStarted()
    {
        return $this->status === 'not_started';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    // Helper methods for priority
    public function isLowPriority()
    {
        return $this->priority === 'low';
    }

    public function isMediumPriority()
    {
        return $this->priority === 'medium';
    }

    public function isHighPriority()
    {
        return $this->priority === 'high';
    }

    // Check if task is overdue
    public function isOverdue()
    {
        return $this->due_date < now() && !$this->isCompleted();
    }

    // Get priority color for UI
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'low' => 'text-green-600 bg-green-100',
            'medium' => 'text-yellow-600 bg-yellow-100',
            'high' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    // Get status color for UI
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'not_started' => 'text-gray-600 bg-gray-100',
            'in_progress' => 'text-blue-600 bg-blue-100',
            'completed' => 'text-green-600 bg-green-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    // Get days until due date
    public function getDaysUntilDueAttribute()
    {
        if ($this->isCompleted()) {
            return null;
        }
        
        return now()->diffInDays($this->due_date, false);
    }

    // Scopes for filtering
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', 'completed');
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
                    ->where('status', '!=', 'completed');
    }
}
