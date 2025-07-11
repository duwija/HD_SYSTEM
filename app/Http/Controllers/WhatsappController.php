<?php

namespace App\Http\Controllers;
use \Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Exception\GuzzleException;
Use GuzzleHttp\Clients;
use \Carbon\Carbon;
use DataTables;
use Illuminate\Support\Facades\Http;
use App\Walog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class WhatsappController extends Controller
{
   protected $gatewayUrl;
   public function __construct()
   {
    // $this->middleware('auth');
    //   $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,vendor,merchant'); // Daftar privilege

     $this->middleware('auth')
     ->except([ 'webhook', 'ack','history' ]);

     $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,vendor,merchant')
     ->except([ 'webhook', 'ack','history' ]);
     $this->gatewayUrl = env('WA_GATEWAY_URL').'/api';
 }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    public function wa_ticket(Request $request)
    {
//dd ($request);

       if (env('WAPISENDER_STATUS')!="disable")
       {
        $client = new Clients();
        $number = $request->phone;

        if(substr(trim($number), 0, 2)=="62"){
            $hp    =trim($number);
        }
            // cek apakah no hp karakter ke 1 adalah angka 0
        else if(substr(trim($number), 0, 1)=="0"){
            $hp    ="62".substr(trim($number), 1);
        }

        $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
            'form_params' => [
               'token' => env('WAPISENDER_KEY'),
               'number' => $hp,
               'message' =>$request->message,
           ]
       ]);

//Kirim pesan ke group
        $result= $result->getBody();
        $array = json_decode($result, true);

        return redirect()->back()->with('success','Message '.$array['status']); 
    }
    else
    {
        return redirect()->back()->with('error','WA Disabled');
    }

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


    public static function scan_qrcode()
    {


        if (env('WAPISENDER_STATUS')!="disable")
        {

           $responseJson = '{ "result": "true", "id": "", "phoneNumber": "", "name": "", "os_version": "", "manufacturer": "", "model": "", "imgUrl": "", "batteryLevel": "", "id_device": "", "token": "", "expired": "", "paket": "", "message": "" }';


    // Convert JSON to associative array
           $deviceData = json_decode($responseJson, true);
           try{ 

               $client = new Clients(); 
               $result = $client->post(env('WAPISENDER_QR'), [
                'form_params' => [
                    'token' => env('WAPISENDER_KEY'),
                    
                ],
                ['connect_timeout' => 5, 'timeout' => 5]
            ]);

               $result= $result->getBody();
               if ($result == "<br><br><center>AUTHENTICATED</center>")


                $deviceData = $client->post(env('WAPISENDER_DEVICE'), [
                    'form_params' => [
                        'token' => env('WAPISENDER_KEY'),

                    ],
                    ['connect_timeout' => 5, 'timeout' => 5]
                ]); 
            $infoData = $client->post(env('WAPISENDER_INFO'), [
                'form_params' => [
                    'token' => env('WAPISENDER_KEY'),
                    'username' =>env('WAPISENDER_USER'),

                ],
                ['connect_timeout' => 5, 'timeout' => 5]
            ]); 
            $infoData = json_decode($infoData->getBody(), true);
            $deviceData = json_decode($deviceData->getBody(), true);
            return view ('wa/qrcode',['result' => $result, 'deviceData' => $deviceData, 'infoData' => $infoData ]);


        }

        catch (Exception $e)
        {

        }



    }
    else
    {
       return "disabled";
   }


}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }
// app/Http/Controllers/WhatsappController.php

    public function showQr(Request $request, $session)
    {
        try {
            $response = Http::get($this->gatewayUrl . '/' . $session . '/qr');
            $data     = $response->json();
        } catch (\Exception $e) {
            return view('wa.qr', [
                'status' => 'error',
                'qrUrl'  => null,
                'device' => [],
                'error'  => 'Gagal terhubung ke Gateway: ' . $e->getMessage()
            ]);
        }

        $status  = $data['status'] ?? 'error';
        $qrRaw   = $data['qr'] ?? null;
        $device  = (array) ($data['device'] ?? []);
        $qrUrl   = $qrRaw ? 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrRaw) : null;

        return view('wa.qr', compact('status', 'qrUrl', 'device'));
    }


    public function logout($session)
    {
        try {
            Http::post($this->gatewayUrl . '/' . $session . '/logout');
            return redirect()->back()->with('success', 'WhatsApp berhasil logout.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal logout: ' . $e->getMessage());
        }
    }


    public function restart($session)
    {
        try {
            Http::post($this->gatewayUrl . '/' . $session . '/restart');
            return redirect()->back()->with('success', 'Client berhasil di-restart.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal restart: ' . $e->getMessage());
        }
    }

    public function getGroups($session)
    {
        try {
            $response = Http::get($this->gatewayUrl . '/' . $session . '/groups');
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal ambil data grup'], 500);
        }
    }

    // 2) Outbound send (dipanggil dari frontend Anda)
    public function send(Request $request, $session)
    {
        $gateway = $this->gatewayUrl;

    // Log seluruh request payload (untuk debugging)
        Log::info('Incoming send() request', $request->all());

        $request->validate([
            'number'  => 'required|string',
            'message' => 'required|string',
        ]);

    // Log input yang ter-validate
        Log::info('WhatsApp send() called', [
            'session' => $session,
            'number'  => $request->number,
            'message' => $request->message,
        ]);

    // Bangun URL ke Node.js gateway
        $gatewayUrl = rtrim($gateway, '/') . "/{$session}/send";
        Log::info('Posting to gateway', ['url' => $gatewayUrl]);

    // Kirim ke gateway
        $resp = Http::post($gatewayUrl, [
            'number'  => $request->number,
            'message' => $request->message,
        ]);

    // Log response dari gateway
        Log::info('Gateway response', [
            'status' => $resp->status(),
            'body'   => $resp->body(),
        ]);

        if (! $resp->successful()) {
            Log::error('Failed to send via gateway', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ]);
            return back()->with('error', 'Gagal kirim pesan.');
        }

        $body = $resp->json();
        Log::info('Gateway ACK body', $body);

    // Simpan log outbound ke DB
        Walog::create([
            'session'     => $session,
            'number'      => $request->number,
            'message'     => $request->message,
            'status'      => 'pending',
            'message_id'  => $body['messageId'] ?? null,
            'direction'   => 'out',
            'created_at'  => now(),
        ]);

        return back()->with('success', 'Pesan dikirim, menunggu ack WA.');
    }

    public function logs()
    {
        // ambil daftar session yang pernah digunakan (distinct)
        $sessions = Walog::query()
        ->select('session')
        ->distinct()
        ->pluck('session')
        ->toArray();

        return view('wa.logs', compact('sessions'));
    }
    public function logsTable(Request $request)
    {
        // \Log::info('🔍 logsTable dipanggil', $request->all());

        $query = Walog::query();

        if ($request->date_from && $request->date_end) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_end)->endOfDay()
            ]);
        }

        if ($request->number) {
            $query->where('number', 'like', '%' . $request->number . '%');
        }

        if ($request->session) {
            $query->where('session', $request->session);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
        ->addIndexColumn()
        ->editColumn('message', function ($row) {
        $short = Str::limit($row->message, 65); // 40 karakter
        return '<span title="' . e($row->message) . '">' . e($short) . '</span>';
    })
    ->rawColumns(['message']) // agar HTML <span> tidak di-escape
    ->make(true);
}
public function webhook(Request $request)
{
    $data = $request->validate([
        'session'   => 'required|string',
        'from'      => 'required|string',
        'body'      => 'nullable|string',
        'id'        => 'required|string',
        'timestamp' => 'required|integer',
    ]);

    Walog::create([
        'session'    => $data['session'],
        'number'     => $data['from'],
        'message'    => $data['body'],
        'status'     => 'received',
        'message_id' => $data['id'],
        'direction'  => 'in',
        'created_at' => now(),
    ]);

    return response()->noContent();
}

