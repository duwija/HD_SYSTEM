<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;
use \Auth;
Use GuzzleHttp\Clients;
use App\Jobs\NotifInvJob;
use App\Jobs\IsolirJob;
use App\Jobs\CreateInvJob;
use App\User;
use Xendit\Xendit;
use Exception;   
use DB;
use DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
class SuminvoiceController extends Controller
{
   public function __construct()
   {
        //$this->middleware('auth');
    $this->middleware('auth', ['except' => ['print', 'notifinvJob', 'tripay']]); 

}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function createinvmonthlyJob(Request $request)
    {
        $inv_date = $request->inv_date;
        $id_merchant = $request->id_merchant;

    // Query pelanggan dengan status 2 atau 4
        $customer = \App\Customer::where(function ($query) {
            $query->where('customers.id_status', '2')
            ->orWhere('customers.id_status', '4');
        });

    // Jika id_merchant diberikan, tambahkan filter
        if (!empty($id_merchant)) {
            $customer->where('id_merchant', $id_merchant);
        }

    // Eksekusi query untuk mendapatkan data pelanggan
        $customers = $customer->get();

    // Mulai eksekusi job dengan delay
        $start = Carbon::now();
        $count = 0;

        foreach ($customers as $cust) {
            $count++;
            CreateInvJob::dispatch($cust->id, $inv_date)
            ->delay($start->addSeconds(15));
        }

    // Pesan sukses
        $msg = 'Processing create ' . $count . ' Invoice(s)';

        return redirect('suminvoice/notification')->with('info', $msg);
    }




    public function tripay(Request $request)
    {

        $endpoint     = env("TRIPAY_ENDPOINT");
        $apiKey       = env("TRIPAY_APIKEY");
        $privateKey   = env("TRIPAY_PRIVATEKEY");
        $merchantCode = env("TRIPAY_MERCHANTCODE");
        $merchantRef  = $request->number;
        $amount       = round($request->amount);
        $tempcode       = $request->tempcode;
        $return_url = url('suminvoice/' . $tempcode . '/print');
        $hash = (hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey));
        $email = $request->email;
        $customer_id = $request->customer_id;
        $name = $request->name;
        $phone = $request->phone;
        if (empty($phone))
        {
            $phone = '0818000000';
        }
        if (empty($email))
        {
            $email = 'billing@alus.co.id';
        }

        $data = [
            'method'         => $request->method,
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => $name.' | '.$customer_id,
            'customer_email' => $email,
            'customer_phone' => $phone,

            'order_items'    => [
                [
                    'sku'         => $request->description,
                    'name'        => 'Invoice ALUSNET No #'. $merchantRef,
                    'price'       => $amount,
                    'quantity'    => 1,

                ]

            ],
            'return_url'     => $return_url,
    'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
    'signature'    => $hash,

];
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_FRESH_CONNECT  => true,
    CURLOPT_URL            => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
    CURLOPT_FAILONERROR    => false,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($data),
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
]);

$response = curl_exec($curl);
$error = curl_error($curl);
if(curl_errno($curl)){
    echo 'Request Error:' . curl_error($curl);
}
else
    $data = json_decode($response, true);
if(empty($data['data']['reference']))
{
    echo $data['message'];
}
else

{
    $start = Carbon::now();
    $reference = $data['data']['reference'];
    $merchant_ref = $data['data']['merchant_ref'];
    $query = \App\Suminvoice::where('number', $merchant_ref)
    ->update([
        'payment_id' =>$reference ,
    ]);

    $payment_name = $data['data']['payment_name'];
// $tagihan = $data['data']['amount_received'];
// $admin_fee = $data['data']['total_fee'];
// $total = $data['data']['amount'];


    $expired_time = date('Y-m-d H:i:s', $data['data']['expired_time']);

    // $message = "*🔔 Informasi Pembayaran*";
    // $message .= "\n\nHalo, *" . $name . "*";
    // $message .= "\nCustomer ID / CID: *" . $customer_id . "*";
    // $message .= "\n\nTerima kasih telah memilih metode pembayaran *" . $payment_name . "*.";
    // $message .= "\n\n💳 *Tagihan No:* #" . $merchant_ref;
    // $message .= "\n⏳ *Batas Pembayaran:* " . $expired_time . " WITA";
    // $message .= "\n\nSilakan selesaikan pembayaran sebelum batas waktu agar transaksi dapat diproses dengan lancar.";
    // $message .= "\n\nJika pembayaran melewati batas waktu tersebut, harap buka kembali halaman tagihan untuk mendapatkan kode pembayaran terbaru.";
    // $message .= "\n\n📌 *Panduan pembayaran & informasi lebih lanjut:*";
    // $message .= "\n👉 " . url("suminvoice/" . $tempcode . "/print");
    // $message .= "\n\nTerima kasih atas kepercayaan Anda! 😊";
    // $message .= "\n\nSalam,";
    // $message .= "\n*" . env("SIGNATURE") . "*";

//Disable WA
    // NotifInvJob::dispatch($request->phone, $message)->delay($start->addSeconds(5));
    return redirect ('https://tripay.co.id/checkout/'.$reference);
}

curl_close($curl);




}



public function customerblockednotifJob()
{


    $phone = '081805360534';
    \Log::channel('notif')->info('==== START Notification to customers with Blocked Status. ===');
    $customer=\App\Customer::where("customers.id_status", "=", 4)              
    ->get();


    $start = Carbon::now();

    $count =0;
    foreach($customer as $customer) {

      // $encryptedurl = Crypt::encryptString($customer->id);
      // $message  ="*Remainder (Pengingat)!*";
      // $message .="\n";
      // $message .="Yth. ".$customer->name." ";
      // $message .="\n";
      // $message .="\nTerimakasih atas kesetiaan Anda menggunakan layanan".env("SIGNATURE") ;
      // $message .="\n";
      // $message .="\nLayanan Internet Anda saat ini dalam masa ISOLIR (PENGHENTIAN SEMENTARA)";  
      // $message .="\nSilakan segera melakukan pembayaran tagihan untuk mengaktifkan kembali Layanan Internet Anda";
      // $message .="\n";

      // $message .="\nUntuk info tagihan lebih lengkap silahkan klik link berikut";
      // $message .="\nhttp://".env("DOMAIN_NAME")."/invoice/cst/".$encryptedurl;
      // $message .="\n";
      // $message .="\nAbaikan pesan ini jika sudah melakukan pembayaran";
      // $message .="\n";
      // $message .="\n~ ".env("SIGNATURE")." ~";
        $encryptedurl = Crypt::encryptString($customer->id);

        $message = "*🔔 Pengingat Tagihan!*";
        $message .= "\n\nHalo, *" . $customer->name . "*,";
        $message .= "\nTerima kasih telah menggunakan layanan *" . env("SIGNATURE") . "*.";
        $message .= "\n\nKami ingin menginformasikan bahwa layanan Anda saat ini sedang *tidak aktif* karena belum dilakukan pembayaran.";
        $message .= "\n\nSilakan segera menyelesaikan tagihan agar layanan dapat kembali digunakan tanpa kendala.";
        $message .= "\n\n🔗 Cek detail tagihan & pembayaran Anda di sini:";
        $message .= "\n👉 " . url("/invoice/cst/" . $encryptedurl);
        $message .= "\n\nJika sudah melakukan pembayaran, abaikan pesan ini.";
        $message .= "\n\nTerima kasih atas perhatian dan kepercayaan Anda! 😊";
        $message .= "\n\nSalam,";
        $message .= "\n*" . env("SIGNATURE") . "*";



        $count = $count +1;

        NotifInvJob::dispatch($customer->phone, $message)->delay($start->addSeconds(19));
        \Log::channel('notif')->info('Add to Job Blocked Remainder CID '.$customer->customer_id. ' | ' .$customer->name); 


    }




    $msg = 'Processing Sent '. $count .' messages';

    return redirect ('suminvoice/notification')->with('info',$msg);
}

