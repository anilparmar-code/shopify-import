<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ErrorLogController extends Controller
{
    /**
     * Display a listing of the error logs.
     */
    public function index(Request $request): Response
    {
        $logs = ErrorLog::query()
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($log) => [
                'id' => $log->id,
                'level' => $log->level,
                'event' => $log->event,
                'message' => $log->message,
                'context' => $log->context,
                'loggable_type' => $log->loggable_type ? class_basename($log->loggable_type) : null,
                'loggable_id' => $log->loggable_id,
                'created_at' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : null,
            ]);

        return Inertia::render('error-logs/Index', [
            'logs' => $logs,
        ]);
    }
}
