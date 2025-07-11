<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Log;
use App\Customer;
use App\Distrouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use App\Helpers\WaGatewayHelper;
use Symfony\Component\Process\Exception\ProcessFailedException;

class EnableMikrotikJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }

    public function handle()
    {
        $customer = Customer::withTrashed()->find($this->customerId);
        if (!$customer) {
            \Log::error("Customer not found with ID {$this->customerId}");
            return;
        }

        $distrouter = Distrouter::withTrashed()->find($customer->id_distrouter);
        if (!$distrouter) {
            \Log::error("Distrouter not found with ID {$customer->id_distrouter}");
            return;
        }

        $maxRetries = 5;
        $success = false;

        for ($i = 1; $i <= $maxRetries; $i++) {
            try {
                Distrouter::mikrotik_enable(
                    $distrouter->ip,
                    $distrouter->user,
                    $distrouter->password,
                    $distrouter->port,
                    $customer->pppoe
                );
                $success = true;
                break;
            } catch (\Exception $e) {
                \Log::warning("Enable Mikrotik failed (try $i) for Customer ID {$customer->id}: " . $e->getMessage());
                sleep(60); // Tunggu 5 detik sebelum mencoba lagi
            }
        }

        if ($success) {
            // Update status customer jadi aktif normal
            $customer->id_status = 2;
            $customer->save();

            // Kirim notifikasi sukses ke Telegram
            // $this->sendTelegramNotification(
            //     "✅ Enable Mikrotik berhasil untuk Customer ID {$customer->id} ({$customer->name})."
            // );
        } else {
            \Log::error("Enable Mikrotik failed permanently for Customer ID {$customer->id}");

            // (Optional) Update customer status kalau mau tandai error khusus
            // $customer->id_status = 99; 
            // $customer->save();
            Log::channel('payment')->error( "❌ Enable PPPOE Mikrotik GAGAL untuk Customer ID {$customer->id} ({$customer->name}) setelah {$maxRetries} percobaan, silahakan info ke NOC untuk melakuakn Action MANUAL.");
            // Kirim notifikasi gagal ke Telegram
            // $this->sendTelegramNotification(
            //     "❌ Enable PPPOE Mikrotik GAGAL untuk Customer ID {$customer->id} ({$customer->name}) setelah {$maxRetries} percobaan, silahakan info ke NOC untuk melakuakn Action MANUAL."
            // );
            $message =  "❌ Enable PPPOE Mikrotik GAGAL untuk Customer ID {$customer->id} ({$customer->name}) setelah {$maxRetries} percobaan, silahkan info ke NOC untuk melakuakn Action MANUAL.";

            $msgresult = WaGatewayHelper::wa_payment( env("WA_GROUP_PAYMENT"), $message);
        }
    }

    protected function sendTelegramNotification($message)
    {
        $process = new Process([
            "python3",
            env("PHYTON_DIR") . "telegram_send_to_group.py",
            env("TELEGRAM_GROUP_PAYMENT"),
            $message
        ]);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            \Log::info("Telegram notification sent: " . $message);
        } catch (\Exception $e) {
            \Log::error("Failed to send Telegram notification: " . $e->getMessage());
        }
    }
}