public function isolirData(Request $request)
{
 // Mengambil parameter 'isolirdate' dari request
    $isolirdate = (int)$request->query('isolirdate');

    $customer_count = \App\Customer::leftJoin('suminvoices', 'suminvoices.id_customer', '=', 'customers.id')
    ->where('suminvoices.payment_status', '=', 0)
    ->where(function ($query) use ($isolirdate) {
        $query->where('customers.id_status', '=', 2)
        ->where('customers.isolir_date', '=', $isolirdate);
    })
    ->distinct('customers.id') // Menambahkan DISTINCT berdasarkan customers.id
    ->count('customers.id'); // Menghitung jumlah pelanggan dengan DISTINCT

    if ($customer_count >0 )
    {

            // Mengembalikan hasil dalam format JSON
        return response()->json([
            'message' => 'ready to be blocked on date :  '.$isolirdate ,
            'customercount' => $customer_count.' Customer'
        ]);

    }
    else
    {
        return response()->json([
            'message' => 'ready to be blocked on date :  '.$isolirdate ,
            'customercount' => 'No Customer' 
        ]);
    }
}



public function getSelectedcustomermerchant(Request $request)
{
    // Ambil parameter 'id_merchant' dan 'inv_date' dari request
    $id_merchant = $request->id_merchant;
    $inv_date = $request->inv_date; // Format: YYYY-MM-DD

    // Konversi $inv_date ke format bulantahun (MMYYYY)
    $periode = Carbon::parse($inv_date)->format('mY'); 

    // Query customers: Jika id_merchant NULL, ambil semua customers
    $query = \App\Customer::whereIn('id_status', [2, 4]);
    
    if (!empty($id_merchant)) {
        $query->where('id_merchant',$id_merchant);
    }

    $customers = $query->get(['id']); // Ambil hanya kolom id

    // Total pelanggan yang memenuhi syarat
    $customer_count_all = $customers->count();

    // Ambil daftar ID pelanggan
    $customer_ids = $customers->pluck('id')->toArray();

    // Jika tidak ada customer yang ditemukan, langsung return 0
    if (empty($customer_ids)) {
        return response()->json([
          'customercount' => "0 of $customer_count_all"
      ]);
    }

    // Hitung jumlah customer yang memiliki invoice dengan monthly_fee = 1 dan periode = 'MMYYYY'
    $customer_count = \App\Invoice::whereIn('id_customer', $customer_ids)
    ->where('monthly_fee', 1)
    ->where('periode', $periode)
    ->count();
    $month = Carbon::parse($inv_date)->translatedFormat('F Y');
    $result =$customer_count_all-$customer_count;
    return response()->json([
        'customercount' => " $result of $customer_count_all",
        'month' => "in $month"
    ]);
}

public function getSelectedblocknotif(Request $request)
{
    // Ambil parameter 'id_merchant' 
    $id_merchant = $request->id_merchant_block;

    // Query customers yang statusnya 4 (diblokir)
    $query = \App\Customer::where('id_status', 4);
    
    if (!empty($id_merchant)) {
        $query->where('id_merchant', $id_merchant);
    }

    $blocked_customer = $query->count(); // Hitung jumlah pelanggan yang diblokir

    // Query semua customers (tanpa filter status)
    $query_all = \App\Customer::query();
    
    if (!empty($id_merchant)) {
        $query_all->where('id_merchant', $id_merchant);
    }

    $customer_count_all = $query_all->count(); // Hitung total pelanggan

    return response()->json([
        'customercount' => "$blocked_customer of $customer_count_all <p><h4> Customers </h4></p>",
    ]);
}

public function getSelectedunpaidnotif(Request $request)
{
    // Ambil parameter 'id_merchant'
    $id_merchant = $request->id_merchant_unpaid;

    // Query semua customers (tanpa filter status)
    $query_all = \App\Customer::query();

    if (!empty($id_merchant)) {
        $query_all->where('id_merchant', $id_merchant);
    }

    $customer_count_all = $query_all->count(); // Hitung total pelanggan

    // Query pelanggan yang memiliki invoice dengan status unpaid (payment_status = 0)
    $custactiveinv = \App\Customer::select(
        'customers.id', 'customers.customer_id', 'customers.name',
        'customers.phone', 'customers.address', 'customers.billing_start',
        'customers.id_plan', 'customers.tax', 'customers.id_status',
        'suminvoices.payment_status', 'customers.deleted_at'
    )
    ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
    ->where("suminvoices.payment_status", 0)
    ->where(function ($query) {
        $query->where("customers.id_status", 2)
        ->orWhere("customers.id_status", 4);
    });

    if (!empty($id_merchant)) {
        $custactiveinv->where('customers.id_merchant', $id_merchant);
    }

    $custactiveinv_count = $custactiveinv->groupBy('customers.id')->get()->count();

    return response()->json([
        'customercount' => "$custactiveinv_count of $customer_count_all <p><h4> Customers </h4></p>",
    ]);
}





public function customerisolirJob(Request $request)
{

    $isolirdate = (int)$request->isolir_date;

       //dd($isolirdate);

   //   $customer = \App\Customer::select ('customers.id','customers.customer_id','customers.name', 'customers.phone','customers.id_status','customers.isolir_date', 'suminvoices.payment_status', 'customers.deleted_at')
   //   ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
   //   ->where("suminvoices.payment_status", "=", 0)

   //   ->where(function ($query) {
   //     $query ->where("customers.id_status", "=", 2)
   //     ->Where("customers.isolir_date", "=", $isolirdate);
   // })

   //   ->groupBy('customers.id')
   //   ->get();




 // Fetch customers based on conditions
    $customer = \App\Customer::select('customers.id', 'customers.customer_id', 'customers.name', 'customers.phone', 'customers.id_status', 'customers.isolir_date', 'suminvoices.payment_status', 'customers.deleted_at')
    ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
    ->where("suminvoices.payment_status", "=", 0)
    ->where(function ($query) use ($isolirdate) {
        $query->where("customers.id_status", "=", 2)
        ->where("customers.isolir_date", "=", $isolirdate);
    })
    ->groupBy('customers.id')
    ->get();



    $start = Carbon::now();

    $count =0;
    foreach($customer as $customer) {




     $count = $count +1;


     IsolirJob::dispatch($customer->id, $customer->id_status)->delay($start->addSeconds(2));
     \Log::channel('isolir')->info('Set Customer :'.$customer->customer_id. ' | ' .$customer->name." to Blocked | Isolir"); 



 }


 $msg = 'Processing Sent '. $count .' messages';

 return redirect ('suminvoice/notification')->with('info',$msg);


}


