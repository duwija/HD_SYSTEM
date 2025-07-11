<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;
use Exception; 
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class XenditCallbackController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
      // $data=response()->json($request);
      $id_xendit= $request->id;
      if ($request->status == 'PAID')

      {

        try{

           $query = \App\Suminvoice::where('payment_id', $id_xendit)
           ->update([
            'recieve_payment' => $request->paid_amount,
            'payment_point' => $request->bank_code,
            'note' => $request->payment_method,
            'updated_by' => $request->merchant_name,
            'payment_status' =>1,
            'payment_date' =>now()->toDateTimeString(),


        ]);
           if($query)
           {

            $invoice =\App\Suminvoice::where('payment_id', $id_xendit)->first();
            
            $customers = \App\Customer::Where('id',$invoice->id_customer)->first();
            
            $message ="Yth. ".$customers->name." ";
            $message .="\n";
            $message .="\nTerimakasih, Pembayaran tagihan Customer dengan CID ".$customers->customer_id." sudah kami *TERIMA* ";
            $message .="\nUntuk info lebih lengkap silahkan klik link";
            $message .="\nhttp://".env("DOMAIN_NAME")."/suminvoice/".$invoice->tempcode."/print";

            $message .="\n* Payment System Trikamedia *";

            $msg = \App\Suminvoice::wa_payment($customers->phone,$message);
            
            $notif_group ="Online Payment ";
            $notif_group .="\n";
            $notif_group .="\nPembayaran tagihan dari CID ".$customers->customer_id." ( ".$customers->name." )  Sudah DITERIMA";
            $notif_group .="\nCheck : http://".env("DOMAIN_NAME")."/invoice/".$invoice->tempcode;
            $notif_group .="\n";
            $notif_group .="\n* Payment System Trikamedia *";
            $msg = \App\Suminvoice::wa_payment_g($customers->phone,$notif_group);


            $active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
            ->where ('id_customer', '=', $invoice->id_customer )
            ->count();
            
            if (($customers->id_status ==4 ) AND ($active_invoice <= 0 ))
            {

               $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
               \App\Customer::where('id', $invoice->id_customer)->update([
                'id_status' => 2 ]);
               \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->customer_id);

           }






       }
   }
   catch (Exception $e)
   {
    return $e;
}

}

}



