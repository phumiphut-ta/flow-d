<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentMovement;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Document History & Ledger (สมุดคุม).
     */
    public function index(Request $request): View
    {
        $routes = Route::orderBy('route_name')->get();

        $query = DocumentMovement::with(['document', 'route', 'inUser', 'outUser']);

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->whereHas('document', function ($q) use ($search) {
                $q->where('doc_number', 'like', "%{$search}%");
            });
        }

        $routeId = $request->integer('route_id');
        if ($routeId > 0) {
            $query->where('route_id', $routeId);
        }

        if ($request->filled('status')) {
            if ($request->string('status') === 'in_progress') {
                $query->where('is_returned', false);
            } elseif ($request->string('status') === 'completed') {
                $query->where('is_returned', true);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('in_datetime', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('in_datetime', '<=', $request->date('date_to'));
        }

        $movements = $query->orderBy('in_datetime', 'desc')->paginate(30)->withQueryString();

        return view('history.index', [
            'movements' => $movements,
            'routes' => $routes,
        ]);
    }

    /**
     * Show document details with all rounds and step logs.
     */
    public function show(Document $document): View
    {
        $document->load([
            'creator',
            'movements' => function ($q) {
                $q->with(['route', 'inUser', 'outUser', 'inDevice', 'outDevice', 'stepLogs.actionUser'])
                    ->orderBy('round_number', 'desc');
            },
        ]);

        return view('history.show', [
            'document' => $document,
        ]);
    }
}
