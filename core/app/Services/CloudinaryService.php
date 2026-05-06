<?php

namespace App\Services;

/**
 * CloudinaryService — Full Cloudinary API integration
 *
 * Handles:
 *  - Signed uploads (images, video, raw files)
 *  - Deletion via public_id
 *  - Connection testing (ping via resources API)
 *  - Transformation URL generation
 *  - Folder management
 *  - Unsigned preset uploads (for direct browser uploads)
 *
 * Uses only cURL — no Cloudinary SDK required.
 */
class CloudinaryService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;
    private string $uploadPreset;
    private string $folder;

    public function __construct(SettingsService $settings)
    {
        $creds = $settings->cloudinaryCredentials();

        $this->cloudName    = $creds['cloud_name']    ?? '';
        $this->apiKey       = $creds['api_key']       ?? '';
        $this->apiSecret    = $creds['api_secret']    ?? '';
        $this->uploadPreset = $creds['upload_preset'] ?? '';
        $this->folder       = $settings->get('cloudinary_folder', 'college-cms');
    }

    /**
     * Check if all required credentials are present.
     */
    public function isConfigured(): bool
    {
        return !empty($this->cloudName)
            && !empty($this->apiKey)
            && !empty($this->apiSecret);
    }

    /**
     * Test the connection by pinging the Cloudinary usage API.
     * Returns ['ok' => bool, 'message' => string, 'plan' => string|null]
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'Credentials are not configured.'];
        }

        try {
            $url  = "https://api.cloudinary.com/v1_1/{$this->cloudName}/usage";
            $data = $this->apiGet($url);

            if (isset($data['error'])) {
                return ['ok' => false, 'message' => $data['error']['message'] ?? 'API error'];
            }

            $plan = $data['plan'] ?? 'Free';
            $usedMb = isset($data['storage']['usage'])
                ? round($data['storage']['usage'] / 1048576, 1) . ' MB'
                : null;

            return [
                'ok'      => true,
                'message' => "Connected — {$plan} plan" . ($usedMb ? " · {$usedMb} used" : ''),
                'plan'    => $plan,
                'storage' => $data['storage'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Upload a file to Cloudinary.
     *
     * @param  string  $filePath   Absolute path to local file
     * @param  string  $filename   Original filename (used to build public_id)
     * @param  string  $mime       MIME type of the file
     * @param  array   $options    Extra Cloudinary params (tags, context, etc.)
     * @return array   ['url' => string, 'public_id' => string, 'width', 'height', 'size', 'format']
     */
    public function upload(
        string $filePath,
        string $filename,
        string $mime,
        array  $options = []
    ): array {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Cloudinary is not configured.');
        }

        $resourceType = $this->resourceType($mime);
        $publicId     = $this->buildPublicId($filename);

        $params = array_merge([
            'public_id' => $publicId,
            'timestamp' => time(),
            'folder'    => $this->folder,
        ], $options);

        // Remove empty params
        $params = array_filter($params, fn($v) => $v !== null && $v !== '');

        // Add upload preset if set
        if ($this->uploadPreset) {
            $params['upload_preset'] = $this->uploadPreset;
        }

        // Build signature (exclude upload_preset from signing)
        $signature = $this->sign($params);

        $postFields = array_merge($params, [
            'file'      => new \CURLFile($filePath, $mime, basename($filename)),
            'api_key'   => $this->apiKey,
            'signature' => $signature,
        ]);

        $url      = "https://api.cloudinary.com/v1_1/{$this->cloudName}/{$resourceType}/upload";
        $response = $this->post($url, $postFields);

        if (isset($response['error'])) {
            throw new \RuntimeException('Cloudinary upload error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        if (empty($response['secure_url'])) {
            throw new \RuntimeException('Cloudinary returned no secure_url.');
        }

        return [
            'url'       => $response['secure_url'],
            'public_id' => $response['public_id'] ?? $publicId,
            'width'     => $response['width']      ?? null,
            'height'    => $response['height']     ?? null,
            'size'      => $response['bytes']      ?? 0,
            'format'    => $response['format']     ?? null,
            'asset_id'  => $response['asset_id']   ?? null,
        ];
    }

    /**
     * Delete a file from Cloudinary by its public_id.
     * Returns true on success, false if not found.
     */
    public function delete(string $publicId, string $resourceType = 'image'): bool
    {
        if (!$this->isConfigured() || empty($publicId)) {
            return false;
        }

        // Determine resource_type from public_id folder/format hints
        $params    = ['public_id' => $publicId, 'timestamp' => time()];
        $signature = $this->sign($params);

        $postFields = array_merge($params, [
            'api_key'   => $this->apiKey,
            'signature' => $signature,
        ]);

        $url      = "https://api.cloudinary.com/v1_1/{$this->cloudName}/{$resourceType}/destroy";
        $response = $this->post($url, $postFields, false);

        return ($response['result'] ?? '') === 'ok';
    }

    /**
     * Delete by trying all resource types until one succeeds.
     * Use this when you don't know the resource type.
     */
    public function deleteAny(string $publicId): bool
    {
        foreach (['image', 'video', 'raw'] as $type) {
            if ($this->delete($publicId, $type)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a transformation URL for an existing Cloudinary URL or public_id.
     *
     * @param  string $urlOrPublicId  Full Cloudinary URL or public_id
     * @param  array  $transforms     ['w' => 800, 'h' => 600, 'c' => 'fill', 'q' => 'auto', 'f' => 'auto']
     */
    public function transform(string $urlOrPublicId, array $transforms = []): string
    {
        if (empty($transforms)) {
            return $urlOrPublicId;
        }

        // If it's already a full URL, inject transformation string after /upload/
        if (str_starts_with($urlOrPublicId, 'http')) {
            $tStr = $this->buildTransformString($transforms);
            return preg_replace(
                '#(/upload/)(?!v\d)#',
                "/upload/{$tStr}/",
                $urlOrPublicId,
                1
            );
        }

        // Otherwise build full URL from public_id
        $tStr = $this->buildTransformString($transforms);
        return "https://res.cloudinary.com/{$this->cloudName}/image/upload/{$tStr}/{$urlOrPublicId}";
    }

    /**
     * Common transformation presets.
     */
    public function thumbnail(string $url, int $size = 300): string
    {
        return $this->transform($url, [
            'w' => $size, 'h' => $size, 'c' => 'fill', 'g' => 'auto',
            'q' => 'auto', 'f' => 'auto',
        ]);
    }

    public function webOptimized(string $url, int $maxWidth = 1200): string
    {
        return $this->transform($url, [
            'w' => $maxWidth, 'c' => 'limit',
            'q' => 'auto', 'f' => 'auto',
        ]);
    }

    /**
     * List files in the CMS folder.
     */
    public function listFolder(int $maxResults = 50): array
    {
        try {
            $folder = urlencode($this->folder);
            $url    = "https://api.cloudinary.com/v1_1/{$this->cloudName}/resources/image"
                    . "?type=upload&prefix={$folder}/&max_results={$maxResults}";
            $data   = $this->apiGet($url);
            return $data['resources'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get the folder name being used.
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function resourceType(string $mime): string
    {
        if (str_starts_with($mime, 'image/'))  return 'image';
        if (str_starts_with($mime, 'video/'))  return 'video';
        return 'raw';
    }

    private function buildPublicId(string $filename): string
    {
        // Strip extension (Cloudinary adds it back) and sanitize
        $noExt = pathinfo($filename, PATHINFO_FILENAME);
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $noExt);
    }

    /**
     * Build the Cloudinary request signature.
     * Params must be sorted, joined as k=v&k=v, then appended with secret.
     */
    private function sign(array $params): string
    {
        // Remove fields that are NOT signed
        $unsigned = ['file', 'api_key', 'upload_preset', 'resource_type'];
        $toSign   = array_diff_key($params, array_flip($unsigned));

        ksort($toSign);
        $str = implode('&', array_map(
            fn($k, $v) => "{$k}={$v}",
            array_keys($toSign),
            $toSign
        ));

        return sha1($str . $this->apiSecret);
    }

    private function buildTransformString(array $t): string
    {
        $parts = [];
        $map   = ['w' => 'w', 'h' => 'h', 'c' => 'c', 'g' => 'g', 'q' => 'q', 'f' => 'f',
                  'r' => 'r', 'e' => 'e', 'o' => 'o', 'a' => 'a', 'dpr' => 'dpr'];
        foreach ($map as $short => $cld) {
            if (isset($t[$short])) {
                $parts[] = "{$cld}_{$t[$short]}";
            }
        }
        return implode(',', $parts);
    }

    private function post(string $url, array $postFields, bool $decode = true): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("cURL error: {$error}");
        }

        if (!$decode) {
            return json_decode($response ?: '{}', true) ?? [];
        }

        if ($status < 200 || $status >= 300) {
            $body = json_decode($response ?: '{}', true) ?? [];
            $msg  = $body['error']['message'] ?? "HTTP {$status}";
            throw new \RuntimeException("Cloudinary API error: {$msg}");
        }

        return json_decode($response ?: '{}', true) ?? [];
    }

    private function apiGet(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => "{$this->apiKey}:{$this->apiSecret}",
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("cURL error: {$error}");
        }

        return json_decode($response ?: '{}', true) ?? [];
    }
}
