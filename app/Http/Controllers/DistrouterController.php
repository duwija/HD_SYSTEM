<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use \RouterOS\Client;
use \RouterOS\Query;
use Carbon\Carbon;
class DistrouterController extends Controller
{
 public function __construct()
 {
    $this->middleware('auth');
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */




    
    public function index()
    {
        //
        $distrouter = \App\Distrouter::orderby('id','DESC')
        ->get();


        return view ('distrouter/index',['distrouter' =>$distrouter]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //

        return view ('distrouter/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {




    // Validate the request data
        $validatedData = $request->validate([
        'name' => ['required', 'string', 'max:255', 'unique:distrouters,name'], // Corrected the 'unique' rule to target the 'olts' table and 'name' column
        'ip' => 'required|ip', // Added IP validation for the 'ip' field
        'port' => 'required|integer|min:1|max:65535', // Added integer validation and port range
        'web' => 'required|integer|min:1|max:65535', // Added integer validation and port range
        'user' => 'required|string|max:255', // Added string validation and max length for 'user'
        'password' => 'required|string|max:255', // Added string validation and max length for 'password'
        'note' => 'required|string|max:255', // Added string validation and max length for 'password'
        
    ]);

        try {
        // Create a new Olt record
            \App\Distrouter::create([
                'name' => $validatedData['name'],
                'ip' => $validatedData['ip'],
                'port' => $validatedData['port'],
                'web' => $validatedData['web'],
                'user' => $validatedData['user'],
                'password' => $validatedData['password'],
                'note' => $validatedData['note'],
                
            'created_at' => now(), // Use current timestamp for created_at
        ]);

            return redirect('/distrouter')->with('success', 'Item created successfully!');
        } catch (\Exception $e) {
        // Handle any exceptions that occur during the creation process
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the item: ' . $e->getMessage()]);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function backupsconfig($id)
    {
        try {
            $nextDate = Carbon::tomorrow()->format('M/d/Y');

            // Connect to the MikroTik Router
            $distrouter = \App\Distrouter::findOrFail($id);
            $client = new Client([

            //to login to api
                'host' => $distrouter->ip,
                'user' => $distrouter->user,
                'pass' => $distrouter->password,
                'port' => $distrouter->port,
            //data


            ]);

            // Query to get Ethernet interfaces and their traffic statistics
            $queryscript =  (new Query('/system/script/add'))
            ->equal('name', 'BackupConfigByAlus')
            ->equal('source',
                ':local sysname [/system identity get name]; :local textfilename; :local backupfilename; :local time [/system clock get time]; :local date [/system clock get date]; :local newdate ""; :for i from=0 to=([:len $date]-1) do={ :local tmp [:pick $date $i]; :if ($tmp !="/") do={ :set newdate "$newdate$tmp" }; :if ($tmp ="/") do={} }; :if ([:find $sysname " "] !=0) do={ :local name $sysname; :local newname ""; :for i from=0 to=([:len $name]-1) do={ :local tmp [:pick $name $i]; :if ($tmp !=" ") do={ :set newname "$newname$tmp" }; :if ($tmp =" ") do={ :set newname "$newname_" } }; :set sysname $newname; }; :set textfilename ($"newdate" . "-" . $"sysname" . ".rsc"); :set backupfilename ($"newdate" . "-" . $"sysname" . ".backup"); :execute [/export file=$"textfilename"]; :execute [/system backup save name=$"backupfilename"]; :delay 2s; tool fetch url="ftp://'.env("DOMAIN_NAME").'/$textfilename" src-path=$textfilename user='.env("FTP_USER").' password='.env("FTP_PASSWORD").' port=21 upload=yes; tool fetch url="ftp://'.env("DOMAIN_NAME").'/$backupfilename" src-path=$backupfilename user='.env("FTP_USER").' password='.env("FTP_PASSWORD").' port=21 upload=yes; :delay 5s; /file remove $textfilename; /file remove $backupfilename;');


            // Send query to RouterOS
            $backupscript = $client->query($queryscript)->read();

            $queryscheduler =
            (new Query('/system/scheduler/add'))
            ->equal('name', 'BackupConfigByAlus')
            ->equal('on-event', 'BackupConfigByAlus')
            ->equal('interval', '3d 00:00:00')
            ->equal('start-date', $nextDate)
            ->equal('start-time', '00:00:10');

            $response = $client->query($queryscheduler)->read();
            $responseString = json_encode($response); 

            // Return the response as JSON
            //return response()->json(['success' => true, 'backupscript' => $response]);
            return redirect ('/distrouter/' . $id)->with('success', $responseString);
        } catch (\Exception $e) {
           return redirect ('/distrouter/' . $id)->with('error', $responseString);
       }
   }




   public function getrouterinterfaces($id)
   {
    try {
            // Connect to the MikroTik Router
       $distrouter = \App\Distrouter::findOrFail($id);
       $client = new Client([

            //to login to api
        'host' => $distrouter->ip,
        'user' => $distrouter->user,
        'pass' => $distrouter->password,
        'port' => $distrouter->port,
            //data


    ]);

            // Query to get Ethernet interfaces and their traffic statistics
       $query = new Query('/interface/ethernet/print');

            // Send query to RouterOS
       $routerInterfaces = $client->query($query)->read();

            // Return the response as JSON
       return response()->json(['success' => true, 'routerInterfaces' => $routerInterfaces]);
   } catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 500);
}
}


// public function getrouterinfo($id)
// {


//     $result = 'unknow';


//     try {


//         $distrouter = \App\Distrouter::findOrFail($id);
//         $client = new Client([

//             //to login to api
//             'host' => $distrouter->ip,
//             'user' => $distrouter->user,
//             'pass' => $distrouter->password,
//             'port' => $distrouter->port,
//             //data


//         ]);
//            // dd($distrouter);

// // Create a query to get system status
//         $query = new Query('/system/resource/print');

// // Execute the query
//         $routerInfo = $client->query($query)->read();

//         $pppActiveQuery = new Query('/ppp/active/print');
//         $pppActiveQuery->equal('count-only', '');
//         $pppActive = $client->query($pppActiveQuery)->read();
//         $pppActiveCount = $pppActive['after']['ret'];

//         $pppUserQuery = new Query('/ppp/secret/print');
//         $pppUserQuery->equal('count-only', '');
//         $pppUser = $client->query($pppUserQuery)->read();
//         $pppUserCount = $pppUser['after']['ret'];


// // Display the response

//         return response()->json(['success' => true, 'routerInfo' => $routerInfo, 'pppActiveCount' => $pppActiveCount, 'pppUserCount' => $pppUserCount]);


//     }


//     catch (Exception $ex) {
//         $result = 'Unknow';
//     }




// }

public function executeCommand(Request $request)
{
    $command = $request->input('command');
    $id = $request->input('id');

    // Pastikan perintah dan ID tidak kosong
    if (!$command || !$id) {
        return response()->json(['error' => 'Command or ID not specified'], 400);
    }

    try {
        // Cari Distrouter berdasarkan ID
        $distrouter = \App\Distrouter::findOrFail($id);

        // Membuat koneksi ke MikroTik menggunakan RouterosAPI
        $client = new Client([
            'host' => $distrouter->ip,
            'user' => $distrouter->user,
            'pass' => $distrouter->password,
            'port' => $distrouter->port,
            'timeout' => 5,  // Waktu timeout
        ]);
        //$command='/ip/address/print';
        //return response()->json(['error' => $command], 400);
        // Menjalankan perintah dengan Query
        $query = new Query($command);
        $output = $client->query($query)->read();  // Membaca hasil perintah

        
        // Mengembalikan hasil perintah sebagai JSON
        return response()->json([
            'output' => $output // Kirim output langsung dalam bentuk array
        ]);
    } catch (\Exception $e) {
        // Tangani kesalahan dan tampilkan pesan kesalahan
        return response()->json(['error' => 'Error executing command: ' . $e->getMessage()], 500);
    }
}







public function getPppoeUsers($id, $status)
{
    try {
        $distrouter = \App\Distrouter::findOrFail($id);
        $customers = \App\Customer::where('id_distrouter', $id)->get();
        \Log::info($customers);
        $client = new Client([
            'host' => $distrouter->ip,
            'user' => $distrouter->user,
            'pass' => $distrouter->password,
            'port' => $distrouter->port,
            'timeout' => 5,
        ]);

        // Ambil daftar semua pengguna PPPOE
        $pppUserQuery = new Query('/ppp/secret/print');
        $pppActiveQuery = new Query('/ppp/active/print');
        $pppUsers = $client->query($pppUserQuery)->read();
        $pppActive = $client->query($pppActiveQuery)->read();
        $onlineUser = collect($pppActive)->pluck('name')->toArray();
        $color = "badge-info";
        $onlineUser = [];
        foreach ($pppActive as $active) {
            $onlineUser[$active['name']] = [
                'address' => $active['address'] ?? 'Unknown',
                'uptime' => $active['uptime'] ?? 'Unknown',
            ];
        }
        // Pisahkan pengguna berdasarkan status
        $online = [];
        $offline = [];
        $disabled = [];


        foreach ($pppUsers as $user) {
            $customer = $customers->firstWhere('pppoe', $user['name']);

            if (!empty($customer)) {
                if ($customer->id_status == 1) {
                    $color = "badge-warning";
                } elseif ($customer->id_status == 2) {
                    $color = "badge-success";
                } elseif ($customer->id_status == 3) {
                    $color = "badge-secondary";
                } elseif ($customer->id_status == 4) {
            $color = "badge-danger"; // Jika 'badge-dagger' salah, ganti ke 'badge-danger'
        } elseif ($customer->id_status == 5) {
            $color = "badge-primary";
        }

        $customerLink = '<a href="/customer/'.$customer->id.'" class="badge '.$color.'">'.$user['name'].'</a>';
    } else {
        $customerLink = $user['name'];
    }
    $userInfo = [


        'name' => $customerLink,
        'description' => $user['comment'] ?? 'No Description',
        'profile' => $user['profile'] ?? 'Unknown',
                // 'local_address' => $user['local-address'] ?? 'Unknown',
                // 'remote_address' => $user['remote-address'] ?? 'Unknown',
        'last_logout' => $user['last-logged-out'] ?? 'N/A',
        'last_disconnect_reason' => $user['last-disconnect-reason'] ?? 'N/A',
        'status' => ''
    ];

    if (isset($user['disabled']) && $user['disabled'] == 'true') {
        $userInfo['status'] = 'Disabled';
        $userInfo['address'] = '';
        $userInfo['uptime'] = '';
        $disabled[] = $userInfo;
    } elseif (array_key_exists($user['name'], $onlineUser)) {
        $userInfo['status'] = 'Online';
        $userInfo['address'] = $onlineUser[$user['name']]['address'];
        $userInfo['uptime'] = $onlineUser[$user['name']]['uptime'];
        $online[] = $userInfo;
    } else {
        $userInfo['status'] = 'Offline';
        $userInfo['address'] = '';
        $userInfo['uptime'] = '';
        $offline[] = $userInfo;
    }
}

        // Filter berdasarkan status
$filteredUsers = match ($status) {
    'online' => $online,
    'offline' => $offline,
    'disabled' => $disabled,
            default => array_merge($online, $offline, $disabled), // Jika status tidak valid, kirim semua data
        };

        return response()->json([
            'success' => true,
            'data' => $filteredUsers,
        ]);
    } catch (\Exception $ex) {
        \Log::error("MikroTik API Error: " . $ex->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching data from RouterOS',
            'error' => $ex->getMessage()
        ], 500);
    }
}









public function getRouterInfo($id)
{
    $routerInfo = [];
    $online = [];
    $offline = [];
    $disabled = [];
    $pppActiveCount = 0;
    $pppUserCount = 0;
    $pppOfflineCount = 0;
    $pppDisabledCount = 0;

    try {
        $distrouter = \App\Distrouter::findOrFail($id);

        $client = new Client([
            'host' => $distrouter->ip,
            'user' => $distrouter->user,
            'pass' => $distrouter->password,
            'port' => $distrouter->port,
            'timeout' => 5,
        ]);

        // 1. Caching system resource info
        $routerInfo = Cache::remember("router_info_{$id}", 30, function () use ($client) {
            try {
                $query = new Query('/system/resource/print');
                return $client->query($query)->read();
            } catch (\Exception $e) {
                \Log::warning("Gagal ambil informasi router: " . $e->getMessage());
                return [['error' => 'Router info not available']];
            }
        });

        // 2. Caching ppp active users
        $onlineUsers = Cache::remember("ppp_active_{$id}", 30, function () use ($client) {
            try {
                $pppActiveQuery = new Query('/ppp/active/print');
                $pppActive = $client->query($pppActiveQuery)->read();
                return collect($pppActive)->pluck('name')->toArray();
            } catch (\Exception $e) {
                \Log::warning("Gagal ambil ppp active: " . $e->getMessage());
                return [];
            }
        });
        $pppActiveCount = count($onlineUsers);

        // 3. Caching ppp users
        $pppData = Cache::remember("ppp_users_{$id}", 30, function () use ($client, $onlineUsers) {
            $disabled = [];
            $offline = [];
            $online = [];
            $pppUserCount = 0;

            try {
                $pppUserQuery = new Query('/ppp/secret/print');
                $pppUsers = $client->query($pppUserQuery)->read();
                $pppUserCount = count($pppUsers);

                foreach ($pppUsers as $user) {
                    $userInfo = $user['name'] . ' - ' . ($user['comment'] ?? 'No Description');

                    if (isset($user['disabled']) && $user['disabled'] == 'true') {
                        $disabled[] = $userInfo;
                    } elseif (in_array($user['name'], $onlineUsers)) {
                        $online[] = $userInfo;
                    } else {
                        $offline[] = $userInfo;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Gagal ambil daftar user PPPoE: " . $e->getMessage());
            }

            return [
                'pppUserCount' => $pppUserCount,
                'online' => $online,
                'offline' => $offline,
                'disabled' => $disabled,
            ];
        });

        // Set hasil akhir
        $pppUserCount = $pppData['pppUserCount'];
        $online = $pppData['online'];
        $offline = $pppData['offline'];
        $disabled = $pppData['disabled'];
        $pppOfflineCount = count($offline);
        $pppDisabledCount = count($disabled);

        return response()->json([
            'success' => true,
            'routerInfo' => $routerInfo,
            'pppActiveCount' => $pppActiveCount,
            'pppUserCount' => $pppUserCount,
            'onlineUsers' => $online,
            'offlineUsers' => $offline,
            'disabledUsers' => $disabled,
            'pppOfflineCount' => $pppOfflineCount,
            'pppDisabledCount' => $pppDisabledCount,
        ]);

    } catch (\Exception $ex) {
        \Log::error("MikroTik API Error: " . $ex->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Router tidak bisa diakses',
            'error' => $ex->getMessage()
        ], 500);
    }
}


public function show($id)
{
   // Temukan Olt berdasarkan ID
    $distrouter = \App\Distrouter::findOrFail($id);
    $count_user = \App\Customer::where('id_distrouter', $distrouter->id)
    ->where('id_status', '!=', 0)
    ->count();


        // Tampilkan halaman dengan informasi dasar distrouter, AJAX akan mengambil detail lainnya
    return view('distrouter.show', ['distrouter' => $distrouter, 'count_user' => $count_user]);

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
       // dd("tst");
        return view ('distrouter.edit',['distrouter' => \App\Distrouter::findOrFail($id)]);
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
        //


         //
      //  dd($request);

        $validatedData = $request->validate([
       // 'name' => ['required', 'string', 'max:255', 'unique:distrouters,name'], // Corrected the 'unique' rule to target the 'olts' table and 'name' column
        'ip' => 'required|ip', // Added IP validation for the 'ip' field
        'port' => 'required|integer|min:1|max:65535', // Added integer validation and port range
        'web' => 'required|integer|min:1|max:65535', // Added integer validation and port range
        'user' => 'required|string|max:255', // Added string validation and max length for 'user'
        'password' => 'required|string|max:255', // Added string validation and max length for 'password'
        'note' => 'required|string|max:255', // Added string validation and max length for 'password'
        
    ]);
        \App\Distrouter::where('id', $id)
        ->update([
           'ip' => $validatedData['ip'],
           'port' => $validatedData['port'],
           'web' => $validatedData['web'],
           'user' => $validatedData['user'],
           'password' => $validatedData['password'],
           'note' => $validatedData['note'],

            'updated_at' => now(), // Use current timestamp for created_at

        ]);
        return redirect ('/distrouter')->with('success','Item updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        \App\Distrouter::destroy($id);
        return redirect ('/distrouter')->with('success','Item deleted successfully!');
    }

    // public function client_monitor($ip,$user,$pass,$port,$cid)
    public function client_monitor(Request $request)
    {
        $result = 'unknow';


        try {

            $client = new Client([


                'host' => $request->ip,
                'user' => $request->user,
                'pass' => $request->password,
                // 'port' => intval($request->port)
                'port' => $request->filled('port') ? intval($request->port) : 8728,
            ]);



            $query =
            (new Query('/interface/monitor-traffic'))
            ->equal('interface',$request->interface)
            ->equal('once');
            $rows = array(); $rows2 = array();

            $getinterfacetraffic= $client->query($query)->read();
            $ftx = $getinterfacetraffic[0]['tx-bits-per-second'];
            $frx = $getinterfacetraffic[0]['rx-bits-per-second'];

            $rows['name'] = 'Tx';
            $rows['data'][] = $ftx;
            $rows2['name'] = 'Rx';
            $rows2['data'][] = $frx;
// Ask for monitoring details
            $result = array();

            array_push($result,$rows);
            array_push($result,$rows2);
            print json_encode($result);



        }


        // catch (Exception $ex) {
        //     $result = 'Unknow';
        // }

        catch (\RouterOS\Exceptions\ConnectException $ex) {
            $result = 'Connection Timeout';
        } catch (\Exception $ex) {
            $result = 'Unknown Error';
        }




        

    }

    public function getMikrotikLogs($id)
    {
        try {
            $distrouter = \App\Distrouter::findOrFail($id);

            $client = new Client([
                'host' => $distrouter->ip,
                'user' => $distrouter->user,
                'pass' => $distrouter->password,
                'port' => $distrouter->port,
                'timeout' => 5,
            ]);

        // Ambil log dari MikroTik
            $logQuery = new Query('/log/print');


            $logs = $client->query($logQuery)->read();

            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);
        } catch (\Exception $ex) {
            \Log::error("MikroTik API Error: " . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching logs from RouterOS',
                'error' => $ex->getMessage()
            ], 500);
        }
    }


    public function interfacemonitor($id, Request $request)
    {
        $interface = $request->get('interface');

        try {
            $distrouter = \App\Distrouter::findOrFail($id);

            $client = new Client([
                'host' => $distrouter->ip,
                'user' => $distrouter->user,
                'pass' => $distrouter->password,
                'port' => $distrouter->port,
            ]);

        // Coba koneksi dulu, supaya kalau gagal langsung tertangkap
            $client->connect();

            $query = (new Query('/interface/monitor-traffic'))
            ->equal('interface', $interface)
            ->equal('.proplist', 'rx-bits-per-second,tx-bits-per-second')
            ->equal('once', '');

            $response = $client->query($query)->read();

            if (isset($response[0])) {
                $ftx = $response[0]['tx-bits-per-second'] ?? 0;
                $frx = $response[0]['rx-bits-per-second'] ?? 0;

                return response()->json([
                    ['name' => 'Tx', 'data' => [$ftx]],
                    ['name' => 'Rx', 'data' => [$frx]]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No data received from RouterOS',
            ]);

        } catch (\Exception $e) {
        // Tangani error agar tidak membuat crash
            \Log::channel('mikrotik')->error('Gagal ambil traffic: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengambil data Mikrotik',
                'tx' => 0,
                'rx' => 0
            ]);
        }
    }




}
