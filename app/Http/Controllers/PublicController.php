<?php

namespace App\Http\Controllers;

use App\Models\ScanActionLog;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function terms()
    {
        return view('pages.terms');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function help()
    {
        return view('pages.help');
    }

    /**
     * GET /cron-process-queue
     * HTTP-accessible queue processor (equivalent to CI3 cron route).
     */
    public function processQueue(\App\Services\ExtractService $service)
    {
        $items = $service->getPendingQueueItems(5);

        if (empty($items)) {
            return response()->json(['status' => 'success', 'message' => 'No pending items in queue.']);
        }

        $successCount = 0;
        $failCount    = 0;
        $errors       = [];

        foreach ($items as $queue) {
            $endpoint = null; $fileUrl = null; $responseCode = null; $message = null; $status = 'started';

            ScanActionLog::log($queue->scan_id, 'extraction_started', 'Extraction Started');

            try {
                $endpoint = $service->getApiEndpoint($queue->type_id);
                if (!$endpoint) throw new \Exception('API endpoint not found.');

                $fileUrl = $service->getFileLocation($queue->scan_id);
                if (!$fileUrl) throw new \Exception('File not found.');

                $apiResponse = $service->callExternalApi($endpoint, $fileUrl);
                $responseCode = $apiResponse['statusCode'];

                if ($responseCode !== 200 || empty($apiResponse['data'])) throw new \Exception('API call failed.');

                $saved = $service->storeExtractedData($queue->type_id, $queue->scan_id, $apiResponse['data'], $queue->created_by, $queue->created_at);
                if (!$saved) throw new \Exception('Failed to store data.');

                $service->updateQueueStatus($queue->id, 'completed');
                $status = 'success'; $message = 'Processed successfully'; $successCount++;

                ScanActionLog::log($queue->scan_id, 'extraction_completed', 'Extraction Completed');
            } catch (\Exception $e) {
                $service->updateQueueStatus($queue->id, 'failed');
                $status = 'failed'; $message = $e->getMessage(); $failCount++;
                $errors[] = "ID {$queue->id}: " . $e->getMessage();

                ScanActionLog::log($queue->scan_id, 'extraction_failed', 'Extraction Failed', $e->getMessage());
            }

            $service->logQueueProcess([
                'queue_id' => $queue->id, 'scan_id' => $queue->scan_id, 'type_id' => $queue->type_id,
                'status' => $status, 'api_endpoint' => $endpoint, 'file_url' => $fileUrl,
                'response_code' => $responseCode, 'message' => $message,
            ]);
        }

        return response()->json([
            'status' => $failCount > 0 ? 'partial' : 'success',
            'message' => "Queue processing completed. Success: {$successCount}, Failed: {$failCount}",
            'errors' => $errors,
        ]);
    }
}
