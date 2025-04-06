<?php

namespace App\Http\Controllers;
use \Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Exception\GuzzleException;
Use GuzzleHttp\Clients;
use \Carbon\Carbon;
use DataTables;

class WhatsappController extends Controller
{
 public function __construct()
 {
    $this->middleware('auth');
      $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,vendor,merchant'); // Daftar privilege
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
