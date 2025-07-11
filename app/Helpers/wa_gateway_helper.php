<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class WaGatewayHelper
{
    public static function wa_payment($phone, $message)
    {
        try {
            $gatewayUrl = env('WAGATEWAY_URL', 'http://localhost:3001/api');
            $response = Http::timeout(5)->get($gatewayUrl . '/health');
            $data = $response->json();
            $sessions = $data['sessions'] ?? [];

            if (empty($sessions)) {
                return 'Tidak ada session aktif';
            }

            // Round-robin dengan cache untuk sesi terakhir
            $lastSession = Cache::get('wa_last_session');
            $nextIndex = 0;

            if ($lastSession && in_array($lastSession, $sessions)) {
                $lastIndex = array_search($lastSession, $sessions);
                $nextIndex = ($lastIndex + 1) % count($sessions);
            }

            // Rotasi urutan session berdasarkan round-robin
            $rotatedSessions = array_merge(
                array_slice($sessions, $nextIndex),
                array_slice($sessions, 0, $nextIndex)
            );

            $hp = preg_replace('/^(+62|0)/', '62', trim($phone));
            $maxRetries = 3;
            $attempts = 0;
            $success = false;

            foreach ($rotatedSessions as $session) {
                if ($attempts >= $maxRetries) break;
                $attempts++;

                try {
                    $send = Http::timeout(5)->post("$gatewayUrl/$session/send", [
                        'number' => $hp,
                        'message' => $message,
                    ]);

                    $result = $send->json();

                    if (isset($result['status']) && $result['status'] === 'sent') {
                        Cache::put('wa_last_session', $session, now()->addMinutes(10));
                        Log::info("WA terkirim via session: $session");

                        // Simpan log ke DB
                        DB::table('walogs')->insert([
                            'session' => $session,
                            'number' => $hp,
                            'message' => $message,
                            'status' => 'sent',
                            'created_at' => now(),
                        ]);

                        return "Terkirim via session: $session";
                    } else {
                        Log::warning("WA session $session gagal kirim: " . json_encode($result));
                        DB::table('walogs')->insert([
                            'session' => $session,
                            'number' => $hp,
                            'message' => $message,
                            'status' => $result['status'] ?? 'failed',
                            'error' => $result['message'] ?? 'Unknown error',
                            'created_at' => now(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("WA session $session error: " . $e->getMessage());
                    DB::table('walogs')->insert([
                        'session' => $session,
                        'number' => $hp,
                        'message' => $message,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'created_at' => now(),
                    ]);
                }
            }

            return 'Semua session gagal mengirim pesan setelah ' . $attempts . ' percobaan';
        } catch (\Exception $e) {
            Log::error("WA Gateway Error: " . $e->getMessage());
            return "Error mengirim pesan: " . $e->getMessage();
        }
    }
}
