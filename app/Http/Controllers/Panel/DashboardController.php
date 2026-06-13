<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DocumentPrediction;
use App\Models\DocumentType;
use App\Models\FinancialYear;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $company   = Company::getDefault();
        $currentFY = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        $fyProgress = null;
        if ($currentFY) {
            $total   = max($currentFY->start_date->diffInDays($currentFY->end_date), 1);
            $elapsed = $currentFY->start_date->diffInDays(now()->min($currentFY->end_date));
            $fyProgress = round(($elapsed / $total) * 100);
        }

        $totalUsers       = User::where('is_active', true)->count();
        $totalPredictions = DocumentPrediction::count();
        $todayPredictions = DocumentPrediction::whereDate('created_at', today())->count();
        $totalDocTypes    = DocumentType::where('is_active', true)->count();

        $predictionsByStatus = DocumentPrediction::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentPredictions = DocumentPrediction::with('predictedType')
            ->latest()
            ->limit(8)
            ->get();

        $monthlyTrend = collect(range(5, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            return [
                'month' => $date->format('M'),
                'count' => DocumentPrediction::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        });

        $avgConfidence = DocumentPrediction::whereNotNull('confidence_score')
            ->avg('confidence_score');

        $topDocTypes = DocumentPrediction::whereNotNull('predicted_type_id')
            ->join('document_types', 'document_predictions.predicted_type_id', '=', 'document_types.id')
            ->selectRaw('document_types.label, COUNT(*) as count')
            ->groupBy('document_types.label')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'company', 'currentFY', 'fyProgress',
            'totalUsers', 'totalPredictions', 'todayPredictions', 'totalDocTypes',
            'predictionsByStatus', 'recentPredictions', 'monthlyTrend',
            'avgConfidence', 'topDocTypes'
        ));
    }
}
