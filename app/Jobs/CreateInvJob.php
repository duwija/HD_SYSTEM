<?php

namespace App\Jobs;

use App\Customer;
use App\Invoice;
use App\Suminvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use App\Mail\EmailNotification;
use Illuminate\Support\Facades\Mail;
use App\Helpers\WaGatewayHelper;
class CreateInvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerId;
    protected $inv_date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($customerId, $inv_date)
    {
        $this->customerId = $customerId;
        $this->inv_date = $inv_date;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Creating invoice by jobs');
        $customer = Customer::where('customers.id', $this->customerId)
        ->join('plans', 'customers.id_plan', '=', 'plans.id')
        ->select('customers.*', 'plans.name as plan', 'plans.price as price')
        ->lockForUpdate()
        ->first();

        if (!$customer) {
            Log::error('Customer not found: ' . $this->customerId);
            return;
        }

        $month = Carbon::parse($this->inv_date)->format('mY');
        $period = Carbon::parse($this->inv_date)->format('F Y'); 
        $latest_number = uniqid();
        $invdate = Carbon::parse($this->inv_date)->format("Y-m-d");
        $notif='';


        $check_invoice = Invoice::where('id_customer', $customer->id)
        ->where('periode', $month)
        ->where('monthly_fee', '1')
        ->first();

        if ($check_invoice) {

            \Log::channel('invoice')->warning('CID : '. $customer->customer_id. ' |' . $customer->name . ' already have monthly Inv => INFO!! ');
            return;
        }
        else
        {

           $tempcode = sha1(time()) . rand();

           DB::beginTransaction();

           try {
            // Step 1: Create Item Invoice
            Invoice::create([
                'id_customer' => $customer->id,
                'monthly_fee' => '1',
                'periode' => $month,
                'description' => 'Monthly fee package ' . $customer->plan,
                'qty' => '1',
                'amount' => $customer->price,
                'payment_status' => 3,
                'tax' => '0',
                'tempcode' => $tempcode,
                'created_by' => 'System',
            ]);

            // Step 2: Create INV
            $due_date_isolir = $customer->isolir_date - 1;
            $due_date = ($due_date_isolir < 1) ? null : Carbon::parse($invdate)->format("Y-m-" . $due_date_isolir);



            $tax = $customer->tax;
            $total_amount = $customer->price + ($customer->price * $tax / 100);
            $result= Suminvoice::create([
                'id_customer' => $customer->id,
                'number' => $latest_number,
                'date' => $invdate,
                'payment_status' => 0,
                'tax' => $tax,
                'tempcode' => $tempcode,
                'due_date' => $due_date,
                'total_amount' => $total_amount,
            ]);



            $total_tax = $customer->price * $tax / 100;

// Data dasar jurnal
            $base_data = [
                'tax_total' => $total_tax,
                'date' => $invdate,
                'reff' => $tempcode,
                'type' => 'jumum',
                'description' => 'Invoice #' . $latest_number . ' | ' . $customer->name,
                'note' => 'Invoice #' . $latest_number . ' | ' . $customer->customer_id . ' | ' . $customer->name,
                'contact_id' => $customer->customer_id
            ];

// 1. Catat KREDIT ke akun Pendapatan
            \App\Jurnal::create(array_merge($base_data, [
                'id_akun' => '4-40000',
                'kredit' => $customer->price,
            ]));

// 2. Jika ada pajak, catat KREDIT ke akun Pajak
            if (!empty($tax) && $tax != 0) {
                \App\Jurnal::create(array_merge($base_data, [
                    'id_akun' => '2-20500',
                    'kredit' => $total_tax,
                ]));
            }

//  3. Terakhir, catat DEBIT ke akun Kas/Bank
            \App\Jurnal::create(array_merge($base_data, [
                'id_akun' => '1-10100',
    'debet' => $total_amount, // price + total_tax
]));



            DB::commit();
            if ($result) {

                $encryptedUrl = Crypt::encryptString($customer->id);
                $duedate = $due_date ?: 'N/A';
                if ($customer->notification == 1) {
        // WhatsApp Notification
                    $message = "*[Informasi Pembayaran Internet]*";
                    $message .= "\n\n";
                    $message .= "Yth. " . $customer->name . ",";
                    $message .= "\n\n";
                    $message .= "Tagihan Anda dengan Customer ID (CID) *" . $customer->customer_id . "* telah diterbitkan.";
                    $message .= "\n*Total Tagihan:* Rp." . number_format($total_amount, 0, ',', '.') . "";
                    $message .= "\n*Batas Pembayaran:* " . $due_date;
                    $message .= "\n\n";
                    $message .= "Untuk informasi lebih lanjut, silakan klik link berikut:";
                    $message .= "\n" . "http://" . env("DOMAIN_NAME") . "/invoice/cst/" . $encryptedUrl;
                    $message .= "\n\n";
                    $message .= "Jika sudah melakukan pembayaran, abaikan pesan ini.";
                    $message .= "\nJika ada pertanyaan, hubungi CS kami di ".env("PAYMENT_WA");
                    $message .= "\n\n";
                    $message .= "".env("SIGNATURE")."";
                    $msgresult = WaGatewayHelper::wa_payment($customer->phone, $message);
                    // $msgresult = \App\Suminvoice::wa_payment($customer->phone, $message);

                    $notif='Notification by WhatsApp';

                } elseif ($customer->notification == 2) {
        // Email Notification
                    if (!empty($customer->email)) {
                        $data = [
                            'phone' => $customer->phone,
                            'name' => $customer->name,
                            'customer_id' => $customer->customer_id,
                            'number' => "#" . $latest_number,
                            'total_amount' => $total_amount,
                            'date' => $invdate,
                            'due_date' => $duedate,
                            'url' => $encryptedUrl,
                        ];

                        try {
                            Mail::to($customer->email)->send(new EmailNotification($data));
                            $notif = 'Notification by Email';
                        } catch (\Exception $e) {
                            \Log::error("Gagal kirim email ke {$customer->email} untuk {$customer->name} ({$customer->customer_id}): " . $e->getMessage());
                            $notif = 'Notification by Email ERROR: ' . $e->getMessage();
                        }

                    }
                }
            }


            \Log::channel('invoice')->info('CID : '. $customer->customer_id. ' |' . $customer->name . ' Created monthly Inv |'. $notif);

        } catch (Exception $e) {
            DB::rollback();
            \Log::channel('invoice')->error('Failed to create invoice for CID : ' . $customer->customer_id . ' |' . $customer->name . ' | Error: ' . $e->getMessage());
        }
    }
}
}