public function notifinvJob(Request $request)
{

$id_merchant = $request->id_merchant_unpaid; // Ambil ID Merchant dari request

\Log::channel('notif')->info('==== START Notification to customers who still have invoice. ==='); 

$customers = \App\Customer::select(
    'customers.id', 'customers.customer_id', 'customers.name', 
    'customers.phone', 'customers.address', 'customers.billing_start',
    'customers.id_plan', 'customers.tax', 'customers.id_status',
    'suminvoices.payment_status', 'customers.deleted_at'
)
->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
->where("suminvoices.payment_status", 0)
->where(function ($query) {
    $query->where("customers.id_status", 2)
    ->orWhere("customers.id_status", 4);
});

// Tambahkan filter berdasarkan ID Merchant jika ada
if (!empty($id_merchant)) {
    $customers->where('customers.id_merchant', $id_merchant);
}

$customers = $customers->groupBy('customers.id')->get();

// Tambahkan log untuk melihat hasilnya
\Log::channel('notif')->info('Total customers found: ' . $customers->count());





$start = Carbon::now();

$count =0;
foreach($customers as $customer) {


    $encryptedurl = '/invoice/cst/'.Crypt::encryptString($customer->id);

    // $message = "*🔔 Pengingat Tagihan!*";
    // $message .= "\n\nHalo, *" . $customer->name . "*,";
    // $message .= "\nKami ingin mengingatkan bahwa tagihan Anda sudah tersedia.";
    // $message .= "\n\n💡 Agar tetap menikmati layanan tanpa gangguan, mohon untuk menyelesaikan pembayaran tepat waktu.";
    // $message .= "\n\n🔗 Lihat detail tagihan Anda di sini:";
    // $message .= "\n👉 " . url("/invoice/cst/" . $encryptedurl);
    // $message .= "\n\nJika sudah melakukan pembayaran, abaikan pesan ini.";
    // $message .= "\n\nTerima kasih atas kepercayaan Anda. Semoga hari Anda menyenangkan! 😊";
    // $message .= "\n\nSalam,";
    // $message .= "\n*" . env("SIGNATURE") . "*";


    $count = $count +1;

    NotifInvJob::dispatch($customer->phone, $customer->name, $customer->customer_id, $encryptedurl )->delay($start->addSeconds(5));
    // \Log::channel('notif')->info('Add to Job Invoice Remainder CID '.$customer->customer_id. ' | ' .$customer->name); 


}

 //NotifInv::dispatch($phone, $message);
$msg = 'Processing Sent '. $count .' messages';

return redirect ('suminvoice/notification')->with('info',$msg);

     //return 'Processing Send'. $count .' message';
}


public function index()
{
        //
   $suminvoice = \App\Suminvoice::orderBy('id', 'DESC')
   ->where('payment_status','=', '0')
   ->get();


   return view ('suminvoice/index',['suminvoice' =>$suminvoice]);
}
public function transaction()
{
        //
 $today = Carbon::today();

 $startOfWeek = Carbon::now()->startOfWeek();
 $endOfWeek = Carbon::now()->endOfWeek();
 $startOfMonth = Carbon::now()->startOfMonth();
 $endOfMonth = Carbon::now()->endOfMonth();
 $sixMonthsAgo = Carbon::today()->subMonths(6);
 $groupedTransactionsUser = \App\Suminvoice::whereBetween('payment_date', [$startOfMonth, $today->addday()])
 ->groupBy('updated_by')
 ->get();


 $user= \App\User::pluck('name', 'id');
 $totalPaymentToday = \App\Suminvoice::whereDate('payment_date', Carbon::today())
 ->sum('recieve_payment');
 $totalTransactionThisWeek = \App\Suminvoice::whereBetween('payment_date', [$startOfWeek, $endOfWeek])
 ->sum('recieve_payment');
    // Menghitung total transaksi bulan ini
 $totalTransactionThisMonth = \App\Suminvoice::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
 ->sum('recieve_payment');
 $totalReceivable = \App\Suminvoice::where('payment_status', 0)
 ->sum('total_amount');
 $groupedTransactions = \App\Suminvoice::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
 ->select('updated_by', DB::raw('SUM(recieve_payment) as total_amount'))
 ->groupBy('updated_by')
 ->get();

 $suminvoice = \App\Suminvoice::orderBy('updated_at', 'DESC')
 ->whereNotNull('updated_by')
 ->whereBetween('payment_date',[(date('Y-m-d', strtotime("-1 week"))), (date('Y-m-d'))])
 ->get();
 $merchant = \App\Merchant::pluck('name', 'id');
 $parentAkuns = \App\Akun::whereNotNull('parent')->pluck('parent');

 $kasbank = \App\Akun::where('category', 'kas & bank')
 ->whereNotIn('akun_code', $parentAkuns)
 ->get();

 return view ('suminvoice/transaction',['suminvoice' =>$suminvoice, 'user'=>$groupedTransactionsUser, 'totalPaymentToday'=>$totalPaymentToday, 'totalTransactionThisWeek'=>$totalTransactionThisWeek, 'totalTransactionThisMonth'=>$totalTransactionThisMonth, 'totalReceivable'=>$totalReceivable, 'groupedTransactions' => $groupedTransactions,'merchant'=>$merchant, 'kasbank'=>$kasbank]);
}
//======================================================================================

