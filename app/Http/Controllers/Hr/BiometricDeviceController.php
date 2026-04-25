<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Services\BiometricDeviceService;
use App\Services\BiometricSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class BiometricDeviceController extends Controller
{
    protected BiometricDeviceService $deviceService;
    protected BiometricSyncService $syncService;

    public function __construct(BiometricDeviceService $deviceService, BiometricSyncService $syncService)
    {
        $this->deviceService = $deviceService;
        $this->syncService = $syncService;
    }

    /**
     * Display listing of biometric devices
     */
    public function index()
    {
        $devices = BiometricDevice::with('employees')->latest()->get();
        return view('hr.biometric-devices.index', compact('devices'));
    }

    /**
     * Store a new device
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'model' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Encrypt password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Crypt::encryptString($validated['password']);
        }

        $device = BiometricDevice::create($validated);

        // Test connection
        $testResult = $this->deviceService->testConnection($device);

        if ($testResult['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Device added and connection verified successfully!',
                'device' => $device,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Device added but connection failed: ' . $testResult['message'],
                'device' => $device,
            ], 422);
        }
    }

    /**
     * Update device
     */
    public function update(Request $request, BiometricDevice $device)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'model' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Encrypt password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Crypt::encryptString($validated['password']);
        } else {
            unset($validated['password']); // Don't update if empty
        }

        $device->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Device updated successfully!',
            'device' => $device,
        ]);
    }

    /**
     * Delete device
     */
    public function destroy(BiometricDevice $device)
    {
        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device deleted successfully!',
        ]);
    }

    /**
     * Test device connection
     */
    public function testConnection(BiometricDevice $device)
    {
        $result = $this->deviceService->testConnection($device);

        return response()->json($result);
    }

    /**
     * Sync all employees to device
     */
    public function syncEmployees(BiometricDevice $device)
    {
        $result = $this->syncService->syncAllEmployeesToDevice($device);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'synced' => $result['synced'],
            'failed' => $result['failed'],
        ]);
    }

    /**
     * Pull attendance logs from device
     */
    public function pullAttendance(BiometricDevice $device)
    {
        $result = $this->syncService->pullAttendanceFromDevice($device);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'created' => $result['created'] ?? 0,
            'skipped' => $result['skipped'] ?? 0,
        ]);
    }
}