// 3) Ack update dari Node.js
public function ack(Request $request, $session)
{
    $data = $request->validate([
        'id'  => 'required|string',
        'ack' => 'required|integer',
    ]);

    $log = Walog::where('session', $session)
    ->where('message_id', $data['id'])
    ->where('direction', 'out')
    ->first();

    if ($log) {
            // map kode ack ke status
        $map = [
                0 => 'pending',   // belum dikirim ke server WA
                1 => 'sent',      // diterima WA server
                2 => 'delivered', // sampai di device penerima
                3 => 'read',      // sudah dibaca
            ];
            $log->status = $map[$data['ack']] ?? 'unknown';
            $log->save();
        }

        return response()->noContent();
    }


    public function chat()
    {
        return view('wa.chat');
    }
    public function chats(Request $request, $session)
    {
    // Panggil Node Gateway untuk semua chat
        try {
            $resp  = Http::get("{$this->gatewayUrl}/{$session}/chats");
            $chats = collect($resp->json());
        } catch (\Exception $e) {
            Log::error('chats(): gagal fetch chats', ['err'=>$e->getMessage()]);
            $chats = collect();
        }

    // Hanya return fields yang kita butuhkan
        $list = $chats->map(fn($c) => [
            'id'          => $c['id'],
        'name'        => $c['name'],        // ini sudah grup name atau pushname
        'unreadCount' => $c['unreadCount'] ?? 0,
    ])->values();

        return response()->json($list);
    }
    public function chatTable(Request $request)
    {
        $session = $request->query('session');

    // Ambil semua log, biarkan Eloquent hydrate created_at sebagai Carbon
        $logs = Walog::where('session', $session)
        ->orderBy('created_at')
        ->get([
           'number AS chatId',
           'direction',
           'message',
                     'created_at',           // <–– jangan alias jadi timestamp
                 ]);

        $grouped = $logs->groupBy('chatId')->map(function ($msgs, $chatId) {
            /** @var \App\Walog $last */
            $last = $msgs->last();

            return [
                'chatId'       => $chatId,
            'name'         => $chatId,   // atau lookup nama grup
            'lastMessage' => $last->message,
            // Gunakan Carbon instance created_at
            'timestamp'    => $last->created_at,
            'unreadCount'  => $msgs->where('direction', 'in')->count(),
        ];
    })->values();

        return response()->json(['data' => $grouped]);
    }


    public function chatsDb(Request $request, $session)
    {
        // 1) Ambil semua chatId unik dari DB
        $chatIds = Walog::where('session', $session)
        ->distinct()
        ->pluck('number')
        ->toArray();

        // 2) Fetch daftar grup dari gateway (id + name)
        try {
            $resp   = Http::get("{$this->gatewayUrl}/{$session}/groups");
            $groups = collect($resp->json())->keyBy('id'); // [ id => ['name'=>...] ]
        } catch (\Exception $e) {
            Log::warning("chatsDb: gagal fetch groups", ['err'=>$e->getMessage()]);
            $groups = collect();
        }

        // 3) Bangun array gabungan
        $chats = collect($chatIds)->map(function($id) use ($groups, $session) {
            if ($groups->has($id)) {
                // grup
                return [
                    'id'          => $id,
                    'name'        => $groups[$id]['name'],
                    'unreadCount' => 0,
                ];
            } else {
                // personal: fetch pushname via gateway
                try {
                    $resp    = Http::get("{$this->gatewayUrl}/{$session}/contact/" . urlencode($id));
                    $contact = $resp->json(); // { pushname, number }
                    $name    = $contact['pushname'] ?: $contact['number'];
                } catch (\Exception $e) {
                    Log::warning("chatsDb: gagal fetch contact {$id}", ['err'=>$e->getMessage()]);
                    $name = $id;
                }
                return [
                    'id'          => $id,
                    'name'        => Str::limit($name, 30),
                    'unreadCount' => 0,
                ];
            }
        })->values();

        return response()->json($chats);
    }
    protected function lookupName($chatId, $session, $groups = null)
    {
    // Grup
        if (Str::endsWith($chatId, '@g.us')) {
            if ($groups && $groups->has($chatId)) {
                return $groups[$chatId]['name'];
            }
        // Fallback jika belum fetch
            try {
                $resp = Http::get("{$this->gatewayUrl}/{$session}/groups");
                $groups = collect($resp->json())->keyBy('id');
                return $groups->get($chatId)['name'] ?? $chatId;
            } catch (\Exception $e) {
                return $chatId;
            }
        }
    // Personal
        try {
            $resp = Http::get("{$this->gatewayUrl}/{$session}/contact/" . urlencode($chatId));
            $contact = $resp->json();
            return $contact['pushname'] ?: $contact['number'];
        } catch (\Exception $e) {
            return $chatId;
        }
    }


    public function history(Request $request, $session)
    {
        $chatId = $request->query('chatId');
        // Log::info('[WA-HISTORY] chatId param:', ['chatId' => $chatId, 'session' => $session]);

    // Fetch daftar grup sekali saja (optional, biar hemat API call)
        $groups = null;
        if ($chatId && Str::endsWith($chatId, '@g.us')) {
            try {
                $resp   = Http::get("{$this->gatewayUrl}/{$session}/groups");
                $groups = collect($resp->json())->keyBy('id');
                // Log::info('[WA-HISTORY] Groups fetched', ['groups' => $groups->keys()]);
            } catch (\Exception $e) {
                $groups = collect();
                // Log::warning('[WA-HISTORY] Failed to fetch groups', ['error' => $e->getMessage()]);
            }
        }

        $query = Walog::where('session', $session)
        ->orderBy('created_at');
        if ($chatId) {
            $query->where('number', $chatId);
            Log::info('[WA-HISTORY] Filtered by chatId');
        }

        $logs = $query->get([
            'number AS chatId',
            'direction',
            'message',
            'created_at',
        ]);
        // Log::info('[WA-HISTORY] Log count', ['count' => $logs->count()]);

        $grouped = $logs->groupBy('chatId')->map(function ($msgs, $chatId) use ($session, $groups) {
        // Dapatkan nama grup atau personal
            $name = $this->lookupName($chatId, $session, $groups);
            Log::info('[WA-HISTORY] Grouping chat', ['chatId' => $chatId, 'msgCount' => $msgs->count()]);
            return [
                'chatId'   => $chatId,
                'name'     => $name,
                'messages' => $msgs->map(function($m) {
                    $ts = \Carbon\Carbon::parse($m->created_at)->timestamp;
                    return [
                        'id'        => $m->chatId . '_' . $ts,
                        'fromMe'    => $m->direction === 'out',
                        'body'      => $m->message,
                        'timestamp' => $ts,
                    ];
                })->values(),
            ];
        })->values();

        // Log::info('[WA-HISTORY] Final grouped result', ['groupedCount' => $grouped->count()]);

        return response()->json($grouped);
    }

    public function myprofile($id)
    {


    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {


    }

}
