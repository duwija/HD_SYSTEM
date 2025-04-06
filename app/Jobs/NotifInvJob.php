<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifInvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $phone;
    protected $name;
    protected $cid;
    protected $encryptedurl;


    public function __construct($phone, $name, $cid, $encryptedurl)
    {
        //
        $this->phone = $phone;
        $this->name = $name;
        $this->cid = $cid;
        $this->encryptedurl = $encryptedurl;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //


        $response = qontak_whatsapp_helper_job_remainder_inv(
            $this->phone,
            $this->name,
            $this->cid,
            $this->encryptedurl
        );
        \Log::channel('notif')->info('Sent Remainder message to  CID '.$this->cid. ' | ' .$this->name . ' | '. $response); 
       // $result=\App\Suminvoice::wa_payment($this->phone, $this->message);
    }
}
