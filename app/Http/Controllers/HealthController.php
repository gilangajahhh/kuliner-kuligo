<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $status = 'ok';
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'connected',
                'driver' => DB::connection()->getDriverName(),
                'database' => DB::connection()->getDatabaseName(),
            ];
        } catch (\Throwable $e) {
            $status = 'error';
            $checks['database'] = ['status' => 'failed', 'message' => $e->getMessage()];
        }

        try {
            $checks['tabel_menu'] = ['status' => 'ok', 'jumlah_menu' => DB::table('menu')->count()];
        } catch (\Throwable $e) {
            $status = 'error';
            $checks['tabel_menu'] = ['status' => 'failed', 'message' => 'Tabel menu belum ada.'];
        }

        return response()->json([
            'status' => $status,
            'app' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $status === 'ok' ? 200 : 500);
    }
}
