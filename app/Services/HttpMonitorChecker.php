<?php

namespace App\Services;

use App\DTOs\HttpCheckResultDto;
use App\Models\HttpMonitorConfig;
use App\Models\Monitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class HttpMonitorChecker
{
    public function check(Monitor $monitor): HttpCheckResultDto
    {
        /** @var HttpMonitorConfig|null $config */
        $config = $monitor->httpConfig;

        if (! $config) {
            return new HttpCheckResultDto(
                success: false,
                errorMessage: 'HTTP monitor config is missing.',
            );
        }

        $startedAt = hrtime(true);

        try {
            $response = Http::timeout($config->timeout_seconds)
                ->withHeaders([
                    'User-Agent' => 'StillupMonitor/1.0',
                    'Accept' => '*/*',
                ])
                ->send($config->method, $config->url);

            $responseTimeMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $statusCode = $response->status();

            if ($statusCode !== $config->expected_status) {
                return new HttpCheckResultDto(
                    success: false,
                    statusCode: $statusCode,
                    responseTimeMs: $responseTimeMs,
                    errorMessage: "Expected status {$config->expected_status}, got {$statusCode}.",
                );
            }

            if (filled($config->keyword) && ! str_contains($response->body(), $config->keyword)) {
                return new HttpCheckResultDto(
                    success: false,
                    statusCode: $statusCode,
                    responseTimeMs: $responseTimeMs,
                    errorMessage: "Response body does not contain expected keyword \"{$config->keyword}\".",
                );
            }

            return new HttpCheckResultDto(
                success: true,
                statusCode: $statusCode,
                responseTimeMs: $responseTimeMs,
            );
        } catch (ConnectionException $exception) {
            return new HttpCheckResultDto(
                success: false,
                responseTimeMs: (int) round((hrtime(true) - $startedAt) / 1_000_000),
                errorMessage: $this->truncateError($exception->getMessage() ?: 'Connection failed.'),
            );
        } catch (RequestException $exception) {
            $response = $exception->response;
            $statusCode = $response?->status();

            return new HttpCheckResultDto(
                success: false,
                statusCode: $statusCode,
                responseTimeMs: (int) round((hrtime(true) - $startedAt) / 1_000_000),
                errorMessage: $this->truncateError($exception->getMessage() ?: 'Request failed.'),
            );
        } catch (Throwable $exception) {
            return new HttpCheckResultDto(
                success: false,
                responseTimeMs: (int) round((hrtime(true) - $startedAt) / 1_000_000),
                errorMessage: $this->truncateError($exception->getMessage() ?: 'Unexpected check failure.'),
            );
        }
    }

    private function truncateError(string $message): string
    {
        return mb_substr($message, 0, 1000);
    }
}
