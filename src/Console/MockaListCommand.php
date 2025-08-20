<?php

namespace Metalogico\Mocka\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use function Laravel\Prompts\{info, warning, table};

class MockaListCommand extends Command
{
    protected $signature = 'mocka:list';

    protected $description = 'List configured Mocka mappings';

    public function handle(): int
    {
        $mappings = (array) config('mocka.mappings', []);
        $mocksPath = (string) config('mocka.mocks_path');
        if (empty($mappings)) {
            warning('No mappings configured.');
            return self::SUCCESS;
        }

        $rows = [];

        foreach ($mappings as $i => $map) {
            $url   = (string) ($map['url'] ?? '');
            $match = strtolower((string) ($map['match'] ?? 'exact'));
            $file  = (string) ($map['file'] ?? '');
            $key   = (string) ($map['key'] ?? '');
            $delay = $map['delay'] ?? '';
            $errors = $map['errors'] ?? '';

            $status = '✅ ok';
            $path = rtrim($mocksPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
            if ($file === '' || !is_file($path)) {
                $status = '❌ missing file';
            } else {
                $data = include $path;
                if (!is_array($data)) {
                    $status = '⚠️ invalid file';
                } elseif ($key !== '') {
                    $has = Arr::has($data, $key);
                    if (!$has) {
                        $status = '❌ missing key';
                    }
                } else {
                    // Empty key is considered missing in the new convention
                    $status = '❌ missing key';
                }
            }

            // Derive errors label shown in the table
            $errorsLabel = is_string($errors) ? $errors : (is_array($errors) ? 'inline' : '');

            // Validate errors profile/key when possible
            $errorsStatus = '-';
            if ($errors === '' || $errors === null) {
                $errorsStatus = '-';
            } elseif ($status === '❌ missing file' || $status === '⚠️ invalid file') {
                $errorsStatus = 'n/a';
            } else {
                if (is_string($errors)) {
                    // Check existence of the referenced errors key as-is only
                    $errorsStatus = Arr::has($data, $errors) ? 'ok' : 'missing key';
                } elseif (is_array($errors)) {
                    $errorsStatus = $this->validateErrorsProfile($errors);
                } else {
                    $errorsStatus = 'invalid';
                }
            }

            // Main row (mapping)
            $rows[] = [
                '#' => (string) $i,
                'url' => $url,
                'match' => $match,
                'file' => $file,
                'key' => $key,
                'delay' => (string) $delay,
                'status' => $status,
            ];

            // Sub-row (errors)
            $rows[] = [
                '#' => '',
                'url' => '',
                'match' => '',
                'file' => '',
                'key' => $errorsLabel ?: '-',
                'delay' => '',
                'status' => $errorsStatus,
            ];
        }

        $headers = ['#','url','match','file','key','delay','status'];

        table($headers, $rows);
        info('Total mappings: '.count($mappings));

        return self::SUCCESS;
    }

    // No method-prefixed fallback; keys must match exactly.

    private function validateErrorsProfile(array $def): string
    {
        // error_rate is optional but if present must be 0..100 numeric
        if (array_key_exists('error_rate', $def)) {
            $rate = $def['error_rate'];
            if (!is_numeric($rate)) {
                return 'invalid rate';
            }
            $rate = (int) $rate;
            if ($rate < 0 || $rate > 100) {
                return 'invalid rate';
            }
        }

        if (!isset($def['errors']) || !is_array($def['errors']) || empty($def['errors'])) {
            return 'invalid errors';
        }

        foreach ($def['errors'] as $status => $payload) {
            if (!is_numeric($status)) {
                return 'invalid status';
            }
            $code = (int) $status;
            if ($code < 100 || $code > 599) {
                return 'invalid status';
            }
        }

        return 'ok';
    }
}
