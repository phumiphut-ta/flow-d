<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    /**
     * Mobile companion tracking view (LINE LIFF).
     */
    public function index(Request $request): View|JsonResponse
    {
        $docNumber = trim((string) $request->input('doc_number', ''));
        $document = null;
        $selectedRound = null;

        if ($docNumber !== '') {
            $document = Document::with([
                'movements' => function ($q) {
                    $q->with(['route', 'inUser', 'outUser', 'stepLogs.actionUser'])
                        ->orderBy('round_number', 'asc');
                },
            ])->where('doc_number', $docNumber)->first();

            if ($document && $document->movements->isNotEmpty()) {
                $requestedRound = $request->integer('round');
                if ($requestedRound > 0) {
                    $selectedRound = $document->movements->firstWhere('round_number', $requestedRound);
                }

                if (! $selectedRound) {
                    // Default to latest round
                    $selectedRound = $document->movements->last();
                }
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            if (! $document) {
                return response()->json([
                    'found' => false,
                    'message' => 'ไม่พบข้อมูลหนังสือเลขรับนี้ในระบบ',
                ], 404);
            }

            return response()->json([
                'found' => true,
                'document' => [
                    'doc_number' => $document->doc_number,
                    'doc_date' => $document->doc_date_readable,
                    'status' => $document->current_status,
                ],
                'rounds' => $document->movements->map(function ($m) {
                    return [
                        'round_number' => $m->round_number,
                        'route_name' => $m->route->route_name,
                        'is_returned' => $m->is_returned,
                        'in_datetime' => $m->in_datetime_thai,
                        'in_by' => $m->inUser?->name ?? 'ไม่ระบุ',
                        'out_datetime' => $m->out_datetime_thai,
                        'out_by' => $m->outUser?->name,
                        'duration' => $m->waiting_duration,
                        'steps' => $m->stepLogs->map(fn ($s) => [
                            'name' => $s->step_name,
                            'status' => $s->status,
                            'action_by' => $s->actionUser?->name ?? 'ระบบ',
                            'datetime' => $s->action_datetime_thai,
                        ]),
                    ];
                }),
            ]);
        }

        return view('liff.tracking', [
            'docNumber' => $docNumber,
            'document' => $document,
            'selectedRound' => $selectedRound,
        ]);
    }
}