public function table_transaction_list(Request $request){


        // if (empty($request->filter))
        // {
    $dateStart = Carbon::createFromFormat('Y-m-d', $request->input('dateStart'))->setTime(0, 0); 
    $dateEnd =  Carbon::createFromFormat('Y-m-d', $request->input('dateEnd'))->endOfDay();
    $parameter = $request->input('parameter');
    $updatedBy = $request->input('updatedBy');
    $id_merchant = $request->input('id_merchant');
    $kasbank = $request->input('kasbank');
    $today = Carbon::today();
    $sixMonthsAgo = Carbon::today()->subMonths(6);
    $groupedTransactionsUser = \App\Suminvoice::whereBetween('payment_date', [$dateStart, $dateEnd])
    ->where('payment_status', 1)
    ->select(
        'suminvoices.updated_by',
        DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
        DB::raw('SUM(suminvoices.total_amount) as total_amount')
    )
    ->join('customers', 'customers.id', '=', 'suminvoices.id_customer'); // Adjust foreign key

    if (!empty($updatedBy)) {
        $groupedTransactionsUser->where('suminvoices.updated_by', $updatedBy);
    }

    if (!empty($id_merchant)) {
        $groupedTransactionsUser->where('customers.id_merchant', $id_merchant);
    }
    if (!empty($kasbank)) {
        $groupedTransactionsUser->where('suminvoices.payment_point', $kasbank);
    }

    $groupedTransactionsUser = $groupedTransactionsUser
    ->groupBy('suminvoices.updated_by')
    ->with('user')
    ->get();

    $groupedTransactionsUser->transform(function ($transaction) {
    // Mengecek apakah updated_by adalah bilangan numerik
        if (is_numeric($transaction->updated_by)) {
            $transaction->updated_by = $transaction->user ? $transaction->user->name : null;
        } else {
        $transaction->updated_by = $transaction->updated_by; // Biarkan seperti data awal
    }
    $payment_fee = $transaction->total_payment - $transaction->total_amount;

    // Mengubah total_amount menjadi format currency
    $transaction->total_amount = $transaction->total_amount; // Format: 1.234,56
    $transaction->payment_fee = $payment_fee;
    return $transaction;




});

    $groupedTransactionsMerchant = \App\Suminvoice::whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
    ->where('suminvoices.payment_status', 1)
    ->join('customers', 'suminvoices.id_customer', '=', 'customers.id')
    ->select(
        'customers.id_merchant',
        DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
        DB::raw('SUM(suminvoices.total_amount) as total_amount')
    );

    if (!empty($updatedBy)) {
        $groupedTransactionsMerchant->where('suminvoices.updated_by', $updatedBy);
    }

    if (!empty($id_merchant)) {
        $groupedTransactionsMerchant->where('customers.id_merchant', $id_merchant);
    }
    if (!empty($kasbank)) {
        $groupedTransactionsMerchant->where('suminvoices.payment_point', $kasbank);
    }

    $groupedTransactionsMerchant = $groupedTransactionsMerchant
    ->groupBy('customers.id_merchant')
    ->get();

   // Assuming you have the Merchant model defined
    $merchant = \App\Merchant::orderby('id','DESC')
    ->get();

    $kasbanks = \App\Akun::orderby('akun_code','DESC')
    ->get();




    $groupedTransactionsKasbank = \App\Suminvoice::whereBetween('suminvoices.payment_date', [$dateStart, $dateEnd])
    ->where('suminvoices.payment_status', 1)
    ->join('akuns', 'suminvoices.payment_point', '=', 'akuns.akun_code') // Join dengan tabel akuns
    ->select(
        'suminvoices.payment_point',
        'akuns.name as akun_name',
        DB::raw('COUNT(suminvoices.id) as total_transactions'), // Menghitung jumlah transaksi
        DB::raw('SUM(suminvoices.recieve_payment) as total_payment'),
        DB::raw('SUM(suminvoices.total_amount) as total_amount')
    );

    if (!empty($updatedBy)) {
        $groupedTransactionsKasbank->where('suminvoices.updated_by', $updatedBy);
    }

    if (!empty($id_merchant)) {
        $groupedTransactionsKasbank->join('customers', 'suminvoices.id_customer', '=', 'customers.id')
        ->where('customers.id_merchant', $id_merchant);
    }

    if (!empty($kasbank)) {
        $groupedTransactionsKasbank->where('suminvoices.payment_point', $kasbank);
    }

    $groupedTransactionsKasbank = $groupedTransactionsKasbank
    ->groupBy('suminvoices.payment_point', 'akuns.name') // Kelompokkan berdasarkan payment_point
    ->get();

// Now $groupedTransactionsMerchant has merchant_name property

  // return $groupedTransactionsMerchant;



// Buat query dengan filter yang diperlukan
    $suminvoice = \App\Suminvoice::orderBy('payment_date', 'DESC')
    ->leftJoin('customers', 'suminvoices.id_customer', '=', 'customers.id')
    ->select('suminvoices.*', 'customers.name','customers.customer_id','customers.id_merchant'); // Add any other fields you need from customers

    if (!empty($dateStart) && !empty($dateEnd)) {
        $suminvoice->whereBetween('payment_date', [$dateStart, $dateEnd]);
    }

    if (!empty($parameter)) {
        $suminvoice->where(function($query) use ($parameter) {
            $query->where('customers.name', 'like', "%$parameter%")
            ->orWhere('customers.customer_id', 'like', "%$parameter%")
            ->orWhere('suminvoices.number', 'like', "%$parameter%"); 
        });
    }

    if (!empty($updatedBy)) {
        $suminvoice->where('suminvoices.updated_by', $updatedBy);
    }
    if (!empty($id_merchant)) {
        $suminvoice->where('customers.id_merchant', $id_merchant);
    }
    if (!empty($kasbank)) {
        $suminvoice->where('suminvoices.payment_point', $kasbank);
    }

    $suminvoice->where('suminvoices.payment_status', 1);
    // $suminvoice->orderby('updated_at', 'DESC');

    $results = $suminvoice->get();
    $sql = $suminvoice->toSql();


    $suminvoiceData = $suminvoice->get();

    $total = $suminvoiceData->sum('total_amount');
    $recieve_payment = $suminvoiceData->sum('recieve_payment');
    $fee_counter = $recieve_payment-$total;


    return DataTables::of($suminvoice)
    ->addIndexColumn()
    ->editColumn('number',function($suminvoice)
    {

        return ' <a href="/suminvoice/'.$suminvoice->tempcode.'" title="INV Number" class="badge badge-primary text-center  "> '.$suminvoice->number. '</a>';
    })

    ->addColumn('cid',function($suminvoice)
    {

     $status = $suminvoice->customer->id_status;
     if ( $status == 2)
      $badge_sts = "badge-success";
  elseif ( $status == 3)
      $badge_sts = "badge-secondary";
  elseif ( $status== 4)
      $badge_sts = "badge-danger";
  elseif ( $status== 5)
      $badge_sts = "badge-primary";
  else
      $badge_sts = "badge-warning";




  return '<a class="badge '.$badge_sts .'" href="/customer/'.$suminvoice->customer->id.'">'.$suminvoice->customer->customer_id .' </a>';

})
    ->addColumn('period', function ($suminvoice) {
        $invoices = \App\Invoice::where('tempcode', $suminvoice->tempcode)->get();

    $periods = []; // Array untuk menampung semua periode
    
    foreach ($invoices as $invoice) {
        if ($invoice->monthly_fee == 1) {
            $type = "M";
        } else {
            $type = "G";
        }

        $periods[] = '<a>' . $type . " " . $invoice->periode . '</a>';
    }

    return implode("<br>", $periods); // Menggabungkan hasil dengan koma
})

    ->addColumn('name',function($suminvoice)
    {

        return '<a>'.$suminvoice->customer->name. '</a>';
    })

    ->addColumn('merchant', function($suminvoice) {
    // Check if customer relationship exists
        if ($suminvoice->customer) {
        // Check if merchant_name relationship exists
            if ($suminvoice->customer->merchant_name) {
                return $suminvoice->customer->merchant_name->name ;
            } else {
            return 'No Merchant'; // Or any default value you want
        }
    } else {
        return 'No Customer'; // Or any default value you want
    }
})
    ->addColumn('address',function($suminvoice)
    {

        return '<a>'.$suminvoice->customer->address. '</a>';
    })
    ->editColumn('total_amount',function($suminvoice)
    {

        return '<a>'.number_format($suminvoice->total_amount, 2, '.', ','). '</a>';
    })
    ->addColumn('payment_fee',function($suminvoice)
    {
        $payment_fee = $suminvoice->recieve_payment -$suminvoice->total_amount;
        return '<a>'.number_format($payment_fee, 2, '.', ','). '</a>';
    })
    ->addColumn('status',function($suminvoice)
    {
        if ($suminvoice->payment_status == 1)
          { $badge_sts = "badge-success";
      $status = "PAID";}
      elseif ($suminvoice->payment_status == 2 )
          { $badge_sts = "badge-secondary";
      $status = "CANCEL";}
      elseif ($suminvoice->payment_status == 0)
          {$badge_sts = "badge-danger";
      $status = "UNPAID";}
      else
          {$badge_sts = "badge-warning";
      $status = "UNKNOW";}
      return '<a class="badge '.$badge_sts .'">'.$status.' </a>';
  })
    ->addColumn('updated_by', function($suminvoice) {
        if (is_numeric($suminvoice->updated_by)) {
            return '<a>' . $suminvoice->user->name . '</a>';
        } else {
            return '<a>' . $suminvoice->updated_by . '</a>';
        }
    })
    ->addColumn('kasbank', function($suminvoice) {

      return '<a>'.($suminvoice->kasbank ? $suminvoice->kasbank->name : '-').'</a>';
  })

    ->addColumn('payment_date', function($suminvoice) {

        return '<a>'.$suminvoice->payment_date.'</a>';
    })



   // ->rawColumns(['DT_RowIndex','date','number'])

    ->rawColumns(['DT_RowIndex','date','number','cid','name','address','note','total_amount','payment_fee','status','updated_by','kasbank','payment_date','merchant','period' ])
    ->with('total', $total)
    ->with('fee_counter', $fee_counter)
    ->with('groupedTransactionsUser', $groupedTransactionsUser)
    ->with('groupedTransactionsMerchant', $groupedTransactionsMerchant)
    ->with('groupedTransactionsKasbank', $groupedTransactionsKasbank)
    ->with('merchants', $merchant)
    ->with('kasbanks', $kasbanks)
    ->make(true);
}


// public function searchtransaction(Request $request)
// {
//         //
//         //dd ($request);
//     $date_from = ($request['date_from']);
//     $date_end = ($request['date_end']);
//     $updated_by = ($request['updated_by']);
//     $user= \App\User::pluck('name', 'id');

//     if (empty($updated_by))
//     {


