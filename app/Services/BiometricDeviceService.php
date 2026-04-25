<?php

namespace App\Services;

use App\Models\BiometricDevice;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class BiometricDeviceService
{
    protected function getClient(BiometricDevice $device)
    {
        // Try to decrypt or use as is
        try {
            $password = Crypt::decryptString($device->password);
        } catch (Exception $e) {
            $password = $device->password;
        }

        return new Client([
            'base_uri' => "http://{$device->ip_address}:{$device->port}/",
            'timeout' => 10,
            'auth' => [$device->username, $password, 'digest'],
            'headers' => [
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
            ],
        ]);
    }

    /**
     * Connect (Test Connection)
     */
    public function connect(BiometricDevice $device): bool
    {
        try {
            $client = $this->getClient($device);
            $response = $client->get('ISAPI/System/deviceInfo');

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            Log::error("Hikvision Connect Failed for {$device->ip_address}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Test Connection for Controller
     */
    public function testConnection(BiometricDevice $device): array
    {
        try {
            if ($this->connect($device)) {
                return ['success' => true, 'message' => 'Connection Successful'];
            }

            return ['success' => false, 'message' => 'Connection Failed (Check Credentials/Network)'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }

    /**
     * Get Attendance Logs via ISAPI (trying both JSON and XML formats)
     */
    public function getAttendanceLogs(BiometricDevice $device): array
    {
        try {
            // Try JSON format first (since it worked for user push)
            $logs = $this->getAttendanceLogsJson($device);

            if (! empty($logs)) {
                return $logs;
            }

            // Fallback to XML format
            return $this->getAttendanceLogsXml($device);

        } catch (Exception $e) {
            Log::error('Hikvision Log Pull Failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get Attendance Logs via JSON API
     */
    /**
     * Get Attendance Logs via JSON API
     */
    protected function getAttendanceLogsJson(BiometricDevice $device): array
    {
        try {
            $client = new Client([
                'base_uri' => "http://{$device->ip_address}:{$device->port}/",
                'timeout' => 30,
                'auth' => [$device->username, $this->getDecryptedPassword($device), 'digest'],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $allLogs = [];
            $searchPosition = 0;
            $hasMore = true;

            // Filter logs for the last 30 days to ensure performance
            $startTime = date('Y-m-d\TH:i:s', strtotime('-30 days'));
            $endTime = date('Y-m-d\TH:i:s');

            while ($hasMore) {
                // JSON Query for attendance events
                $searchData = [
                    'AcsEventCond' => [
                        'searchID' => '1',
                        'searchResultPosition' => $searchPosition,
                        'maxResults' => 100,
                        'major' => 0,
                        'minor' => 0,
                        'startTime' => $startTime,
                        'endTime' => $endTime,
                    ],
                ];

                $response = $client->post('ISAPI/AccessControl/AcsEvent?format=json', [
                    'json' => $searchData,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();

                if ($statusCode !== 200) {
                    Log::warning("JSON pull failed at position {$searchPosition}: Status {$statusCode}");
                    $hasMore = false;

                    continue;
                }

                $data = json_decode($body, true);
                $batchCount = 0;

                // Parse JSON response
                if (isset($data['AcsEvent']['InfoList'])) {
                    foreach ($data['AcsEvent']['InfoList'] as $info) {
                        $allLogs[] = [
                            'id' => (string) ($info['employeeNoString'] ?? $info['employeeNo'] ?? '0'),
                            'timestamp' => (string) ($info['time'] ?? ''),
                            'state' => 1,
                            'uid' => (string) ($info['serialNo'] ?? 0),
                        ];
                        $batchCount++;
                    }
                }

                Log::info("JSON batch pulled: {$batchCount} logs at position {$searchPosition}");

                if ($batchCount < 100) {
                    $hasMore = false;
                } else {
                    $searchPosition += 100;
                }

                // Safety break
                if ($searchPosition > 10000) {
                    $hasMore = false;
                }
            }

            Log::info('Total JSON logs found: '.count($allLogs));

            return $allLogs;

        } catch (Exception $e) {
            Log::warning('JSON attendance pull failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get Attendance Logs via XML API (fallback)
     */
    protected function getAttendanceLogsXml(BiometricDevice $device): array
    {
        try {
            $client = $this->getClient($device);

            $allLogs = [];
            $searchPosition = 0;
            $hasMore = true;

            // Filter logs for the last 30 days to ensure performance
            $startTime = date('Y-m-d\TH:i:s', strtotime('-30 days'));
            $endTime = date('Y-m-d\TH:i:s');

            while ($hasMore) {
                $xmlQuery = '<?xml version="1.0" encoding="utf-8"?>
                            <AcsEventCond version="2.0">
                                <searchID>1</searchID>
                                <searchResultPosition>'.$searchPosition.'</searchResultPosition>
                                <maxResults>100</maxResults>
                                <major>0</major>
                                <minor>0</minor>
                                <startTime>'.$startTime.'</startTime>
                                <endTime>'.$endTime.'</endTime>
                            </AcsEventCond>';

                $response = $client->post('ISAPI/AccessControl/AcsEvent', [
                    'body' => $xmlQuery,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $xmlString = (string) $response->getBody();

                if ($statusCode !== 200) {
                    Log::warning("XML pull failed at position {$searchPosition}: Status {$statusCode}");
                    $hasMore = false;

                    continue;
                }

                $xml = simplexml_load_string($xmlString);
                if ($xml === false) {
                    Log::warning("XML parse error at position {$searchPosition}");
                    $hasMore = false;

                    continue;
                }

                $batchCount = 0;

                if (isset($xml->InfoList) && isset($xml->InfoList->Info)) {
                    foreach ($xml->InfoList->Info as $info) {
                        $allLogs[] = [
                            'id' => (string) ($info->employeeNoString ?? $info->employeeNo ?? '0'),
                            'timestamp' => (string) ($info->time->time ?? $info->time),
                            'state' => 1,
                            'uid' => (string) ($info->serialNo ?? 0),
                        ];
                        $batchCount++;
                    }
                }

                Log::info("XML batch pulled: {$batchCount} logs at position {$searchPosition}");

                if ($batchCount < 100) {
                    $hasMore = false; // Less than maxResults means we reached the end
                } else {
                    $searchPosition += 100;
                }

                // Safety break
                if ($searchPosition > 10000) {
                    $hasMore = false;
                }
            }

            Log::info('Total XML logs found: '.count($allLogs));

            return $allLogs;

        } catch (Exception $e) {
            Log::warning('XML attendance pull failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Sync Device Time
     */
    public function syncTime(BiometricDevice $device): bool
    {
        try {
            $client = $this->getClient($device);
            $time = date('Y-m-d\TH:i:s');
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <Time>
                        <timeMode>manual</timeMode>
                        <localTime>$time</localTime>
                        <timeZone>CST-5:00:00</timeZone>
                    </Time>";

            $client->put('ISAPI/System/time', ['body' => $xml]);

            return true;
        } catch (Exception $e) {
            Log::error('Hikvision Time Sync Failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Add/Update user on device via Hikvision ISAPI (JSON format)
     */
    public function addUserToDevice(BiometricDevice $device, string $userId, string $name, string $password = ''): bool
    {
        try {
            $client = new Client([
                'base_uri' => "http://{$device->ip_address}:{$device->port}/",
                'timeout' => 10,
                'auth' => [$device->username, $this->getDecryptedPassword($device), 'digest'],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            // Hikvision ISAPI UserInfo in JSON format
            $userData = [
                'UserInfo' => [
                    'employeeNo' => $userId,
                    'name' => $name,
                    'userType' => 'normal',
                    'Valid' => [
                        'enable' => true,
                        'beginTime' => '2020-01-01T00:00:00',
                        'endTime' => '2037-12-31T23:59:59',
                    ],
                    'doorRight' => '1',
                    'RightPlan' => [
                        [
                            'doorNo' => 1,
                            'planTemplateNo' => '1',
                        ],
                    ],
                ],
            ];

            // Try to add user first
            $response = $client->post('ISAPI/AccessControl/UserInfo/Record?format=json', [
                'json' => $userData,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            Log::info("Add user attempt for {$userId}: Status {$statusCode}, Body: ".substr($body, 0, 200));

            // If user exists, try to modify instead
            if ($statusCode === 409 || $statusCode === 400 || strpos($body, 'userExisted') !== false || strpos($body, 'exist') !== false) {
                Log::info("User {$userId} exists, trying modify...");
                $response = $client->put('ISAPI/AccessControl/UserInfo/Modify?format=json', [
                    'json' => $userData,
                    'http_errors' => false,
                ]);
                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();
            }

            if ($statusCode === 200 || $statusCode === 201) {
                Log::info("User {$userId} ({$name}) pushed to device {$device->ip_address} via JSON");

                return true;
            }

            Log::warning("JSON Push failed. Status: {$statusCode}. Trying XML fallback...");

            return $this->addUserToDeviceXml($device, $userId, $name, $password);

        } catch (Exception $e) {
            Log::warning("Hikvision Add User JSON Failed for {$userId}: ".$e->getMessage().'. Trying XML fallback...');

            return $this->addUserToDeviceXml($device, $userId, $name, $password);
        }
    }

    /**
     * Add/Update user on device via Hikvision ISAPI (XML format)
     */
    protected function addUserToDeviceXml(BiometricDevice $device, string $userId, string $name, string $password = ''): bool
    {
        try {
            $client = $this->getClient($device);

            // Minimal XML Helper
            $xmlBody = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <UserInfo version=\"2.0\" xmlns=\"http://www.hikvision.com/ver20/XMLSchema\">
                <employeeNo>{$userId}</employeeNo>
                <name>{$name}</name>
                <userType>normal</userType>
                <closeDelayEnabled>false</closeDelayEnabled>
                <Valid>
                    <enable>true</enable>
                    <beginTime>2020-01-01T00:00:00</beginTime>
                    <endTime>2037-12-31T23:59:59</endTime>
                    <timeType>local</timeType>
                </Valid>
                <doorRight>1</doorRight>
                <RightPlan>
                    <doorNo>1</doorNo>
                    <planTemplateNo>1</planTemplateNo>
                </RightPlan>
            </UserInfo>";

            // Try to add user first (POST)
            try {
                $response = $client->post('ISAPI/AccessControl/UserInfo/Record', [
                    'body' => $xmlBody,
                    'http_errors' => false, // Handle 4xx manually
                ]);
                $statusCode = $response->getStatusCode();
            } catch (Exception $ex) {
                $statusCode = 500;
            }

            // If user exists (409/old firmware behavior), try PUT to Modify
            if ($statusCode !== 200 && $statusCode !== 201) {
                $response = $client->put('ISAPI/AccessControl/UserInfo/Modify', [
                    'body' => $xmlBody,
                    'http_errors' => false,
                ]);
                $statusCode = $response->getStatusCode();
            }

            if ($statusCode === 200 || $statusCode === 201) {
                Log::info("User {$userId} ({$name}) pushed to device {$device->ip_address} via XML");

                return true;
            }

            Log::error("XML Push User Failed. Status: {$statusCode}");

            return false;

        } catch (Exception $e) {
            Log::error("Hikvision Add User XML Failed for {$userId}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Get decrypted password
     */
    protected function getDecryptedPassword(BiometricDevice $device): string
    {
        try {
            return Crypt::decryptString($device->password);
        } catch (Exception $e) {
            return $device->password;
        }
    }

    public function clearLogs(BiometricDevice $device): bool
    {
        return false;
    }

    public function syncShifts(BiometricDevice $device): array
    {
        return $this->syncTime($device)
            ? ['success' => true, 'message' => 'Device Time Synced. Shifts handled by Server.']
            : ['success' => false, 'message' => 'Connection Failed.'];
    }
}