public function update_tripay(Request $request)
{
    $date = now()->toDateTimeString();
    $number = $request->merchant_ref;
    $updatedBy ='TRIPAY' ;
    $msg='';
    $changes = [];
    // 

    \Log::channel('payment')->debug("=== Callback received from Tripay | STATUS : ".$request->status." ===", [
        'merchant_ref' => $number,
        'status' => $request->status,
        'amount_received' => $request->amount_received,
    ]);

    if ($request->status !== 'PAID') {
        return response()->json(['success' => false, 'message' => 'Payment not completed'], 400);
    }

    $cekstatus = \App\Suminvoice::where('number', $number)->first();
    if (!$cekstatus) {
        \Log::channel('payment')->error("Invoice tidak ditemukan: $number");
        return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
    }

    if ($cekstatus->payment_status == 1) {
        // return response()->json(['success' => false, 'message' => 'Invoice already paid'], 400);
        return(json_encode(['success' => true]));
    }

    DB::beginTransaction();
    try {
        \App\Suminvoice::where('number', $number)->update([
            'recieve_payment' => $request->amount_received,
            'payment_point' => '1-10039',
            'note' => $request->payment_method . ' (' . $request->amount_received . '+' . $request->total_fee . '->' . $request->total_amount . ')',
            'updated_by' => 'TRIPAY',
            'payment_status' => 1,
            'payment_date' => now()->toDateTimeString(),
        ]);

        $invoice = \App\Suminvoice::where('number', $number)->first();
        if (!$invoice) {
            throw new \Exception("Invoice tidak ditemukan setelah update");
        }

        $customers = \App\Customer::where('id', $invoice->id_customer)->first();
        if (!$customers) {
            throw new \Exception("Customer tidak ditemukan");
        }

        $oldStatus = $customers->status_name->name ?? 'Unknown';
        $totalamountandfee = $request->amount_received + $request->fee_merchant;
        // qontak_whatsapp_helper_receive_payment_confirmation(
        //     $customers->phone, $customers->name, $number, $customers->customer_id, $totalamountandfee, "/invoice/cst/" . Crypt::encryptString($customers->id)
        // );

        // Kirim Notifikasi Telegram
        // $notif_group = "[ONLINE PAYMENT]\n\n" .
        // "CID: {$customers->customer_id}\n" .
        // "Nama: {$customers->name}\n" .
        // "SUDAH DITERIMA\n" .
        // "Jumlah: Rp " . number_format($request->amount_received, 0, ',', '.') . "\n" .
        // "Oleh: TRIPAY | {$request->payment_method}\n" .
        // "👉 " . url("/suminvoice/" . $invoice->tempcode) . "\n\n" .
        // "Terima kasih\n~ " . env("SIGNATURE") . " ~";

        // $process = new Process([
        //     "python3", env("PHYTON_DIR") . "telegram_send_to_group.py",
        //     env("TELEGRAM_GROUP_PAYMENT"), $notif_group
        // ]);
        // $process->run();

        // if (!$process->isSuccessful()) {
        //     throw new ProcessFailedException($process);
        // }

        // Entri Jurnal Keuangan
        \App\Jurnal::create([
            'date' => $date, 'reff' => $invoice->tempcode . 'receive',
            'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
            'note' => 'Receive Payment ONLINE #' . $number . ' | ' . $customers->customer_id. ' | ' . $customers->name,
            'id_akun' => '1-10039', 'debet' => $request->amount_received, 'contact_id' => $customers->customer_id
        ]);
        \App\Jurnal::create([
            'date' => $date, 'reff' => $invoice->tempcode . 'receive',
            'type' => 'jumum', 'description' => 'Receive Payment #' . $number . ' | ' . $customers->name,
            'note' => 'Receive Payment ONLINE #' . $number . ' | ' . $customers->customer_id. ' | ' . $customers->name,
            'id_akun' => '1-10100', 'kredit' => $request->amount_received, 'contact_id' => $customers->customer_id
        ]);

        // Cek jika tidak ada invoice unpaid, update status customer
        if ($customers->id_status == 4 && \App\Suminvoice::where('payment_status', 0)->where('id_customer', $invoice->id_customer)->count() <= 0) {
            \App\Customer::where('id', $invoice->id_customer)->update(['id_status' => 2]);
            if ($distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first()) {
                \App\Distrouter::mikrotik_enable($distrouter->ip, $distrouter->user, $distrouter->password, $distrouter->port, $customers->pppoe);



                $changes = [
                    'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Active',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy ='TRIPAY' ;
        $msg='Diaktifkan kembali karena tidak ada invoice unpaid.';

    }
}




elseif ($customers->id_status == 4 && \App\Suminvoice::where('payment_status', 0)->where('id_customer', $invoice->id_customer)->count() > 0) {
    // Ambil invoice unpaid dengan due_date terdekat
    $active_invoice = \App\Suminvoice::where('payment_status', '=', '0')
    ->where('id_customer', '=', $invoice->id_customer)
    ->orderBy('due_date', 'asc')
    ->first();

    // Periksa apakah invoice masih dalam batas waktu jatuh tempo
    if ($active_invoice && Carbon::parse($active_invoice->due_date)->greaterThan(Carbon::today())) {
        $distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first();

        \App\Customer::where('id', $invoice->id_customer)->update(['id_status' => 2]);

        \App\Distrouter::mikrotik_enable(
            $distrouter->ip, 
            $distrouter->user, 
            $distrouter->password, 
            $distrouter->port, 
            $customers->pppoe
        );

        // Perubahan status

        $changes = [
            'Status' => [
                'old' => $oldStatus ?? 'Unknown',  // Status lama, misal: Active
                'new' => 'Active',  // Status baru
            ],
        ];

        // Tentukan siapa yang mengubah status (karena ini job, kita anggap "System Job")
        $updatedBy ='TRIPAY ' ;


        // File log untuk customer



        $msg ="Diaktifkan kembali karena invoice unpaid masih dalam masa jatuh tempo.";

    }
}








DB::commit();
$logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

// \App\Customerlog::create([
//     'id_customer' => $customers->id,
//     'date' => now(),
//     'updated_by' => $updatedBy,
//     'topic' => 'payment',
//     'updates' => json_encode($changes),
// ]);
// Log::channel('payment')->info("Pelanggan ID: {$customers->customer_id} diaktifkan kembali karena tidak ada invoice unpaid. |".$logMessage);



if (!empty($changes)) {
    \App\Customerlog::create([
        'id_customer' => $customers->id,
        'date' => now(),
        'updated_by' => $updatedBy,
        'topic' => 'payment',
        'updates' => json_encode($changes),
    ]);

    \Log::channel('payment')->info("[ONLINE PAYMENT ] Pelanggan ID: {$customers->customer_id}  | INV no: ".$number." | ".$msg." |".$logMessage);
}
else
{
    $changes = [];
    \Log::channel('payment')->info("[ONLINE PAYMENT ] Pelanggan ID: {$customers->customer_id}  | INV no: ".$number." | ".$msg." |".$logMessage);
}

return(json_encode(['success' => true]));
} catch (\Exception $e) {
    DB::rollBack();
    \Log::channel('payment')->error("Error in update_tripay: " . $e->getMessage());
    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
}
}


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */





    public function update_winpay(Request $request)
    {
        $date = now()->toDateTimeString();
        $number = $request->merchant_ref;
        $updatedBy ='TRIPAY' ;
        $msg='';
        $changes = [];
    // 

        \Log::channel('payment')->info("=== Callback received from winpay | STATUS : ".$request);
    }


    public function destroy($id)
    {
        //
    }




}
