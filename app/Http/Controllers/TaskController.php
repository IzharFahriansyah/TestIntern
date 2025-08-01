<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks
     */
    public function index(Request $request)
    {
        $query = Task::with(['project', 'assignedUser', 'comments.user']);

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by project
        if ($request->has('project_id') && $request->project_id != '') {
            $query->where('project_id', $request->project_id);
        }

        // Filter by assigned user
        if ($request->has('assigned_to') && $request->assigned_to != '') {
            $query->where('assigned_to', $request->assigned_to);
        }

        // For members, only show tasks assigned to them or in their projects
        $user = $request->user();
        if ($user && $user->role === 'member') {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereHas('project.users', function($projectQuery) use ($user) {
                      $projectQuery->where('user_id', $user->id);
                  });
            });
        }

        $tasks = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created task
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date|after:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user has access to this project
        $project = Project::find($request->project_id);
        $user = $request->user();
        
        if ($user->role === 'member' && !$project->users->contains($user->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. You are not a member of this project.'
            ], 403);
        }

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'project_id' => $request->project_id,
            'assigned_to' => $request->assigned_to,
            'status' => $request->status,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'created_by' => $user->id
        ]);

        $task->load(['project', 'assignedUser', 'comments']);

        return response()->json([
            'status' => 'success',
            'message' => 'Task created successfully',
            'data' => $task
        ], 201);
    }

    /**
     * Display the specified task
     */
    public function show(Request $request, $id)
    {
        $task = Task::with(['project', 'assignedUser', 'comments.user', 'createdBy'])->find($id);

        if (!$task) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        // Check if member has access to this task
        $user = $request->user();
        if ($user && $user->role === 'member') {
            $hasAccess = $task->assigned_to === $user->id || 
                        $task->project->users->contains($user->id);
            
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied'
                ], 403);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $task
        ]);
    }

    /**
     * Update the specified task
     */
    public function update(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        // Check if member has access to this task
        $user = $request->user();
        if ($user && $user->role === 'member') {
            $hasAccess = $task->assigned_to === $user->id || 
                        $task->project->users->contains($user->id);
            
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied'
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'sometimes|required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'sometimes|required|in:pending,in_progress,completed,cancelled',
            'priority' => 'sometimes|required|in:low,medium,high',
            'due_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $task->update($request->only([
            'title', 'description', 'project_id', 'assigned_to', 
            'status', 'priority', 'due_date'
        ]));

        $task->load(['project', 'assignedUser', 'comments']);

        return response()->json([
            'status' => 'success',
            'message' => 'Task updated successfully',
            'data' => $task
        ]);
    }

    /**
     * Remove the specified task
     */
    public function destroy(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        // Only admin or task creator can delete tasks
        $user = $request->user();
        if ($user->role !== 'admin' && $task->created_by !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only admin or task creator can delete tasks.'
            ], 403);
        }

        $task->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Task deleted successfully'
        ]);
    }

    /**
     * Get tasks for a specific project
     */
    public function getProjectTasks(Request $request, $projectId)
    {
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found'
            ], 404);
        }

        // Check if member has access to this project
        $user = $request->user();
        if ($user && $user->role === 'member' && !$project->users->contains($user->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied'
            ], 403);
        }

        $tasks = Task::with(['assignedUser', 'comments.user'])
                    ->where('project_id', $projectId)
                    ->latest()
                    ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $tasks
        ]);
    }

    /**
     * Assign task to user
     */
    public function assignTask(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if assigned user is member of the project
        $assignedUser = User::find($request->assigned_to);
        if (!$task->project->users->contains($assignedUser->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is not a member of this project'
            ], 422);
        }

        $task->update(['assigned_to' => $request->assigned_to]);
        $task->load(['project', 'assignedUser']);

        return response()->json([
            'status' => 'success',
            'message' => 'Task assigned successfully',
            'data' => $task
        ]);
    }

    /**
     * Get tasks assigned to current user
     */
    public function getMyTasks(Request $request)
    {
        $user = $request->user();
        
        $tasks = Task::with(['project', 'comments.user'])
                    ->where('assigned_to', $user->id)
                    ->latest()
                    ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $tasks
        ]);
    }
}