//      $suminvoice = \App\Suminvoice::orderBy('updated_at', 'ASC')
//      ->whereNotNull('updated_by')
//      ->whereBetween('payment_date',[($request['date_from']), ($request['date_end'])])
//      ->get();

//  }
//  else
//  {

//     $suminvoice = \App\Suminvoice::orderBy('payment_date', 'ASC')
//     ->whereNotNull('updated_by')
//     ->whereBetween('payment_date',[($request['date_from']), ($request['date_end'])])
//     ->where('updated_by', 'LIKE', "%".$updated_by."%") 
//     ->get();

// }


// return view ('suminvoice/transaction',['suminvoice' =>$suminvoice, 'user' =>$user, 'date_from'=>$date_from, 'date_end'=>$date_end ]);
// }
public function mytransaction()
{
        //
   $suminvoice = \App\Suminvoice::orderBy('updated_at', 'ASC')
   ->where('updated_by','=',  \Auth::user()->id)

   ->whereBetween('payment_date',[(date('Y-m-1')), (date('Y-m-d'))])
   ->get();


   return view ('suminvoice/mytransaction',['suminvoice' =>$suminvoice]);
}
public function searchmytransaction(Request $request)
{
        //
    $date_from = ($request['date_from']);
    $date_end = ($request['date_end']);
        // $customer_id = ($request['customer_id']);


    $suminvoice = \App\Suminvoice::orderBy('payment_date', 'ASC')
        //->whereNotNull('updated_by')
    ->where('updated_by','=',  \Auth::user()->id)
        // ->where('customer_id',[($request['customer_id'])])
    ->whereBetween('payment_date',[($request['date_from']), ($request['date_end'])])
    ->get();
    


        //dd ($suminvoice);
    return view ('suminvoice/mytransaction',['suminvoice' =>$suminvoice,'date_from'=>$date_from, 'date_end'=>$date_end ]);
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

    public function xendit(Request $request)
    {

      if($request->header("X-CALLBACK-TOKEN") == "myoOCdWvUWsXWfmffsOy0DpfepvwNg6K1Bxw02uXKK4UuRYX"){
        return ($request);
    }
    return response()->json($request);
}
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)

    {
        $msg="";  
        //$array="";  
        $tempcode=sha1(time().rand());
        $id = $request->invoice_item;
        $latest_number=uniqid();
        $customers = \App\Customer::Where('id',$request['id_customer'])->withTrashed()->first();

        $email = !empty($customer->email) ? $customer->email : "return@alus.co.id";

   // try
   // { 
        $tax = $customer->tax ?? 0;

        $date=date("Y-m-d");


        DB::beginTransaction();

        try{
            // Step 1 : Set current invoice item to parent Suminvoice
            \App\Invoice::whereIn('id', $id)->update([
                'payment_status' => 3,
                'tempcode' => $tempcode,
            ]);


            //Step 2 : Create Suminvoice

            \App\Suminvoice::create([
                'id_customer' => ($request['id_customer']),
                'number' => $latest_number,
                'date' => $date, 
                'payment_status' => 0,
                'tax' => ($request['tax']),
                'total_amount' =>($request['subtotal']+ $request['tax_total']),
                'payment_id' => 'empty',
                'tempcode' => $tempcode,
                'due_date' => $request->due_date,


            ]);

 //Step 3: Sent Message



            $data = [
                'tax_total' => $request['tax_total'],
                'date' => $date,
                'reff' => $tempcode,
                'type' => 'jumum',
                'description' => 'Invoice #'.$latest_number,
                'note' => 'Invoice #'.$latest_number.' | '.$customers->customer_id .' | '.$customers->name,
            ];

// Create debit entry
            $data['id_akun'] = '1-10100';
            $data['debet'] = $request['subtotal']+ $request['tax_total'];
            \App\Jurnal::create($data);
unset($data['debet']); // Remove debet key for the credit entry

// Create credit entry
$data['id_akun'] = '4-40000';
$data['kredit'] = $request['subtotal'];
\App\Jurnal::create($data);

if (!empty($request['tax']) && $request['tax'] != 0) {
    $data['id_akun'] = '2-20500';
    $data['kredit'] = $request['tax_total'];
    \App\Jurnal::create($data);
}




// $message ="Yth. ".$customers->name." ";
// $message .="\n";
// $message .="\nTagihan Customer dengan CID *".$customers->customer_id."* sudah kami Terbitkan";
// $message .="\n";
// $message .="\nNo Tagihan : *#".$latest_number."*";
// $message .="\nTotal Tagihan : *Rp.".$request["total"]."*";
// $message .="\nJatuh Tempo :  *". $request->due_date."*";
// $message .="\n";
// $message .="\nUntuk info lebih lengkap silahkan klik link berikut";
// $message .="\nhttp://".env("DOMAIN_NAME")."/suminvoice/".$tempcode."/print";
// $message .="\n";
// $message .="\nAbaikan pesan ini jika sudah melakukan pembayaran";
// $message .="\n";
// $message .="\n~ ".env("SIGNATURE")." ~";

// $message = "Halo *" . $customers->name . "*,";
// $message .= "\n\nTagihan dengan CID *" . $customers->customer_id . "* telah diterbitkan.";
// $message .= "\n\n📄 *No. Tagihan*: #" . $latest_number;
// $message .= "\n💰 *Total*: Rp. " . number_format($request["total"], 0, ',', '.');
// $message .= "\n📅 *Jatuh Tempo*: " . $request->due_date;
// $message .= "\n\nUntuk detail lebih lanjut, silakan klik tautan berikut:";
// $message .= "\n👉 " . url("/suminvoice/" . $tempcode . "/print");
// $message .= "\n\nJika sudah melakukan pembayaran, abaikan pesan ini.";
// $message .= "\n\nTerima kasih atas perhatian Anda.";
// $message .= "\n~ *" . env("SIGNATURE") . "* ~";



$sumamount =$request['subtotal'] + $request['tax_total'];

$encryptedurl = Crypt::encryptString($customers->id);

$response = qontak_whatsapp_helper_info_new_inv(
    $customers->phone,
    $customers->name,
    $customers->customer_id,
    $sumamount,
    $request->due_date,
    "/invoice/cst/" . $encryptedurl
);




DB::commit();
$msg = "Success create invoice.";
//Disable WA
$msgresult="";
// $msgresult= \App\Suminvoice::wa_payment($customers->phone,$message);
// $msg .="\n Whatsapp : ".$response;
return redirect ('invoice/'.$request->id_customer)->with('info',$msg);

}catch(\Exception $e){

    DB::rollback();
    return redirect ('invoice/'.$request->id_customer)->with('info','Failed to Create Invoice'.$e);
}





}

public function testwa()
{

    $response = qontak_whatsapp_helper_info_new_inv(
        '6281805360534',
        'duwija',
        '225655541',
        '200000',

        '22-22-22',
        '/invoice/cst/eyJpdiI6Ik9SanRpRUtsMGR1MngzRVlkZlBZdHc9PSIsInZhbHVlIjoiWVJwdlI0elZOQXpQejlJUUVkY2laQT09IiwibWFjIjoiM2M2ZDUzZjcwMGRkYjFhNzRjNDQ2YzM2ZGE5Mjc0ZGE4Nzg2ZDk3M2M4YWMxZGRkZGU3Yzc3ODI4Y2MzMTdjNyIsInRhZyI6IiJ9'
    );
    return $response;
}

public function search(Request $request)
{
 $date_from = ($request['date_from']);
 $date_end = ($request['date_end']);

 $suminvoice = \App\Suminvoice::orderBy('recieve_payment', 'asc')
 ->where('updated_by','=',  \Auth::user()->id)
 ->whereBetween('date',[($request['date_from']), ($request['date_end'])])
 ->get();


 return view ('suminvoice/mytransaction',['suminvoice' =>$suminvoice, 'date_from' =>$date_from, 'date_end'  =>$date_end]);



}

