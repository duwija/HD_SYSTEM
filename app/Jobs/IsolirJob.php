<?php

namespace App\Jobs;
use Illuminate\Support\Facades\DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;


class IsolirJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $id;
    protected $status;
    public function __construct($id, $status)
    {
        //
     $this->id = $id;

     $this->status = $status;

 }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       // $phone = '6281805360534';
        //$msg = ' ini test aja';
      //  $result=\App\Suminvoice::wa_payment($phone, $msg);
        $customers = \App\Customer::Where('id',$this->id)->first();
        $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
        $oldStatus =$customers->status_name->name;

        DB::beginTransaction();

        try {
            \App\Customer::where('id', $this->id)->update([
                'id_status' => 4,
            ]);

            // Ambil nama customer untuk log
            //$customerName = $customer->name ?? "Unknown";

        // Perubahan status
            $changes = [
                'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Blocked',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy = 'System Job';

        // File log untuk customer
        $logFile = "customers/customer_{$this->id}.log";

        // Membuat log message
        $logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

        // Simpan log ke files
       // Storage::append($logFile, $logMessage);

        


        \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);


        \Log::channel('isolir')->info('Set Customer :'.$customers->customer_id. ' | ' .$customers->name." |".$logMessage); 
        DB::commit();

    } catch (\Exception $e) {
        DB::rollback();
        \Log::channel('isolir')->info('Set Customer :'.$customers->customer_id. ' | ' .$customers->name." to Rollback | Canceled Blocking WARNING !!!  ". $e->getMessage()); 


    }









}
}
