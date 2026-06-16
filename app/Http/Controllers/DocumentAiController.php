<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DocumentPrediction;
use App\Models\DocumentTrainingData;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentAiController extends Controller
{
    private string $pythonBin;
    private string $pythonPath;
    private string $uploadPath;

    public function __construct()
    {
        $this->pythonPath = public_path('python');
        $this->uploadPath = storage_path('app/public/document-ai');

        // Auto-create upload folder if not exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }

        // Detect python binary — prefer venv if it exists
        $venvPython = $this->pythonPath . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR
            . (PHP_OS_FAMILY === 'Windows' ? 'Scripts' : 'bin') . DIRECTORY_SEPARATOR . 'python';

        if (PHP_OS_FAMILY === 'Windows') {
            $venvPython .= '.exe';
        }

        if (file_exists($venvPython)) {
            $this->pythonBin = '"' . $venvPython . '"';
        } else {
            // Fallback: system python
            exec('python3 --version 2>&1', $out, $code);
            $this->pythonBin = ($code === 0) ? 'python3' : 'python';
        }
    }

    /**
     * PAGE: Playground
     */
    public function playground()
    {
        $types = DocumentType::where('is_active', true)
            ->orderBy('label')
            ->get();

        return view('document-ai.playground', compact('types'));
    }

    /**
     * AJAX: Predict Document
     */
    public function predictDocument(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        // 1. Store uploaded file
        $file         = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension    = strtolower($file->getClientOriginalExtension());
        $storedName   = time() . '_' . uniqid() . '.' . $extension;
        $file->move($this->uploadPath, $storedName);
        $storedPath   = $this->uploadPath . DIRECTORY_SEPARATOR . $storedName;

        // 2. Create prediction record (status=pending)
        $prediction = DocumentPrediction::create([
            'original_filename' => $originalName,
            'stored_filename'   => $storedName,
            'file_extension'    => $extension,
            'status'            => 'pending',
            'created_by'        => auth()->id(),
        ]);

        // 3. Build training data from database
        $types = DocumentType::with('activeTrainingData')
            ->where('is_active', true)
            ->get();

        $trainingData = $types->map(function ($type) {
            return [
                'id'             => $type->id,
                'name'           => $type->label,
                'texts'          => $type->activeTrainingData->pluck('sample_text')->toArray(),
                'keywords'       => $type->activeTrainingData->pluck('keywords')->filter()->implode(','),
                'title_patterns' => $type->activeTrainingData->pluck('title_patterns')->filter()->implode(','),
            ];
        })->values();

        // 4. Try Python AI first, fallback to PHP keyword matching
        $result = $this->tryPythonPrediction($storedPath, $trainingData->toJson());

        if (!$result) {
            // Fallback: PHP-based keyword prediction (works on shared hosting)
            $result = $this->phpKeywordPrediction($storedPath, $extension, $trainingData->toArray());
        }

        if (!$result || !($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Prediction failed',
            ], 500);
        }

        // 5. Update prediction record with results
        $predTypeId  = $result['prediction']['basis_id'] ?? null;
        $confidence  = $result['prediction']['confidence'] ?? 0;
        $ocrText     = $this->cleanUtf8String($result['ocr_text'] ?? '');

        // 5b. Build reasoning data
        $reasoning = $this->buildReasoning($ocrText, $result['all_scores'] ?? []);

        $prediction->update([
            'ocr_text'                => $ocrText,
            'predicted_type_id'       => $predTypeId,
            'confidence_score'        => $confidence,
            'prediction_reasoning'    => $reasoning,
            'status'                  => 'predicted',
            'ocr_page_count'          => $result['page_count'] ?? 1,
            'ocr_page_texts'          => $this->cleanOcrPageTexts($result['pages'] ?? []),
        ]);

        // 7. Return JSON
        return response()->json([
            'success'        => true,
            'prediction_id'  => $prediction->id,
            'file_url'       => asset('storage/document-ai/' . $storedName),
            'file_ext'       => $extension,
            'ocr_text'       => $ocrText,
            'ocr_confidence' => $result['ocr_confidence'] ?? 0,
            'page_count'     => $result['page_count'] ?? 1,
            'prediction'     => [
                'basis_id'   => $predTypeId,
                'basis_name' => $result['prediction']['basis_name'] ?? '',
                'confidence' => $confidence,
            ],
            'all_scores'     => $result['all_scores'] ?? [],
            'reasoning'      => $reasoning,
        ]);
    }

    /**
     * AJAX: Save Classification
     */
    public function saveClassification(Request $request)
    {
        $request->validate([
            'prediction_id'   => 'required|integer|exists:document_predictions,id',
            'basis_id'        => 'required|integer|exists:document_types,id',
            'user_remark'     => 'nullable|string|max:500',
            'add_to_training' => 'nullable|boolean',
        ]);

        $prediction = DocumentPrediction::findOrFail($request->prediction_id);

        // Determine status: confirmed or corrected
        $status = ($prediction->predicted_type_id == $request->basis_id)
            ? 'confirmed'
            : 'corrected';

        // Clean user remark to prevent UTF-8 encoding issues
        $userRemark = $request->user_remark ? $this->cleanUtf8String($request->user_remark) : null;

        $prediction->update([
            'confirmed_type_id' => $request->basis_id,
            'user_remark'       => $userRemark,
            'status'            => $status,
        ]);

        // Train AI: save OCR text as new training record
        if ($request->boolean('add_to_training') && !empty($prediction->ocr_text)) {
            $sampleText = $this->cleanUtf8String(substr($prediction->ocr_text, 0, 5000));
            DocumentTrainingData::create([
                'document_type_id' => $request->basis_id,
                'sample_text'      => $sampleText,
                'keywords'         => null,
                'status'           => 'active',
                'created_by'       => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Classification saved successfully.',
            'status'  => $status,
        ]);
    }

    /**
     * PAGE: Classification Settings
     */
    public function classificationSettings()
    {
        $types = DocumentType::with('trainingData')
            ->withCount([
                'trainingData',
                'trainingData as active_training_count' => function ($q) {
                    $q->where('status', 'active');
                },
            ])->orderBy('label')->get();

        // Pre-build training data map for JS
        $trainingDataMap = $types->mapWithKeys(function ($type) {
            return [$type->id => $type->trainingData->map(function ($t) {
                return [
                    'id'             => $t->id,
                    'sample_text'    => $t->sample_text,
                    'keywords'       => $t->keywords,
                    'title_patterns' => $t->title_patterns,
                    'status'         => $t->status,
                    'created_at'     => $t->created_at ? $t->created_at->format('d M Y, h:i A') : null,
                ];
            })->values()];
        });

        return view('document-ai.settings', compact('types', 'trainingDataMap'));
    }

    /**
     * AJAX: Store Training Data
     */
    public function storeTrainingData(Request $request)
    {
        $request->validate([
            'document_type_id' => 'required|integer|exists:document_types,id',
            'sample_text'      => 'required|string|min:10',
            'keywords'         => 'nullable|string|max:500',
            'title_patterns'   => 'nullable|string|max:500',
            'status'           => 'required|in:active,inactive',
        ]);

        $training = DocumentTrainingData::create([
            'document_type_id' => $request->document_type_id,
            'sample_text'      => $this->cleanUtf8String($request->sample_text),
            'keywords'         => $request->keywords ? $this->cleanUtf8String($request->keywords) : null,
            'title_patterns'   => $request->title_patterns ? $this->cleanUtf8String($request->title_patterns) : null,
            'status'           => $request->status,
            'created_by'       => auth()->id(),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Training data added.',
            'training' => $training->load('documentType'),
        ]);
    }

    /**
     * AJAX: Update Training Data
     */
    public function updateTrainingData(Request $request, $id)
    {
        $training = DocumentTrainingData::findOrFail($id);

        $request->validate([
            'sample_text'    => 'nullable|string|min:10',
            'keywords'       => 'nullable|string|max:500',
            'title_patterns' => 'nullable|string|max:500',
            'status'         => 'nullable|in:active,inactive',
        ]);

        $updateData = [];
        if ($request->has('sample_text') && $request->sample_text !== null) {
            $updateData['sample_text'] = $this->cleanUtf8String($request->sample_text);
        }
        if ($request->has('keywords')) {
            $updateData['keywords'] = $request->keywords ? $this->cleanUtf8String($request->keywords) : null;
        }
        if ($request->has('title_patterns')) {
            $updateData['title_patterns'] = $request->title_patterns ? $this->cleanUtf8String($request->title_patterns) : null;
        }
        if ($request->has('status') && $request->status !== null) {
            $updateData['status'] = $request->status;
        }

        if (!empty($updateData)) {
            $training->update($updateData);
        }

        return response()->json(['success' => true, 'message' => 'Updated.']);
    }

    /**
     * AJAX: Delete Training Data
     */
    public function deleteTrainingData($id)
    {
        DocumentTrainingData::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Deleted.']);
    }

    /**
     * AJAX: Toggle Document Type active status (for AI training purposes)
     */
    public function toggleBasisStatus($id)
    {
        $type = DocumentType::findOrFail($id);
        $type->update([
            'is_active' => !$type->is_active,
        ]);

        return response()->json([
            'success' => true,
            'status'  => $type->is_active ? 'active' : 'inactive',
        ]);
    }

    /**
     * AJAX: Get prediction reasoning detail for a specific prediction
     */
    public function getPredictionReasoning($id)
    {
        $prediction = DocumentPrediction::with(['predictedType', 'predictedDepartment', 'predictedLocation'])
            ->findOrFail($id);

        return response()->json([
            'success'   => true,
            'reasoning' => $prediction->prediction_reasoning,
            'prediction' => [
                'type'       => $prediction->predictedType?->label ?? 'Unknown',
                'type_confidence' => $prediction->confidence_score,
                'department' => $prediction->predictedDepartment?->department_name ?? 'Not detected',
                'dept_code'  => $prediction->predictedDepartment?->department_code ?? '-',
                'dept_confidence' => $prediction->department_confidence,
                'location'   => $prediction->predictedLocation?->name ?? 'Not detected',
                'loc_state'  => $prediction->predictedLocation?->state_name ?? '-',
                'loc_confidence' => $prediction->location_confidence,
            ],
        ]);
    }

    /**
     * PAGE: Department Prediction Rules
     */
    public function deptRules()
    {
        $departments = Department::active()->orderBy('department_name')->get();
        $rules = \App\Models\DepartmentPredictionRule::with('department')
            ->orderBy('department_id')
            ->orderBy('rule_type')
            ->orderByDesc('weight')
            ->get()
            ->groupBy('department_id');

        return view('document-ai.dept-rules', compact('departments', 'rules'));
    }

    /**
     * AJAX: Get dept rules data
     */
    public function deptRulesData(Request $request)
    {
        $rules = \App\Models\DepartmentPredictionRule::with('department')
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('rule_type'), fn($q) => $q->where('rule_type', $request->rule_type))
            ->orderBy('department_id')
            ->orderByDesc('weight')
            ->get();

        return response()->json(['success' => true, 'rules' => $rules]);
    }

    /**
     * AJAX: Store dept rule
     */
    public function storeDeptRule(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'rule_type'     => 'required|in:doc_type,vendor_keyword,content_keyword',
            'pattern'       => 'required|string|max:255',
            'weight'        => 'required|integer|min:1|max:100',
        ]);

        $rule = \App\Models\DepartmentPredictionRule::create([
            'department_id' => $request->department_id,
            'rule_type'     => $request->rule_type,
            'pattern'       => strtolower(trim($request->pattern)),
            'weight'        => $request->weight,
            'is_active'     => true,
            'created_by'    => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Rule added.', 'rule' => $rule->load('department')]);
    }

    /**
     * AJAX: Update dept rule
     */
    public function updateDeptRule(Request $request, $id)
    {
        $rule = \App\Models\DepartmentPredictionRule::findOrFail($id);

        $request->validate([
            'pattern'   => 'nullable|string|max:255',
            'weight'    => 'nullable|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $rule->update(array_filter($request->only(['pattern', 'weight', 'is_active']), fn($v) => $v !== null));

        return response()->json(['success' => true, 'message' => 'Updated.']);
    }

    /**
     * AJAX: Delete dept rule
     */
    public function deleteDeptRule($id)
    {
        \App\Models\DepartmentPredictionRule::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Deleted.']);
    }

    /**
     * PAGE: Prediction Logs with DataTable
     */
    public function predictionLogs()
    {
        $types = DocumentType::where('is_active', true)->orderBy('label')->get();
        $departments = Department::active()->orderBy('department_name')->get();

        return view('document-ai.logs', compact('types', 'departments'));
    }

    /**
     * AJAX: Server-side DataTable data for prediction logs
     */
    public function predictionLogsData(Request $request)
    {
        $query = DocumentPrediction::with(['predictedType', 'predictedDepartment', 'predictedLocation', 'creator']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type_id')) {
            $query->where('predicted_type_id', $request->type_id);
        }
        if ($request->filled('department_id')) {
            $query->where('predicted_department_id', $request->department_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('confidence_min')) {
            $query->where('confidence_score', '>=', $request->confidence_min);
        }
        if ($request->filled('confidence_max')) {
            $query->where('confidence_score', '<=', $request->confidence_max);
        }

        // Search
        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('ocr_text', 'like', "%{$search}%");
            });
        }

        $total = DocumentPrediction::count();
        $filtered = $query->count();

        // Sorting
        $orderCol = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'original_filename', 'predicted_type_id', 'confidence_score', 'status', 'created_at'];
        $sortBy = $columns[$orderCol] ?? 'created_at';
        $query->orderBy($sortBy, $orderDir === 'asc' ? 'asc' : 'desc');

        // Pagination
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $rows = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(function ($p) {
                return [
                    'id'              => $p->id,
                    'filename'        => $p->original_filename,
                    'file_ext'        => $p->file_extension,
                    'file_url'        => asset('storage/document-ai/' . $p->stored_filename),
                    'predicted_type'  => $p->predictedType?->label ?? '-',
                    'confidence'      => $p->confidence_score,
                    'department'      => $p->predictedDepartment?->department_name ?? '-',
                    'dept_code'       => $p->predictedDepartment?->department_code ?? '',
                    'location'        => $p->predictedLocation?->name ?? '-',
                    'status'          => $p->status,
                    'confirmed_type'  => $p->confirmedType?->label ?? null,
                    'user_remark'     => $p->user_remark,
                    'created_by'      => $p->creator?->name ?? '-',
                    'created_at'      => $p->created_at?->format('d M Y, h:i A'),
                    'ocr_text_short'  => substr($p->ocr_text ?? '', 0, 100),
                ];
            }),
        ]);
    }

    /**
     * PAGE: Analytics Dashboard
     */
    public function analytics()
    {
        return view('document-ai.analytics');
    }

    /**
     * AJAX: Analytics data for charts and stats
     */
    public function analyticsData(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        // Total predictions
        $totalPredictions = DocumentPrediction::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)->count();

        // Today's count
        $todayCount = DocumentPrediction::whereDate('created_at', today())->count();

        // By status
        $byStatus = DocumentPrediction::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // By document type (top 10)
        $byType = DocumentPrediction::whereDate('document_predictions.created_at', '>=', $dateFrom)
            ->whereDate('document_predictions.created_at', '<=', $dateTo)
            ->whereNotNull('predicted_type_id')
            ->join('document_types', 'document_predictions.predicted_type_id', '=', 'document_types.id')
            ->selectRaw('document_types.label, COUNT(*) as count, AVG(document_predictions.confidence_score) as avg_conf')
            ->groupBy('document_types.label')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn($r) => [$r->label => ['count' => $r->count, 'avg_conf' => round($r->avg_conf, 1)]]);

        // By department
        $byDepartment = DocumentPrediction::whereDate('document_predictions.created_at', '>=', $dateFrom)
            ->whereDate('document_predictions.created_at', '<=', $dateTo)
            ->whereNotNull('predicted_department_id')
            ->join('departments', 'document_predictions.predicted_department_id', '=', 'departments.id')
            ->selectRaw('departments.department_name, departments.department_code, COUNT(*) as count')
            ->groupBy('departments.department_name', 'departments.department_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Average confidence
        $avgConfidence = DocumentPrediction::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->whereNotNull('confidence_score')
            ->avg('confidence_score');

        // Yesterday's avg for trend
        $yesterdayAvg = DocumentPrediction::whereDate('created_at', now()->subDay())
            ->whereNotNull('confidence_score')
            ->avg('confidence_score');
        $confTrend = $avgConfidence && $yesterdayAvg ? round($avgConfidence - $yesterdayAvg, 1) : 0;

        // Accuracy rate
        $confirmed = DocumentPrediction::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'confirmed')->count();
        $corrected = DocumentPrediction::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'corrected')->count();
        $accuracyRate = ($confirmed + $corrected) > 0
            ? round(($confirmed / ($confirmed + $corrected)) * 100, 1) : 0;

        // Daily trend
        $dailyTrend = DocumentPrediction::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(confidence_score) as avg_conf')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Confidence distribution
        $confDistribution = [
            '90-100%' => DocumentPrediction::whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo)->whereBetween('confidence_score', [90, 100])->count(),
            '80-89%'  => DocumentPrediction::whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo)->whereBetween('confidence_score', [80, 89.99])->count(),
            '70-79%'  => DocumentPrediction::whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo)->whereBetween('confidence_score', [70, 79.99])->count(),
            '60-69%'  => DocumentPrediction::whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo)->whereBetween('confidence_score', [60, 69.99])->count(),
            '<60%'    => DocumentPrediction::whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo)->where('confidence_score', '<', 60)->count(),
        ];

        // Error analysis — what got corrected
        $corrections = DocumentPrediction::whereDate('document_predictions.created_at', '>=', $dateFrom)
            ->whereDate('document_predictions.created_at', '<=', $dateTo)
            ->where('document_predictions.status', 'corrected')
            ->whereNotNull('predicted_type_id')
            ->whereNotNull('confirmed_type_id')
            ->join('document_types as predicted', 'document_predictions.predicted_type_id', '=', 'predicted.id')
            ->join('document_types as confirmed', 'document_predictions.confirmed_type_id', '=', 'confirmed.id')
            ->selectRaw('predicted.label as predicted_label, confirmed.label as confirmed_label, COUNT(*) as count')
            ->groupBy('predicted.label', 'confirmed.label')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Reviewer activity — who confirmed/corrected fastest
        $reviewerActivity = DocumentPrediction::whereDate('document_predictions.created_at', '>=', $dateFrom)
            ->whereDate('document_predictions.created_at', '<=', $dateTo)
            ->whereIn('document_predictions.status', ['confirmed', 'corrected'])
            ->join('users', 'document_predictions.created_by', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as total, SUM(CASE WHEN document_predictions.status = "confirmed" THEN 1 ELSE 0 END) as confirmed, SUM(CASE WHEN document_predictions.status = "corrected" THEN 1 ELSE 0 END) as corrected')
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return response()->json([
            'total_predictions'       => $totalPredictions,
            'today_count'             => $todayCount,
            'by_status'               => $byStatus,
            'by_type'                 => $byType,
            'by_department'           => $byDepartment,
            'avg_confidence'          => round($avgConfidence ?? 0, 2),
            'conf_trend'              => $confTrend,
            'accuracy_rate'           => $accuracyRate,
            'confirmed_count'         => $confirmed,
            'corrected_count'         => $corrected,
            'daily_trend'             => $dailyTrend,
            'confidence_distribution' => $confDistribution,
            'corrections'             => $corrections,
            'reviewer_activity'       => $reviewerActivity,
        ]);
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Try running Python AI prediction. Returns null if Python is unavailable.
     */
    private function tryPythonPrediction(string $storedPath, string $trainingJson): ?array
    {

        // Quick check: can Python even run? (2 second timeout)
        $testCmd = $this->pythonBin . ' -c "print(1)" 2>&1';
        $testOutput = @shell_exec($testCmd);

        if (trim($testOutput ?? '') !== '1') {
            return null;
        }

        $trainingJsonFile = $this->uploadPath . DIRECTORY_SEPARATOR . uniqid('training_') . '.json';
        file_put_contents($trainingJsonFile, $trainingJson);

        $appPy = $this->pythonPath . DIRECTORY_SEPARATOR . 'document' . DIRECTORY_SEPARATOR . 'app.py';
        $cmd = sprintf(
            '%s %s --file %s --training-json-file %s --lang en',
            $this->pythonBin,
            escapeshellarg($appPy),
            escapeshellarg($storedPath),
            escapeshellarg($trainingJsonFile)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $this->pythonPath . DIRECTORY_SEPARATOR . 'document');
        $rawOutput = '';

        if (is_resource($process)) {
            fclose($pipes[0]);
            $rawOutput = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }

        @unlink($trainingJsonFile);

        if (empty($rawOutput)) {
            return null;
        }

        $result = json_decode($rawOutput, true);
        return ($result && ($result['success'] ?? false)) ? $result : null;
    }

    /**
     * PHP-only keyword-based prediction (works on shared hosting without Python ML).
     * Extracts text from filename context and uses keyword matching.
     */
    private function phpKeywordPrediction(string $storedPath, string $extension, array $trainingData): array
    {
        // For PHP fallback, we can't do OCR on images/PDFs without Python
        // But we CAN use the filename + any text-based content
        // On shared hosting, user should paste OCR text manually or use the filename
        $ocrText = $this->extractTextFallback($storedPath, $extension);

        if (empty(trim($ocrText))) {
            // If we can't extract text, use filename as hint
            $ocrText = pathinfo($storedPath, PATHINFO_FILENAME);
        }

        // Clean the OCR text to ensure valid UTF-8
        $ocrText = $this->cleanUtf8String($ocrText);
        $ocrLower = strtolower($ocrText);
        $ocrHeader = strtolower(substr($ocrText, 0, 500));

        $allScores = [];

        foreach ($trainingData as $basis) {
            $score = 0;
            $maxScore = 0;

            // Keyword matching
            $keywords = array_filter(array_map('trim', explode(',', $basis['keywords'] ?? '')));
            if (!empty($keywords)) {
                $matched = 0;
                foreach ($keywords as $kw) {
                    if ($kw && str_contains($ocrLower, strtolower($kw))) {
                        $matched++;
                    }
                }
                $score = count($keywords) > 0 ? ($matched / count($keywords)) * 70 : 0;
                $maxScore = max($maxScore, $score);
            }

            // Title pattern matching (strong signal)
            $titlePatterns = array_filter(array_map('trim', explode(',', $basis['title_patterns'] ?? '')));
            $titleBonus = 0;
            foreach ($titlePatterns as $pattern) {
                if ($pattern && str_contains($ocrHeader, strtolower($pattern))) {
                    $titleBonus = 25; // +25% for title in header
                    break;
                } elseif ($pattern && str_contains($ocrLower, strtolower($pattern))) {
                    $titleBonus = 15; // +15% for title anywhere
                    break;
                }
            }

            // Sample text similarity (simple word overlap)
            $sampleBonus = 0;
            foreach ($basis['texts'] ?? [] as $sampleText) {
                $sampleWords = array_unique(str_word_count(strtolower($sampleText), 1));
                $ocrWords = array_unique(str_word_count($ocrLower, 1));
                $overlap = count(array_intersect($sampleWords, $ocrWords));
                $sampleScore = count($sampleWords) > 0 ? ($overlap / count($sampleWords)) * 30 : 0;
                $sampleBonus = max($sampleBonus, $sampleScore);
            }

            $finalScore = min($maxScore + $titleBonus + $sampleBonus, 100);

            $allScores[] = [
                'basis_id'   => $basis['id'],
                'basis_name' => $this->cleanUtf8String($basis['name'] ?? ''),
                'confidence' => round($finalScore, 2),
            ];
        }

        // Apply negative penalty: if a type has title match, penalize others
        $hasTitle = collect($allScores)->contains(fn($s) => $s['confidence'] > 80);
        if ($hasTitle) {
            $topId = collect($allScores)->sortByDesc('confidence')->first()['basis_id'];
            $allScores = array_map(function ($s) use ($topId) {
                if ($s['basis_id'] !== $topId && $s['confidence'] > 60) {
                    $s['confidence'] = round(max($s['confidence'] - 8, 0), 2);
                }
                return $s;
            }, $allScores);
        }

        usort($allScores, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        $best = $allScores[0] ?? ['basis_id' => null, 'basis_name' => 'Unknown', 'confidence' => 0];

        return [
            'success'        => true,
            'ocr_text'       => $ocrText,
            'ocr_confidence' => 0, // No OCR confidence in PHP mode
            'page_count'     => 1,
            'pages'          => ['1' => $ocrText],
            'prediction'     => $best,
            'all_scores'     => $allScores,
        ];
    }

    /**
     * Try to extract text from file without Python (basic fallback).
     * Works for text-based PDFs using simple PHP.
     */
    private function extractTextFallback(string $filePath, string $extension): string
    {
        if ($extension === 'pdf') {
            // Try basic PDF text extraction (works for text-based PDFs, not scanned)
            $content = @file_get_contents($filePath);
            if ($content) {
                // Simple PDF text extraction (stream-based)
                $text = '';
                // Extract text between BT and ET markers
                if (preg_match_all('/\((.*?)\)/', $content, $matches)) {
                    $text = implode(' ', array_slice($matches[1], 0, 500));
                }
                // Also try to find readable text patterns
                if (strlen($text) < 50) {
                    // Try extracting from decoded streams
                    preg_match_all('/\/T\s*\((.*?)\)/', $content, $fieldMatches);
                    preg_match_all('/\/V\s*\((.*?)\)/', $content, $valueMatches);
                    $text .= ' ' . implode(' ', $fieldMatches[1] ?? []);
                    $text .= ' ' . implode(' ', $valueMatches[1] ?? []);
                }
                // Clean extracted text to ensure valid UTF-8
                return $this->cleanUtf8String(trim($text));
            }
        }

        // For images, we can't extract text without OCR
        // Return empty — the system will use filename as fallback
        return '';
    }

    /**
     * Build reasoning data explaining why each score was assigned.
     */
    private function buildReasoning(string $ocrText, array $allScores): array
    {
        // Clean the OCR text to ensure valid UTF-8 encoding
        $ocrText = $this->cleanUtf8String($ocrText);
        $ocrLower = strtolower($ocrText);
        $ocrSnippet = substr($ocrText, 0, 500);

        // Get training data keywords that matched for top predictions
        $typeReasoning = [];
        foreach (array_slice($allScores, 0, 5) as $score) {
            $type = DocumentType::find($score['basis_id']);
            if (!$type) continue;

            $trainingData = DocumentTrainingData::where('document_type_id', $type->id)
                ->where('status', 'active')
                ->get();

            $matchedKeywords = [];
            foreach ($trainingData as $td) {
                if (empty($td->keywords)) continue;
                $keywords = array_map('trim', explode(',', $td->keywords));
                foreach ($keywords as $kw) {
                    if ($kw && str_contains($ocrLower, strtolower($kw))) {
                        // Clean keywords as well to ensure valid UTF-8
                        $matchedKeywords[] = $this->cleanUtf8String($kw);
                    }
                }
            }

            $typeReasoning[] = [
                'type_name'        => $this->cleanUtf8String($score['basis_name'] ?? ''),
                'confidence'       => $score['confidence'],
                'matched_keywords' => array_unique($matchedKeywords),
                'keyword_count'    => count(array_unique($matchedKeywords)),
                'method'           => 'Sentence-Transformer cosine similarity + keyword bonus',
            ];
        }

        return [
            'ocr_snippet'    => $ocrSnippet,
            'ocr_length'     => strlen($ocrText),
            'type_reasoning' => $typeReasoning,
        ];
    }

    /**
     * Clean string to ensure valid UTF-8 encoding for JSON serialization.
     */
    private function cleanUtf8String(string $input): string
    {
        // Remove invalid UTF-8 characters and control characters
        $cleaned = mb_convert_encoding($input, 'UTF-8', 'UTF-8');
        
        // Remove non-printable characters except newline, tab, and carriage return
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleaned);
        
        // Ensure the string is valid UTF-8 and can be JSON encoded
        if (json_encode($cleaned) === false) {
            // If still can't encode, remove all non-ASCII characters as fallback
            $cleaned = preg_replace('/[^\x20-\x7E\x0A\x0D\x09]/', '', $input);
        }
        
        return $cleaned;
    }

    /**
     * Clean OCR page texts array to ensure all strings are valid UTF-8.
     */
    private function cleanOcrPageTexts(array $pages): array
    {
        $cleanedPages = [];
        foreach ($pages as $key => $page) {
            if (is_string($page)) {
                $cleanedPages[$key] = $this->cleanUtf8String($page);
            } elseif (is_array($page)) {
                // If page is an array with text content, clean recursively
                $cleanedPages[$key] = array_map(function($item) {
                    return is_string($item) ? $this->cleanUtf8String($item) : $item;
                }, $page);
            } else {
                $cleanedPages[$key] = $page;
            }
        }
        return $cleanedPages;
    }
}
