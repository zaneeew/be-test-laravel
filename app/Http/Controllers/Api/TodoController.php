<?php

namespace App\Http\Controllers\Api;

use App\Exports\TodoExport;
use App\Http\Controllers\Controller;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class TodoController extends Controller
{
    public function store(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'title' => 'required|string',
            'assignee' => 'nullable|string',
            'due_date' => 'required|date|after_or_equal:today',
            'time_tracked' => 'nullable|numeric',
            'status' => 'nullable|in:pending,open,in_progress,completed',
            'priority' => 'required|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['status'] = $data['status'] ?? 'pending';
        $data['time_tracked'] = $data['time_tracked'] ?? 0;

        $todo = Todo::create($data);
        return response()->json($todo, 201);
    }

    public function export(Request $req)
    {
        $query = Todo::query();

        // Filtering
        if ($req->filled('title')) {
            $query->where('title', 'like', '%' . $req->title . '%');
        }

        if ($req->filled('assignee')) {
            $assignees = explode(',', $req->assignee);
            $query->whereIn('assignee', $assignees);
        }

        if ($req->filled('start') && $req->filled('end')) {
            $query->whereBetween('due_date', [$req->start, $req->end]);
        }

        if ($req->filled('min') && $req->filled('max')) {
            $query->whereBetween('time_tracked', [$req->min, $req->max]);
        }

        if ($req->filled('status')) {
            $statuses = explode(',', $req->status);
            $query->whereIn('status', $statuses);
        }

        if ($req->filled('priority')) {
            $priorities = explode(',', $req->priority);
            $query->whereIn('priority', $priorities);
        }

        $todos = $query->get();

        $total = [
            'title' => 'Total',
            'assignee' => '',
            'due_date' => '',
            'time_tracked' => $todos->sum('time_tracked'),
            'status' => '',
            'priority' => count($todos)
        ];

        $collection = $todos->map(function ($item) {
            return [
                'title' => $item->title,
                'assignee' => $item->assignee,
                'due_date' => $item->due_date,
                'time_tracked' => $item->time_tracked,
                'status' => $item->status,
                'priority' => $item->priority
            ];
        })->push($total);

        return Excel::download(new TodoExport($collection), 'todo_report.xlsx');
    }

    public function chart(Request $req)
    {
        switch ($req->query('type')) {
            case 'status':
                $defaultStatuses = [
                    'pending' => 0,
                    'open' => 0,
                    'in_progress' => 0,
                    'completed' => 0
                ];

                $statusCounts = Todo::selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->toArray();

                return response()->json([
                    'status_summary' => array_merge($defaultStatuses, $statusCounts)
                ]);
            case 'priority':
                $defaultPriorities = [
                    'low' => 0,
                    'medium' => 0,
                    'high' => 0
                ];

                $priorityCounts = Todo::selectRaw('priority, count(*) as total')
                    ->groupBy('priority')
                    ->pluck('total', 'priority')
                    ->toArray();

                return response()->json([
                    'priority_summary' => array_merge($defaultPriorities, $priorityCounts)
                ]);
            case 'assignee':
                $result = [];
                $assignees = Todo::select('assignee')->distinct()->pluck('assignee');
                foreach ($assignees as $assignee) {
                    $todos = Todo::where('assignee', $assignee);
                    $result[$assignee] = [
                        'total_todos' => $todos->count(),
                        'total_pending_todos' => $todos->where('status', 'pending')->count(),
                        'total_timetracked_completed_todos' => $todos->where('status', 'completed')->sum('time_tracked'),
                    ];
                }
                return response()->json(['assignee_summary' => $result]);
            default:
                return response()->json(['error' => 'Unknown chart type'], 400);
        }
    }
}
