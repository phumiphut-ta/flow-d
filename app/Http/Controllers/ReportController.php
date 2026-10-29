<?php

namespace App\Http\Controllers;

use App\Models\DocumentMovement;
use App\Models\Route;
use App\Support\ThaiDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the periodic summary report with PDF A4 print view.
     */
    public function index(Request $request): View
    {
        $period = $request->input('period', 'today');
        $routeId = $request->filled('route_id') && $request->integer('route_id') > 0
            ? $request->integer('route_id')
            : null;

        $now = Carbon::now();

        switch ($period) {
            case 'this_week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $periodLabel = 'สัปดาห์นี้ ('.ThaiDate::format($startDate).' - '.ThaiDate::format($endDate).')';
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $periodLabel = 'เดือนนี้ ('.ThaiDate::formatThaiReadable($startDate).' - '.ThaiDate::formatThaiReadable($endDate).')';
                break;
            case 'this_year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $periodLabel = 'ปีนี้ (พ.ศ. '.($now->year + 543).')';
                break;
            case 'custom':
                $dateFrom = $request->input('date_from');
                $dateTo = $request->input('date_to');

                $startDate = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : $now->copy()->startOfDay();
                $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : $now->copy()->endOfDay();
                $periodLabel = 'ช่วงวันที่ '.ThaiDate::format($startDate).' ถึง '.ThaiDate::format($endDate);
                break;
            case 'today':
            default:
                $period = 'today';
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'วันนี้ ('.ThaiDate::formatThaiReadable($now).')';
                break;
        }

        // Base movements query for intake in this period
        $intakeQuery = DocumentMovement::query()
            ->whereBetween('in_datetime', [$startDate, $endDate]);

        // Base movements query for completed in this period
        $completedQuery = DocumentMovement::query()
            ->where('is_returned', true)
            ->whereBetween('out_datetime', [$startDate, $endDate]);

        if ($routeId) {
            $intakeQuery->where('route_id', $routeId);
            $completedQuery->where('route_id', $routeId);
        }

        $totalIntake = (clone $intakeQuery)->count();
        $totalCompleted = (clone $completedQuery)->count();

        // Currently pending among intake in period
        $totalPending = (clone $intakeQuery)->where('is_returned', false)->count();

        // Overall active in system right now
        $totalActiveSystem = DocumentMovement::where('is_returned', false)->count();

        // Breakdown by route
        $allRoutes = Route::where('is_active', true)->orderBy('id')->get();
        $routeBreakdown = $allRoutes->map(function ($rt) use ($startDate, $endDate) {
            $inCount = DocumentMovement::where('route_id', $rt->id)
                ->whereBetween('in_datetime', [$startDate, $endDate])
                ->count();

            $outCount = DocumentMovement::where('route_id', $rt->id)
                ->where('is_returned', true)
                ->whereBetween('out_datetime', [$startDate, $endDate])
                ->count();

            $pendingCount = DocumentMovement::where('route_id', $rt->id)
                ->whereBetween('in_datetime', [$startDate, $endDate])
                ->where('is_returned', false)
                ->count();

            return [
                'id' => $rt->id,
                'name' => $rt->route_name,
                'is_pipeline' => $rt->isPipeline(),
                'steps_count' => count($rt->steps_json ?? []),
                'in_count' => $inCount,
                'out_count' => $outCount,
                'pending_count' => $pendingCount,
                'completion_rate' => $inCount > 0 ? round(($outCount / $inCount) * 100) : 0,
            ];
        });

        // Detail list of movements during this period (up to 200 for clean print view)
        $movements = (clone $intakeQuery)
            ->with(['document', 'route', 'inUser', 'outUser'])
            ->orderBy('in_datetime', 'asc')
            ->limit(300)
            ->get();

        return view('reports.index', [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedRouteId' => $routeId,
            'routes' => $allRoutes,
            'totalIntake' => $totalIntake,
            'totalCompleted' => $totalCompleted,
            'totalPending' => $totalPending,
            'totalActiveSystem' => $totalActiveSystem,
            'completionRate' => $totalIntake > 0 ? round(($totalCompleted / $totalIntake) * 100) : ($totalCompleted > 0 ? 100 : 0),
            'routeBreakdown' => $routeBreakdown,
            'movements' => $movements,
        ]);
    }
}
