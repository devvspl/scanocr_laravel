<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Http\UploadedFile;

class S3Service
{
    private S3Client $client;
    private string   $bucket;
    private string   $region;

    public function __construct()
    {
        $this->region = config('filesystems.disks.s3.region', config('aws.region', 'ap-south-1'));
        $this->bucket = config('filesystems.disks.s3.bucket', config('aws.bucket', ''));

        $this->client = new S3Client([
            'version'     => 'latest',
            'region'      => $this->region,
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key',    config('aws.key', '')),
                'secret' => config('filesystems.disks.s3.secret', config('aws.secret', '')),
            ],
            'scheme' => 'https',
            'http'   => [
                'verify' => true,
                'curl'   => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2],
            ],
        ]);
    }

    /**
     * Upload an UploadedFile to S3.
     *
     * @return array{success: bool, url?: string, key?: string, error?: string}
     */
    public function upload(UploadedFile $file, string $folder, string $fileName): array
    {
        $key         = rtrim($folder, '/') . '/' . $fileName;
        $contentType = $file->getMimeType() ?? 'application/octet-stream';

        // Ensure the uploaded file actually landed on disk before hitting S3.
        $realPath = $file->getRealPath();
        if ($realPath === false || ! file_exists($realPath)) {
            return [
                'success' => false,
                'error'   => 'Uploaded file could not be read from the server temp directory. '
                           . 'Check that upload_tmp_dir is writable (current: ' . ini_get('upload_tmp_dir') . ').',
            ];
        }

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $key,
                'SourceFile'  => $realPath,
                'ContentType' => $contentType,
            ]);

            return [
                'success' => true,
                'url'     => "https://s3.{$this->region}.amazonaws.com/{$this->bucket}/{$key}",
                'key'     => $key,
            ];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage() ?: $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Move (copy + delete) an object within S3.
     *
     * @return array{success: bool, url?: string, key?: string, error?: string}
     */
    public function move(string $sourceKey, string $destKey): array
    {
        try {
            // Skip copy if destination already exists
            if ($this->client->doesObjectExist($this->bucket, $destKey)) {
                return [
                    'success' => true,
                    'url'     => $this->client->getObjectUrl($this->bucket, $destKey),
                    'key'     => $destKey,
                ];
            }

            $this->client->copyObject([
                'Bucket'     => $this->bucket,
                'CopySource' => $this->bucket . '/' . $sourceKey,
                'Key'        => $destKey,
            ]);

            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $sourceKey,
            ]);

            return [
                'success' => true,
                'url'     => $this->client->getObjectUrl($this->bucket, $destKey),
                'key'     => $destKey,
            ];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage() ?: $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get a pre-signed or path-style URL for an object.
     */
    public function getUrl(string $key): string
    {
        return $this->client->getObjectUrl($this->bucket, $key);
    }
}