public function notification()
{


    $custactiveinv = \App\Customer::select ('customers.id','customers.customer_id','customers.name', 'customers.phone','customers.address','customers.billing_start','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
    ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
    ->where("suminvoices.payment_status", "=", 0)

    ->where(function ($query) {
     $query ->where("customers.id_status", "=", 2)
     ->orWhere("customers.id_status", "=", 4);
 })

    ->groupBy('customers.id')
    ->get()
    ->count('customers.id');

    $custblocked = \App\Customer::where("customers.id_status", "=", 4)

    ->get()
    ->count();

    $customerinv = \App\Customer::where('customers.id_status', '2')
    ->orWhere('customers.id_status', '4')
    ->count() ;

    $queue = \App\Job::count() ;


    $merchant = \App\Merchant::pluck('name', 'id');
    
    $custisolirdate = \App\Customer::select ('customers.id','customers.customer_id','customers.name', 'customers.phone','customers.address','customers.billing_start','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
    ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
    ->where("suminvoices.payment_status", "=", 0)

    ->where(function ($query) {
     $query ->where("customers.id_status", "=", 2)
     ->Where("customers.isolir_date", "=", date('d'));
 })

    ->groupBy('customers.id')
    ->get()
    ->count('customers.id');

    
    return view ('suminvoice/notification',['custactiveinv' =>$custactiveinv,'custblocked' =>$custblocked, 'custisolirdate' =>$custisolirdate, 'customerinv' =>$customerinv, 'queue' =>$queue, 'merchant' => $merchant ]);



}

public function searchinv(Request $request)
{
    $date_from = ($request['date_from']);
    $date_end = ($request['date_end']);
    $payment_status = ($request['payment_status']);
        // dd($payment_status);
    if ($payment_status=="")
    {
          //  dd($payment_status);
        $suminvoice = \App\Suminvoice::orderBy('id', 'DESC')
        ->whereBetween('date',[($request['date_from']), ($request['date_end'])])

        ->get(); 
    }
    else
    {
        $suminvoice = \App\Suminvoice::orderBy('id', 'DESC')
        ->whereBetween('date',[($request['date_from']), ($request['date_end'])])
        ->where('payment_status','=',  $payment_status)
        ->get(); 

    }



    return view ('suminvoice/index',['suminvoice'=>$suminvoice, 'date_from'=>$date_from, 'date_end'=>$date_end,'payment_status' =>$payment_status]);
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
        //dd($id);
        $current_inv_status =0;
        $user = \App\User::with('akuns')->find(\Auth::user()->id);
$bank = $user->akuns; // Mengembalikan koleksi akunbn

    // $bank = \App\Akun::where('category', 'kasbank')->get();
$mount = now()->format('mY');
$invoice = \App\Invoice::where('tempcode', $id)

->where('payment_status', '=', 3)
->get();

if (empty($invoice[0])){

   return abort(404);
}
else
{
    $invoice_code = \App\Invoice::where('tempcode', $id)->first();
    $suminvoice_number = \App\Suminvoice::where('tempcode', $id)->first();
    $customer = \App\Customer::where('customers.id', $invoice_code->id_customer)

    ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
    ->Join('plans', 'customers.id_plan', '=', 'plans.id')
    ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')
    ->withTrashed()
    ->first();


    $active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
    ->where ('id_customer', '=', $invoice_code->id_customer )
    ->count();
    if ($active_invoice > 1)
    {

        $last_active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
        ->where ('id_customer', '=', $invoice_code->id_customer )
        ->orderBy('date', 'asc')->first();
        if ($id==$last_active_invoice->tempcode)
        {
            $current_inv_status =0;


//result jika ada inv sebelumnya

        }
        else
        {
            $current_inv_status =1;



        }
    }




    return view ('suminvoice/show',['invoice' =>$invoice, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'current_inv_status'=>$current_inv_status]);
}

}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        //
        $current_inv_status =0;
        $bank = \App\Bank::pluck('name', 'id');
        $mount = now()->format('mY');
        $invoice = \App\Invoice::where('tempcode', $id)

        ->where('payment_status', '=', 3)
        ->get();
        if (empty($invoice[0])){

           return abort(404);
       }
       else
       {

         $invoice_code = \App\Invoice::where('tempcode', $id)->first();
         $suminvoice_number = \App\Suminvoice::where('tempcode', $id)->first();
         $merchants = \App\Merchant::where('payment_point', 1)->get();
         $customer = \App\Customer::where('customers.id', $invoice_code->id_customer)


         ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
         ->Join('plans', 'customers.id_plan', '=', 'plans.id')
         ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')
         ->withTrashed()
         ->first();
         $encryptedurl = Crypt::encryptString($invoice_code->id_customer);
         $active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
         ->where ('id_customer', '=', $invoice_code->id_customer )
         ->count();
         if ($active_invoice > 1)
         {

            $last_active_invoice = \App\Suminvoice::where('payment_status', '=', '0' )
            ->where ('id_customer', '=', $invoice_code->id_customer )
            ->orderBy('date', 'asc')->first();
            if ($id==$last_active_invoice->tempcode)
            {
                $current_inv_status =0;


//result jika ada inv sebelumnya

            }
            else
            {
                $current_inv_status =1;



            }
        }



        $endpoint     = env("TRIPAY_ENDPOINT");
        $apiKey       = env("TRIPAY_APIKEY");
        $privateKey   = env("TRIPAY_PRIVATEKEY");
        $merchantCode = env("TRIPAY_MERCHANTCODE");
        $payment_id = $suminvoice_number->payment_id;
//$hash = (hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey));



        $data = [
            'reference'         =>$payment_id,

        ];



        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => 'https://tripay.co.id/api/transaction/detail?'.http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $result = json_decode($response, true);
        $tripayinv_status = 0;
//dd($result);
        if(curl_errno($curl)){

        }
        else
        {
            if (!empty($result['data']['status']) AND ($result['data']['status']=="UNPAID") ) {
                $tripayinv_status = 1;

       // dd('UNPAIDww');
            }
            else
            {
       // dd($time);


// $currentTimestamp = time();

// $reference = $result['data']['reference'];
// $expired_time = $result['data']['expired_time'];
// $timeDifferenceSeconds = $expired_time - $currentTimestamp;
// //dd($timeDifferenceSeconds);
// if ($timeDifferenceSeconds>0) {
//     $time = $timeDifferenceSeconds;
// //dd($time);

// }
            }





        }

        curl_close($curl);




        $suminvoice_amountdue = \App\Suminvoice::where('id_customer','=', $invoice_code->id_customer )
        ->where('payment_status', '=', 0)
        ->sum('total_amount');


        return view ('suminvoice/print',['invoice' =>$invoice, 'suminvoice_amountdue'=>$suminvoice_amountdue, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number, 'current_inv_status' => $current_inv_status, 'encryptedurl'=>$encryptedurl, 'result'=>$result, 'merchants'=>$merchants]);   
    }
}
public function dotmatrix($id)
{
        //
    $bank = \App\Bank::pluck('name', 'id');
    $mount = now()->format('mY');
    $invoice = \App\Invoice::where('tempcode', $id)        
    ->where('payment_status', '=', 3)
    ->get();

    if (empty($invoice[0])){

       return abort(404);
   }
   else
   {
     $invoice_code = \App\Invoice::where('tempcode', $id)->first();
     $suminvoice_number = \App\Suminvoice::where('tempcode', $id)->first();
     $customer = \App\Customer::where('customers.id', $invoice_code->id_customer)

     ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
     ->Join('plans', 'customers.id_plan', '=', 'plans.id')
     ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')
     ->withTrashed()
     ->first();
     return view ('suminvoice/dotmatrix',['invoice' =>$invoice, 'customer'=>$customer, 'bank'=>$bank, 'suminvoice_number' => $suminvoice_number]);
 }
}
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
    public function verify($id)
    {

     $query = \App\Suminvoice::where('id', $id)
     ->update([
        'verify' =>'1']);
     return redirect ('/suminvoice/transaction')->with('success','Transaction was verified');
 }


 public function update(Request $request, $id)
 {
     DB::beginTransaction();

     try {
        $date=date("Y-m-d H:i:s");
        $msg="";
        $query = \App\Suminvoice::where('id', $id)
        ->update([
            'recieve_payment' => $request->recieve_payment,
            'payment_point' => $request->payment_point,
            'note' => $request->note,
            'updated_by' => $request->updated_by,
            'payment_status' =>1,
            'payment_date' =>now()->toDateTimeString(),


        ]);



        $data = [

            'date' => $date,
            'reff' =>  $request->tempcode.'receive',
            'type' => 'jumum',
            'description' => 'Receive Payment  #'.$request->number.' | '. $request->customer_name,
            'note' => 'Receive Payment OFFLINE  #'.$request->number.' | '.$request->customer_id. ' | '.$request->customer_name,
        ];


        $data['id_akun'] = $request->payment_point;
        $data['debet'] = $request->recieve_payment;
        \App\Jurnal::create($data);

    unset($data['debet']); // Remove debet key for the credit entry

// Create credit entry
    $data['id_akun'] = '1-10100';
    $data['kredit'] = $request->recieve_payment;
    \App\Jurnal::create($data);


    Xendit::setApiKey(env('XENDIT_KEY'));
    $id = $request->payment_id;
    $customers = \App\Customer::withTrashed()->where('id', $request->id_customer)->first();
    $oldStatus =$customers->status_name->name;

// Hitung jumlah invoice yang masih unpaid
    $active_invoice = \App\Suminvoice::where('payment_status', '=', '0')
    ->where('id_customer', '=', $request->id_customer)
    ->count();

// Jika status pelanggan = 4 dan tidak ada invoice unpaid, aktifkan kembali layanan
    if ($customers->id_status == 4 && $active_invoice <= 0) {
        $distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first();

        \App\Customer::where('id', $request->id_customer)->update(['id_status' => 2]);

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
        $updatedBy = Auth::check() ? 'Payment by ' . Auth::user()->name : 'System';


        // File log untuk customer
       // $logFile = "customers/customer_{$customers->id}.log";

        // Membuat log message
        $logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

        // Simpan log ke files
        
        \App\Customerlog::create([
            'id_customer' => $customers->id,
            'date' => now(),
            'updated_by' => $updatedBy,
            'topic' => 'payment',
            'updates' => json_encode($changes),
        ]);

        Log::channel('payment')->info("Pelanggan ID: {$customers->customer_id} diaktifkan kembali karena tidak ada invoice unpaid. |".$logMessage);

    } 
// Jika status pelanggan = 4 dan masih ada invoice unpaid
    elseif ($customers->id_status == 4 && $active_invoice > 0) {
    // Ambil invoice unpaid dengan due_date terdekat
        $active_invoice = \App\Suminvoice::where('payment_status', '=', '0')
        ->where('id_customer', '=', $request->id_customer)
        ->orderBy('due_date', 'asc')
        ->first();

    // Periksa apakah invoice masih dalam batas waktu jatuh tempo
        if ($active_invoice && Carbon::parse($active_invoice->due_date)->greaterThan(Carbon::today())) {
            $distrouter = \App\Distrouter::withTrashed()->where('id', $customers->id_distrouter)->first();

            \App\Customer::where('id', $request->id_customer)->update(['id_status' => 2]);

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
        $updatedBy = Auth::check() ? 'Payment by ' . Auth::user()->name : 'System';

        // File log untuk customer
        // $logFile = "customers/customer_{$customers->id}.log";

        // Membuat log message
        $logMessage = now() . " - {$customers->name} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

        // Simpan log ke files
        
        \App\Customerlog::create([
            'id_customer' => $customers->id,
            'date' => now(),
            'updated_by' => $updatedBy,
            'topic' => 'payment',
            'updates' => json_encode($changes),
        ]);

        Log::channel('payment')->info("Pelanggan ID: {$customers->customer_id} diaktifkan kembali karena invoice unpaid masih dalam masa jatuh tempo | ".$logMessage);
    }
}






$customers = \App\Customer::Where('id',$request->id_customer)->withTrashed()->first();
$users = \App\User::Where('id',$request->updated_by)->withTrashed()->first();
$jumlah = $request->recieve_payment; 

 // $message ="Yth. ".$customers->name." ";
 // $message .="\n";
 // $message .="\nTerimakasih, Pembayaran tagihan Customer dengan CID ".$customers->customer_id." sudah kami *TERIMA* ";
 // $message .="\nUntuk info lebih lengkap silahkan klik link";
 // $message .="\nhttp://".env("DOMAIN_NAME")."/suminvoice/".$request->tempcode."/print";

 // $message .="\n*".env("SIGNATURE")."*";

   // $message = "Halo *" . $customers->name . "*,";
   // $message .= "\n\nKami ingin mengonfirmasi bahwa pembayaran tagihan dengan CID *" . $customers->customer_id . "* telah kami terima.";
   // $message .= "\n\nUntuk informasi lebih lanjut, Anda dapat mengakses tautan berikut:";
   // $message .= "\n👉 " . url("/suminvoice/" . $request->tempcode . "/print");
   // $message .= "\n\nTerima kasih atas kepercayaan Anda.";
   // $message .= "\nSalam,";
   // $message .= "\n*" . env("SIGNATURE") . "*";










 // $notif_group ="Offline Payment ";
 // $notif_group .="\n";
 // $notif_group .="\nPembayaran tagihan dari CID".$customers->customer_id." ( ".$customers->name." )  Sudah DITERIMA";
 // $notif_group .="\nCheck : http://".env("DOMAIN_NAME")."/suminvoice/".$request->tempcode;
 // $notif_group .="\n";
 // $notif_group .="\n*".env("SIGNATURE")."*";
$msg ='';
$jumlah = $request->recieve_payment; // Ambil jumlah dari request
$jumlah_rupiah = number_format($jumlah, 0, ',', '.'); // Format menjadi rupiah




$encryptedurl = Crypt::encryptString($customers->id);

$response = qontak_whatsapp_helper_receive_payment_confirmation(
    $customers->phone,
    $customers->name,
    $request->number,
    $customers->customer_id,
    $jumlah,
    "/invoice/cst/" . $encryptedurl
);







$notif_group = "[OFFLINE PAYMENT]";
$notif_group .= "\n\nPembayaran dari pelanggan ";
$notif_group .= "\nCID :" . $customers->customer_id. "" ;
$notif_group .= "\nNama :" . $customers->name."" ;
$notif_group .= "\nSUDAH DITERIMA";
$notif_group .= "\nJumlah: Rp " . $jumlah_rupiah;
$notif_group .= "\nOleh : ". $users->name."";
$notif_group .= "\n\nUntuk melihat detail pembayaran, silakan klik tautan berikut:";
$notif_group .= "\n👉 " . url("/suminvoice/" . $request->tempcode);
$notif_group .= "\n\nTerima kasih";
$notif_group .= "\n~ " . env("SIGNATURE") . " ~";






DB::commit();
 //Disable WA
// $msg .="\n Wa to Customer : ".$response;





$process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_group.py", 
   env("TELEGRAM_GROUP_PAYMENT"), $notif_group]);
try {
            // Menjalankan proses
    $process->run();

            // Memeriksa apakah proses berhasil
    if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
    }

            // Mendapatkan output dari proses
    $output = $process->getOutput();
    $msg .="\n Send messages to Payment Group : success";


          //  return redirect ('/ticket/view/'.$request['id_customer'])->with('success', $output);
} catch (ProcessFailedException $e) {
            // Jika proses gagal, kembalikan pesan kesalahan
    $errorMessage = $e->getMessage();
          //  return redirect()->back()->with('error', $errorMessage);
}



  // $msg .="\n Wa to Payment Group :" .\App\Suminvoice::wa_payment_g($customers->phone,$notif_group);
