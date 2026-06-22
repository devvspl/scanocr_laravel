<?php

namespace App\Console\Commands;

use App\Services\ExtractService;
use Illuminate\Console\Command;

class ProcessQueue extends Command
{
    protected $signature = 'queue:process-extract {--limit=5 : Number of items to process} {--id= : Process a specific queue ID}';
    protected $description = 'Process pending extraction queue items (calls external API, stores data, moves to punchfile)';

    public function handle(ExtractService $service): int
    {
        $specificId = $this->option('id');
        $limit      = (int) $this->option('limit');

        if ($specificId) {
            $items = \DB::table('tbl_queues')
                ->whereIn('status', ['pending', 'failed'])
                ->where('id', $specificId)
                ->get()
                ->toArray();
        } else {
            $items = $service->getPendingQueueItems($limit);
        }

        if (empty($items)) {
            $this->info('No pending items in queue.');
            return 0;
        }

        $this->info('Processing ' . count($items) . ' queue item(s)...');

        $successCount = 0;
        $failCount    = 0;

        foreach ($items as $queue) {
            $endpoint     = null;
            $fileUrl      = null;
            $responseCode = null;
            $message      = null;
            $status       = 'started';

            try {
                // Get API endpoint
                $endpoint = $service->getApiEndpoint($queue->type_id);
                if (!$endpoint) {
                    throw new \Exception('API endpoint not found for type_id: ' . $queue->type_id);
                }

                // Get file URL
                $fileUrl = $service->getFileLocation($queue->scan_id);
                if (!$fileUrl) {
                    throw new \Exception('File not found for scan_id: ' . $queue->scan_id);
                }

                // Call external API
                $apiResponse  = $service->callExternalApi($endpoint, $fileUrl);
                $responseCode = $apiResponse['statusCode'];

                if ($responseCode !== 200 || empty($apiResponse['data'])) {
                    throw new \Exception('API call failed with status: ' . $responseCode);
                }

                // Store extracted data + move to punchfile
                $saved = $service->storeExtractedData(
                    $queue->type_id,
                    $queue->scan_id,
                    $apiResponse['data'],
                    $queue->created_by,
                    $queue->created_at
                );

                if (!$saved) {
                    throw new \Exception('Failed to store extracted data.');
                }

                // Mark success
                $service->updateQueueStatus($queue->id, 'completed');
                $status  = 'success';
                $message = 'Processed successfully';
                $successCount++;

                $this->line("  ✓ Queue #{$queue->id} (scan {$queue->scan_id}) — success");

            } catch (\Exception $e) {
                $service->updateQueueStatus($queue->id, 'failed');
                $status  = 'failed';
                $message = $e->getMessage();
                $failCount++;

                $this->error("  ✗ Queue #{$queue->id} (scan {$queue->scan_id}) — {$e->getMessage()}");
            }

            // Always log the process
            $service->logQueueProcess([
                'queue_id'      => $queue->id,
                'scan_id'       => $queue->scan_id,
                'type_id'       => $queue->type_id,
                'status'        => $status,
                'api_endpoint'  => $endpoint,
                'file_url'      => $fileUrl,
                'response_code' => $responseCode,
                'message'       => $message,
            ]);
        }

        $this->newLine();
        $this->info("Done. Success: {$successCount}, Failed: {$failCount}");

        return $failCount > 0 ? 1 : 0;
    }
}
