<?php

namespace App\Http\Controllers;

use App\Models\AssessmentResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $totalAssessments = AssessmentResult::query()->count();

        $uniqueStudents = (int) AssessmentResult::query()
            ->distinct('user_id')
            ->count('user_id');

        $programResults = AssessmentResult::query()
            ->whereNotNull('program_code')
            ->whereNotIn('program_code', ['N/A', 'PENDING']);
        $programResultCount = (clone $programResults)->count();
        $legacyConfidence = AssessmentResult::query()
            ->whereNull('instrument_version')
            ->avg('confidence');
        $avgConfidence = $legacyConfidence === null
            ? null
            : round((float) $legacyConfidence, 1);

        $sourceCounts = AssessmentResult::query()
            ->select('source', DB::raw('count(*) as total'))
            ->groupBy('source')
            ->pluck('total', 'source');

        $aiGeneratedCount = (int) ($sourceCounts['openai'] ?? 0)
            + (int) ($sourceCounts['ollama'] ?? 0);
        $fallbackCount = (int) ($sourceCounts['fallback'] ?? 0);

        $aiAvailabilityRate = $totalAssessments > 0
            ? round(($aiGeneratedCount / $totalAssessments) * 100, 1)
            : 0.0;

        $flaggedCount = AssessmentResult::query()
            ->whereNotNull('response_flags')
            ->count();

        $programBreakdown = (clone $programResults)
            ->selectRaw('program_code, program_name, count(*) as total')
            ->groupBy('program_code', 'program_name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($programResultCount) {
                $row->percentage = $programResultCount > 0
                    ? round(($row->total / $programResultCount) * 100, 1)
                    : 0.0;

                return $row;
            });

        $recentResults = AssessmentResult::query()
            ->with('user:id,name,email')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('admin.analytics', compact(
            'totalAssessments',
            'uniqueStudents',
            'avgConfidence',
            'aiGeneratedCount',
            'fallbackCount',
            'aiAvailabilityRate',
            'flaggedCount',
            'programBreakdown',
            'recentResults'
        ));
    }

    /**
     * JSON feed for potential dashboard widgets or future integrations.
     */
    public function programDistribution(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $distribution = AssessmentResult::query()
            ->whereNotNull('program_code')
            ->whereNotIn('program_code', ['N/A', 'PENDING'])
            ->selectRaw('program_code, count(*) as total')
            ->groupBy('program_code')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'programs' => $distribution,
        ]);
    }
}