return redirect ('/suminvoice/'. $request->tempcode)->with('info',$msg);


} catch (\Exception $e) {
        // Rollback the transaction if something went wrong
    DB::rollBack();

        // Log the error message for debugging purposes
    \Log::error('Update Suminvoice Error: ' . $e->getMessage());

        // Redirect back with a warning message
    return redirect('/suminvoice/' . $request->tempcode)->with('warning', 'Update failed: ' . $e->getMessage());
}
}

/**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
//     public function destroy(Request $request, $id)
//     {
//      $query = \App\Suminvoice::where('id', $id)
//      ->update([

//         'updated_by' => $request->updated_by,
//         'payment_status' =>2,
//         'payment_date' =>now()->toDateTimeString(),


//     ]);

//      if ($query)
//      {

//         $msg = "\n Item updated successfully!";

//         return redirect ('/suminvoice/'. $request->tempcode)->with('info',$msg);
//     }
//     else
//     {
//         return redirect ('/suminvoice/'. $request->tempcode)->with('warning','Item updated Failed');
//     }

// }



//     public function destroy(Request $request, $id)
//     {
//     DB::beginTransaction(); // Mulai transaksi

//     try {
//         // Ambil data suminvoice berdasarkan ID
//         $suminvoice = \App\Suminvoice::findOrFail($id);

//         // Update data di suminvoice
//         $suminvoice->update([
//             'updated_by' => $request->updated_by,
//             'payment_status' => 2,
//             'payment_date' => now()->toDateTimeString(),
//         ]);

//         // Update semua invoice yang memiliki tempcode yang sama
//         \App\Invoice::where('tempcode', $suminvoice->tempcode)
//         ->update(['monthly_fee' => 0]);

//         DB::commit(); // Jika semua query sukses, commit transaksi

//         return redirect('/suminvoice/' . $suminvoice->tempcode)
//         ->with('info', 'Item updated successfully!');
//     } catch (\Exception $e) {
//         DB::rollBack(); // Jika terjadi error, rollback transaksi

//         return redirect('/suminvoice/' . $request->tempcode)
//         ->with('warning', 'Item update failed: ' . $e->getMessage());
//     }
// }


public function send_reminder_inv($id)

{
    //dd($id);

    $suminvoice = \App\Suminvoice::where('id', $id)->first();

    $customer = \App\Customer::Where('id',$suminvoice->id_customer)->withTrashed()->first();
    $encryptedurl = '/invoice/cst/'. Crypt::encryptString($customer->id);
    $formattedDate = Carbon::parse($suminvoice->date)->translatedFormat('M Y');


    $response = qontak_whatsapp_helper_remainder_inv(
        $customer->phone,
        $customer->name,
        $customer->customer_id,
        "#".$suminvoice->number,
        $suminvoice->total_amount,
        $formattedDate,
        $suminvoice->due_date,
        $encryptedurl
    );

    return redirect()->back()->with($response, $response);

}

public function destroy(Request $request, $id)
{
    DB::beginTransaction(); // Mulai transaksi

    try {
        // Ambil data suminvoice berdasarkan ID
        $suminvoice = \App\Suminvoice::findOrFail($id);

        // Update data di suminvoice
        $suminvoice->update([
            'updated_by' => $request->updated_by,
            'payment_status' => 2,
            'payment_date' => "",
        'note' => $request->cancel_reason // Simpan alasan pembatalan
    ]);

        // Update semua invoice yang memiliki tempcode yang sama
        \App\Invoice::where('tempcode', $suminvoice->tempcode)
        ->update(['monthly_fee' => 0]);

        // Soft delete pada tabel jurnals berdasarkan reff = tempcode atau tempcode + 'receive'
        \App\Jurnal::where('reff', $request->tempcode)
        ->orWhere('reff', $request->tempcode . 'receive')
        ->delete(); // Soft delete jika model menggunakan `SoftDeletes`

        DB::commit(); // Jika semua query sukses, commit transaksi

        return redirect()->back()
        ->with('success', 'Invoice updated and related journal entries deleted successfully!');
    } catch (\Exception $e) {
            DB::rollBack(); // Jika terjadi error, rollback transaksi

            return redirect()->back()
            ->with('error', 'Invoice update failed: ' . $e->getMessage());
        }
    }



    public function invoicenotif()
    {
        $phone = '081805360534';
// \Log::channel('invoice')->info('==== START INVOICE CREATE BY SYSTEM. ===');
        $unpaidinv =\App\Suminvoice::orderBy('id', 'DESC')
        ->where('payment_status','=', '0')
        ->limit (3)
        ->get();



        foreach($unpaidinv as $inv) {
          $customer = \App\Customer::Where('id',$inv->id_customer)->withTrashed()->first();
      // $message ="Yth. ".$customer->name." ";
      // $message .="\n";
      // $message .="\nTagihan Customer dengan CID *".$customer->customer_id."* sudah kami Terbitkan sebesar *Rp.". $inv->total_amount."*";
      // $message .="\nSilahakan melakukan pembayaran sebelum tanggal 20-".date("m-Y", time());
      // $message .="\nUntuk info lebih lengkap silahkan klik link berikut";
      // $message .="\nhttp://".env("DOMAIN_NAME")."/suminvoice/".$inv->tempcode."/print";
      // $message .="\n";
      // $message .="\n~ ".env("SIGNATURE")." ~";
          $message = "Halo *" . $customer->name . "*,";
          $message .= "\n\nTagihan dengan CID *" . $customer->customer_id . "* telah diterbitkan sebesar *Rp. " . number_format($inv->total_amount, 0, ',', '.') . "*.";
          $message .= "\nMohon melakukan pembayaran sebelum *20-" . date("m-Y") . "*.";
          $message .= "\n\nUntuk informasi lebih lanjut, silakan klik tautan berikut:";
          $message .= "\n👉 " . url("/suminvoice/" . $inv->tempcode . "/print");
          $message .= "\n\nTerima kasih atas perhatian Anda.";
          $message .= "\n~ *" . env("SIGNATURE") . "* ~";


//Disable WA
      // $msgresult=\App\Suminvoice::wa_payment($phone,$message);


          sleep(2); 


      }


  }







  public function faktur (Request $request, $id)
  {
      $request->validate([
        'file' => 'required'
    ]); 

      if($request->file('file')) {
       $file = $request->file('file');
       $name = $file->getClientOriginalName();
       $filename = time().'_'.str_replace(' ', '_',$name);

         // File upload location
       $location = 'upload/tax';

         // Upload file
       $file->move($location,$filename);

       $id_customer = ($request['id_customer']);

       $tempcode = ($request['tempcode']);

       \App\Suminvoice::where('id', $id)
       ->update([

        'file' => $filename


    ]);


       return redirect ('/suminvoice/'.$tempcode)->with('success','file Updated successfully!');
   }else{
    return redirect ('/suminvoice'.$tempcode)->with('success','File Not Uploaded!');
}
}
}
