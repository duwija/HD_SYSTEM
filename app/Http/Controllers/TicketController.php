<?php

namespace App\Http\Controllers;

use App\Tiket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \Auth;
use \RouterOS\Client;
use \RouterOS\Query;
Use GuzzleHttp\Clients;
use Exception;
use PDF;
use \Carbon\Carbon;
use DataTables;
use App\Helpers\WaGatewayHelper;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TicketController extends Controller
{
 public function __construct()
 {
    $this->middleware('auth');
     $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,marketing,vendor'); // Daftar privilege
 }

    /**
     * Display a listing of the resource.

     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $from=date('Y-m-1');
       $to=date('y-m-d');
       $ticket = \App\Ticket::orderBy('id', 'DESC')
       ->whereBetween('date', [$from, $to])
       ->get();
       $ticketcategorie = \App\Ticketcategorie::pluck('name', 'id');
       $tags = \App\Tag::pluck('name', 'id');
       $user = \App\User::where('privilege', '!=', 'counter')->pluck('name', 'id');

       return view ('ticket/index',['ticket' =>$ticket, 'ticketcategorie' =>$ticketcategorie, 'user'=>$user, 'tags'=>$tags]);


   }

   public function groupticket()
   {
       $from=date('Y-m-1');
       $to=date('y-m-d');
       $ticket = \App\Ticket::orderBy('id', 'DESC')
       ->whereBetween('date', [$from, $to])
       ->get();
       $ticketcategorie = \App\Ticketcategorie::pluck('name', 'id');


       $currentUser = Auth::user();
       $currentGroupIds = $currentUser->groups->pluck('id');

    // Cari semua user yang memiliki grup yang sama
       $users = \App\User::whereHas('groups', function ($query) use ($currentGroupIds) {
        $query->whereIn('groups.id', $currentGroupIds);
    })->pluck('name', 'id');


       return view ('ticket/groupticket',['ticket' =>$ticket, 'ticketcategorie' =>$ticketcategorie, 'user'=>$users]);


   }






   public function myticket()
   {
    $from=date('Y-m-1');
    $to=date('y-m-d');

    $id = Auth::user()->id;
    $ticket = \App\Ticket::orderBy('id', 'DESC')
    ->where('assign_to', $id)
    ->whereBetween('date', [$from, $to])
    ->get();


    return view ('ticket/myticket',['ticket' =>$ticket, 'title'=> 'Ticket List | My Ticket']);
}

public function table_myticket_list(Request $request){
    $user = Auth::user()->id;

    $date_from = $request->input('date_from');
    $date_end = $request->input('date_end');
    $id_status = $request->input('id_status');



    $ticket = \App\Ticket::whereBetween('date', [$date_from, $date_end]);

    if (!empty($id_status)) {
        $ticket->where('status', $id_status);
    }

    $ticket->where('assign_to', $user);
// Order the results
    $ticket->orderBy('date', 'DESC');
    $ticket->orderBy('time', 'DESC');
    $results = $ticket->get();

    

    $total = $results->count();
    $open = $results->where('status', 'Open')->count();
    $close = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve = $results->where('status', 'Solve')->count();
    $pending = $results->where('status', 'pending')->count();


// Return the results using DataTables
    return DataTables::of($results)
    ->addIndexColumn()
    ->editColumn('id',function($ticket)
    {

        return ' <a href="/ticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center  "> '.$ticket->id. '</a>';
    })


    ->editColumn('status',function($ticket)
    {



        if ($ticket->status == "Open")

        {
          $color='bg-danger'; 
          $btn_c='bg-danger'; }


          elseif ($ticket->status == "Close")
            {$color='bg-secondary'; 
        $btn_c='bg-secondary'; }
        elseif ($ticket->status == "Pending")
          {  $color='bg-warning'; 
      $btn_c='bg-warning'; }
      elseif ($ticket->status == "Solve")
          {  $color='bg-info'; 
      $btn_c='bg-info'; }
      else
         {  $color='bg-primary'; 
     $btn_c='bg-primary'; }
     return '<badge class=" badge '. $btn_c. '"<a>'.$ticket->status. '</a>';
 })





    ->editColumn('id_customer',function($ticket)
    {



        return ' <a href="/customer/'.$ticket->id_customer.'" title="ticket" class="badge p-1 badge-success text-center  "> '.$ticket->customer->name. '</a>';
    })
    ->editColumn('id_categori',function($ticket)
    {

       return '<a>'.$ticket->categorie->name. '</a>';
   })
    ->editColumn('assign_to',function($ticket)
    {

       return '<a>'.$ticket->user->name. '</a>';
   })
    ->editColumn('date',function($ticket)
    {

       return '<a>'.$ticket->date. ' '. $ticket->time.'</a>';
   })




    ->rawColumns(['DT_RowIndex','id','id_customer','status','id_categori','tittle','assign_to','date'])
    ->with('total', $total)
    ->with('open', $open)
    ->with('close', $close)
    ->with('inprogress', $inprogress)
    ->with('solve', $solve)
    ->with('pending', $pending)

    ->make(true);
}

public function view($id)
{


    $ticket = \App\Ticket::orderBy('id', 'DESC')
    ->where('id_customer','=', $id)
    ->get();


    return view ('ticket/view',['ticket' =>$ticket]);



}
public function table_ticket_list(Request $request){


    $date_from = $request->input('date_from');
    $date_end = $request->input('date_end');
    $id_categori = $request->input('id_categori');
    $assign_to = $request->input('assign_to');
    $id_status = $request->input('id_status');
    $ticketid = $request->input('ticketid');
    $title = $request->input('title');
    $tags = $request->input('tags', []);

// Initialize the query
    $ticket = \App\Ticket::whereBetween('date', [$date_from, $date_end]);

// Apply filters based on input
    if (!empty($id_categori)) {
        $ticket->where('id_categori', $id_categori);
    }

    if (!empty($assign_to)) {
        $ticket->where('assign_to', $assign_to);
    }

    if (!empty($id_status)) {
        $ticket->where('status', $id_status);
    }
    if (!empty($ticketid)) {
        $ticket->where('id', $ticketid);
    }
    if (!empty($title)) {
        $ticket->where('tittle', 'like', "%{$title}%");
    }


    if (!empty($tags) && is_array($tags)) {
        $ticket->whereHas('tags', function ($query) use ($tags) {
            $query->whereIn('tags.id', $tags);
        });
    }

// Order the results
    $ticket->orderBy('id', 'DESC');

// Get the results
    $results = $ticket->get();



    $total = $results->count();
    $open = $results->where('status', 'Open')->count();
    $close = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve = $results->where('status', 'Solve')->count();
    $pending = $results->where('status', 'pending')->count();


// Return the results using DataTables
    return DataTables::of($results)
    ->addIndexColumn()
    ->editColumn('id',function($ticket)
    {

        return ' <a href="/ticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center  "> '.$ticket->id. '</a>';
    })


    ->editColumn('status',function($ticket)
    {



        if ($ticket->status == "Open")

        {
          $color='bg-danger'; 
          $btn_c='bg-danger'; }


          elseif ($ticket->status == "Close")
            {$color='bg-secondary'; 
        $btn_c='bg-secondary'; }
        elseif ($ticket->status == "Pending")
          {  $color='bg-warning'; 
      $btn_c='bg-warning'; }
      elseif ($ticket->status == "Solve")
          {  $color='bg-info'; 
      $btn_c='bg-info'; }
      else
         {  $color='bg-primary'; 
     $btn_c='bg-primary'; }
     return '<badge class=" badge '. $btn_c. '"<a>'.$ticket->status. '</a>';
 })





    ->editColumn('id_customer',function($ticket)
    {



        return ' <a href="/customer/'.$ticket->id_customer.'" title="ticket" class="badge p-1 badge-success text-center  "> '.$ticket->customer->name. '</a>';
    })
    ->addColumn('merchant', function($ticket) {
    // Ambil dulu objek customer (bisa null)
        $cust = $ticket->customer;

    // Dari customer ambil relasi merchant_name (bisa null juga)
        $m = optional($cust)->merchant_name;

        if ($m) {
        // Kedua relasi pasti ada, aman untuk akses id & name
            return sprintf(
                '<a href="/merchant/%d" class="badge p-1 badge-primary text-center">%s</a>',
                $m->id,
                e($m->name)
            );
        }

    // Jika customer atau merchant_name null, tampilkan placeholder
        return '<span class="text-muted">–</span>';
    })

    ->addColumn('address',function($ticket)
    {



        return $ticket->customer->address;
    })
    ->editColumn('id_categori',function($ticket)
    {

       return '<a>'.$ticket->categorie->name. '</a>';
   })
    ->addColumn('tags', function($ticket) {
    // Mengambil tags yang terkait dengan tiket dan menampilkannya dalam badge
    $tags = $ticket->tags->pluck('name')->toArray(); // Ambil nama-nama tag
    $tagBadges = '';

    foreach ($tags as $tag) {
        // Membuat badge untuk setiap tag
        $tagBadges .= '<span class="badge badge-info">' . $tag . '</span> ';
    }

    return $tagBadges;
})
    ->editColumn('assign_to',function($ticket)
    {

       return '<a>'.$ticket->user->name. '</a>';
   })
    ->editColumn('date',function($ticket)
    {

       return '<a>'.$ticket->date. ' '. $ticket->time.'</a>';
   })

    ->editColumn('created_at',function($ticket)
    {
        $formattedDate = Carbon::parse($ticket->created_at)->format('Y-m-d H:i:s');
        $humanFormat =Carbon::parse($formattedDate)->diffForHumans(); 

        return '<a>'.$humanFormat. '</br>'.$formattedDate.'</a>';
    })

    ->editColumn('solved_at',function($ticket)
    {
     $solvedDate = $ticket->solved_at 
     ? Carbon::parse($ticket->solved_at)->format('Y-m-d H:i:s') 
    : ''; // Nilai default jika null

    return '<a>'.$solvedDate.'</a>';
})



    ->rawColumns(['DT_RowIndex','id','id_customer','address','merchant','status','id_categori','tittle','assign_to','date','created_at','solved_at', 'tags'])
    ->with('total', $total)
    ->with('open', $open)
    ->with('close', $close)
    ->with('inprogress', $inprogress)
    ->with('solve', $solve)
    ->with('pending', $pending)

    ->make(true);
}



public function table_groupticket_list(Request $request){

    $date_from = $request->input('date_from');
    $date_end = $request->input('date_end');
    $id_categori = $request->input('id_categori');
    $assign_to = $request->input('assign_to');
    $id_status = $request->input('id_status');


 // Dapatkan grup dari pengguna aktif
    $currentUser = Auth::user();
    $currentGroupIds = $currentUser->groups->pluck('id'); // Grup pengguna aktif

    // Inisialisasi query
    $ticket = \App\Ticket::whereBetween('date', [$date_from, $date_end])
    ->whereHas('assignToUser.groups', function ($query) use ($currentGroupIds) {
            $query->whereIn('groups.id', $currentGroupIds); // Grup harus sama
        });

// Apply filters based on input
    if (!empty($id_categori)) {
        $ticket->where('id_categori', $id_categori);
    }

    if (!empty($assign_to)) {
        $ticket->where('assign_to', $assign_to);
    }

    if (!empty($id_status)) {
        $ticket->where('status', $id_status);
    }

// Order the results
    $ticket->orderBy('id', 'DESC');

// Get the results
    $results = $ticket->get();



    $total = $results->count();
    $open = $results->where('status', 'Open')->count();
    $close = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve = $results->where('status', 'Solve')->count();
    $pending = $results->where('status', 'pending')->count();


// Return the results using DataTables
    return DataTables::of($results)
    ->addIndexColumn()
    ->editColumn('id',function($ticket)
    {

        return ' <a href="/ticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center  "> '.$ticket->id. '</a>';
    })

    ->addColumn('merchant',function($ticket)
    {



        return ' <a href="/merchant/'.$ticket->customer->merchant_name->id.'" title="ticket" class="badge p-1 badge-primary text-center  "> '.$ticket->customer->merchant_name->name. '</a>';
    })
    ->editColumn('status',function($ticket)
    {



        if ($ticket->status == "Open")

        {
          $color='bg-danger'; 
          $btn_c='bg-danger'; }


          elseif ($ticket->status == "Close")
            {$color='bg-secondary'; 
        $btn_c='bg-secondary'; }
        elseif ($ticket->status == "Pending")
          {  $color='bg-warning'; 
      $btn_c='bg-warning'; }
      elseif ($ticket->status == "olve")
          {  $color='bg-info'; 
      $btn_c='bg-info'; }
      else
         {  $color='bg-primary'; 
     $btn_c='bg-primary'; }
     return '<badge class=" badge '. $btn_c. '"<a>'.$ticket->status. '</a>';
 })





    ->editColumn('id_customer',function($ticket)
    {



        return ' <a href="/customer/'.$ticket->id_customer.'" title="ticket" class="badge p-1 badge-success text-center  "> '.$ticket->customer->name. '</a>';
    })
    ->editColumn('id_categori',function($ticket)
    {

       return '<a>'.$ticket->categorie->name. '</a>';
   })
    ->editColumn('assign_to',function($ticket)
    {

       return '<a>'.$ticket->user->name. '</a>';
   })
    ->editColumn('date',function($ticket)
    {

       return '<a>'.$ticket->date. ' '. $ticket->time.'</a>';
   })

    ->editColumn('created_at',function($ticket)
    {
        $formattedDate = Carbon::parse($ticket->created_at)->format('Y-m-d H:i:s');
        $humanFormat =Carbon::parse($formattedDate)->diffForHumans(); 

        return '<a>'.$humanFormat. '</br>'.$formattedDate.'</a>';
    })

    ->editColumn('solved_at',function($ticket)
    {
        $solvedDate = $ticket->solved_at 
        ? Carbon::parse($ticket->solved_at)->format('Y-m-d H:i:s') 
    : ''; // Nilai default jika null

    return '<a>'.$solvedDate.'</a>';
})


    ->rawColumns(['DT_RowIndex','id','id_customer','status','id_categori','tittle','assign_to','date','merchant','created_at','solved_at'])
    ->with('total', $total)
    ->with('open', $open)
    ->with('close', $close)
    ->with('inprogress', $inprogress)
    ->with('solve', $solve)
    ->with('pending', $pending)

    ->make(true);
}


public function report(Request $request)
{
 if (($request->date_from == null) or ($request->date_end == null))
 {
   $from=date('Y-m-1');
   $to=date('y-m-d');

}
else
{
   $from=$request->date_from;
   $to=$request->date_end;

}


$ticket_report = \App\Ticket::Join('ticketcategories', 'tickets.id_categori', '=', 'ticketcategories.id')
->whereBetween('tickets.date', [$from, $to])
->groupBy('id_categori')
->select('tickets.id_categori as categori','ticketcategories.name as name', DB::raw("count(tickets.id_categori) as count"))->get();

$ticket_date = \App\Ticket::whereBetween('tickets.date', [$from, $to])
->groupBy('date')
->select('date', DB::raw("count(date) as countdate"))->get();

$ticket_customer = \App\Ticket::Join('customers', 'tickets.id_customer', '=', 'customers.id')
->whereBetween('tickets.date', [$from, $to])
->groupBy('id_customer')
->select('customers.id as cust_id','customers.name as name', DB::raw("count(tickets.id_customer) as count"))
->orderBy('count', 'DESC')
->limit(10)
->get();


return view ('ticket/report',['ticket_report' =>$ticket_report, 'ticket_customer' => $ticket_customer, 'date_from' =>$from, 'date_end' =>$to, 'ticket_date' => $ticket_date ]);



}

public function search(Request $request)
{
   $date_from = ($request['date_from']);
   $date_end = ($request['date_end']);

   $ticket = \App\Ticket::orderBy('id', 'DESC')
   ->whereBetween('date',[($request['date_from']), ($request['date_end'])])
   ->get();


   return view ('ticket/index',['ticket' =>$ticket, 'date_from' =>$date_from, 'date_end'  =>$date_end, 'ticket_date']);



}




public function uncloseticket()
{
    $id = Auth::user()->id;
    $ticket = \App\Ticket::orderBy('id', 'DESC')
    ->where('assign_to','=', $id)
    ->where('status','!=', 'Close')
    ->get();


    return view ('ticket/myticket',['ticket' =>$ticket, 'title'=>'Ticket List | My UnClose Ticket']);
}


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {

        $category = \App\Ticketcategorie::pluck('name', 'id');
        $user= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');
        $status = \App\Statuscustomer::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        $plan = \App\Plan::pluck('name', 'id');
        $customer_coordinate = \App\Customer::where('id', $id)->pluck('coordinate');
        $tags = \App\Tag::pluck('name', 'id');

        $customer = \App\Customer::where('customers.id', $id)
        ->Join('distpoints', 'customers.id_distpoint', '=', 'distpoints.id')
        ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
        ->Join('plans', 'customers.id_plan', '=', 'plans.id')
        ->select('customers.*','distpoints.name as distpoint_name','statuscustomers.name as status_name','plans.name as plan_name')->first();

        if ( $customer == null)
        {
            return abort(404);
        }
        else
        {

     //   dd($customer);
            if ($customer_coordinate == null)

            {
                $customer_coordinate ='-8.471722, 115.289472';
            }



            $config['center'] = $customer_coordinate;
            $config['zoom'] = '13';
//$this->googlemaps->initialize($config);

            $marker = array();
            $marker['position'] =$customer_coordinate;
            $marker['draggable'] = true;
            $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

            app('map')->initialize($config);

            app('map')->add_marker($marker);
            $map = app('map')->create_map();


            return view ('ticket/create',['customer' => $customer, 'map' => $map, 'status' => $status, 'distpoint'=>$distpoint, 'plan' => $plan, 'category'=>$category, 'user'=>$user , 'tags'=>$tags ] );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

       // dd ($request);
        $client = new Clients();

        $request ->validate([

            'id_customer' => 'required',
            'called_by' => 'required',
            'phone' => 'required',
            'status' => 'required',
            'id_categori' => 'required',
            'tittle'  => 'required',
            'description' => 'required',
            'assign_to' => 'required',
            'date' => 'required',
            'time' => 'required',
         'tags' => 'nullable|array', // Validasi untuk tags (boleh kosong)
     ]);




        $member = $request->input('member');
        if ($member == null)
        {
            $member ="";
        }
        else{



            $member = implode(',', $member);
        }
      //  dd ($member);
        $description=$request->input('description');
        $dom = new \DomDocument();
        $dom->loadHtml($description, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);    
        $images = $dom->getElementsByTagName('img');

        foreach($images as $key => $img){
            $data = $img->getAttribute('src');

            list($type, $data) = explode(';', $data);
            list(, $data)      = explode(',', $data);
            $data = base64_decode($data);

            $image_name= "/upload/ticket/" . time().$key.'.png';
            $path = public_path() . $image_name;

            file_put_contents($path, $data);

            $img->removeAttribute('src');
            $img->setAttribute('src', $image_name);
        }

        $description = $dom->saveHTML();
        $newTicket = \App\Ticket::create([
            'id_customer' => ($request['id_customer']),
            'called_by' => ($request['called_by']),
            'phone' => ($request['phone']), 
            'status' => ($request['status']),
            'id_categori' => ($request['id_categori']),
            'tittle' => ($request['tittle']),
            // 'description' => ($request['description']),
            'description' => $description,
            'assign_to' => ($request['assign_to']),
            'member' => ($member),
            'date' => ($request['date']),
            'time' => ($request['time']),
            'create_by' => ($request['create_by']),
            'updated_at' => ($request['created_at']),




        ]);
        if ($request->has('tags')) {
            $newTicket->tags()->sync($request->tags);

        }
        $customer = \App\Customer::findOrFail($request['id_customer']);


        if (env('WAPISENDER_STATUS')!="disable")
        {


         $message = "[NEW TICKET]";
         $message .= "\n\nHalo Tim,";
         $message .= "\n\nSebuah tiket baru telah dibuat dengan detail berikut:";
         $message .= "\n━━━━━━━━━━━━━━━";
         $message .= "\nJudul: " . $request['tittle'];
         $message .= "\nNama Pelanggan: " . $customer->name;
         $message .= "\nNomor HP: " . $customer->phone;
         $message .= "\nAlamat: " . $customer->address;
         $message .= "\n━━━━━━━━━━━━━━━";

         $message .= "\n\n🔗 Untuk melihat detail tiket, silakan klik tautan berikut:";
         $message .= "\n" . "https://" . env('DOMAIN_NAME') . "/ticket/" . $newTicket->id;
         $message .= "\n\n📌 Tiket ini dibuat oleh: *" . \Auth::user()->name . "*";
         $message .= "\n\nTerima kasih! Jika ada yang perlu dikonfirmasi, silakan koordinasikan lebih lanjut.";
         $message .= "\n\n~ " . env("SIGNATURE") . "~";


    //      $result_g = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
    //         'form_params' => [
    //             'token' => env('WAPISENDER_KEY'),
    //             'number' =>env('WAPISENDER_GROUPTICKET'),
    //             'message' =>$message,
    //         ]
    //     ]);


    //      $result_g= $result_g->getBody();
    //      $array = json_decode($result_g, true);
    //  // $process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_group.py", 
    //  //    env("TELEGRAM_GROUP_SUPPORT"), $message]);
    //      try {
    //         // Menjalankan proses
    //         $process->run();

    //         // Memeriksa apakah proses berhasil
    //         if (!$process->isSuccessful()) {
    //             throw new ProcessFailedException($process);
    //         }

    //         // Mendapatkan output dari proses
    //         $output = $process->getOutput();

    //         return redirect ('/ticket/view/'.$request['id_customer'])->with('success', $output);
    //     } catch (ProcessFailedException $e) {
    //         // Jika proses gagal, kembalikan pesan kesalahan
    //         $errorMessage = $e->getMessage();
    //         return redirect()->back()->with('error', $errorMessage);
    //     }

    // }


         return redirect ('/ticket/view/'.$request['id_customer'])->with('success','Item created successfully!');
     }


 }


    /**
     * Display the specified resource.
     *
     * @param  \App\Tiket  $tiket
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ticket = \App\Ticket::findOrFail($id);

        $category = \App\Ticketcategorie::pluck('name', 'id');
      //  $user= \App\User::pluck('name', 'id');

        $distpoint = \App\Distpoint::pluck('name', 'id');
        $sale = \App\Sale::pluck('name', 'id', 'phone');
        $plan = \App\Plan::pluck('name', 'id');
        $user = \App\User::pluck('name', 'id');
       // $users = \App\User::pluck('name', 'id');
        $users= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');
        // Ambil tag ID dan nama agar bisa dipilih di select box
        $tags = $ticket->tags->pluck('name', 'id')->toArray();
// Ambil semua tag dengan ID dan name agar bisa dipakai di form select
$alltags = \App\Tag::pluck('name', 'id'); // Perbaikan: Ambil ID juga, bukan hanya nama


$user = Auth::user()->privilege;

if ($user == "vendor")
{
   return view ('ticket/vendorshow',['ticket' => $ticket, 'distpoint'=>$distpoint,'user'=>$user,'users'=>$users, 'plan' => $plan, 'category'=>$category, 'sale'=>$sale ] );
}
else
{
    return view ('ticket/show',['ticket' => $ticket, 'distpoint'=>$distpoint,'user'=>$user,'users'=>$users, 'plan' => $plan, 'category'=>$category, 'sale'=>$sale,'tags'=>$tags, 'alltags'=>$alltags ] );
}


     //return view ('ticket/show',['ticket' => $ticket] );
}


    // public function vendorshow($id)
    // {
    //     $ticket = \App\Ticket::findOrFail($id);

    //     $category = \App\Ticketcategorie::pluck('name', 'id');
    //   //  $user= \App\User::pluck('name', 'id');

    //     $distpoint = \App\Distpoint::pluck('name', 'id');
    //     $sale = \App\Sale::pluck('name', 'id', 'phone');
    //     $plan = \App\Plan::pluck('name', 'id');
    //     $user = \App\User::pluck('name', 'id');
    //    // $users = \App\User::pluck('name', 'id');
    //     $users= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');




    //     return view ('ticket/vendorshow',['ticket' => $ticket, 'distpoint'=>$distpoint,'user'=>$user,'users'=>$users, 'plan' => $plan, 'category'=>$category, 'sale'=>$sale ] );
    //  //return view ('ticket/show',['ticket' => $ticket] );
    // }

public function print_ticket($id)
{
    $ticket = \App\Ticket::findOrFail($id);

    $category = \App\Ticketcategorie::pluck('name', 'id');
    $user= \App\User::pluck('name', 'id');

    $distpoint = \App\Distpoint::pluck('name', 'id');

    $plan = \App\Plan::pluck('name', 'id');
    $user = \App\User::pluck('name', 'id');
    $users = \App\User::pluck('name', 'id');


    $pdf = PDF::loadview('pdf',['ticket' => $ticket, 'distpoint'=>$distpoint,'user'=>$user,'users'=>$users, 'plan' => $plan, 'category'=>$category ] );
    return $pdf->download('Ticket-pdf'.$id);


     //return view ('ticket/show',['ticket' => $ticket] );
}



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Tiket  $tiket
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
     $ticket = \App\Ticket::findOrFail($id);
     $customer = \App\Customer::findOrFail($ticket->id_customer);

     $category = \App\Ticketcategorie::pluck('name', 'id');
      //  $user= \App\User::pluck('name', 'id');

     $distpoint = \App\Distpoint::pluck('name', 'id');

     $plan = \App\Plan::pluck('name', 'id');
       // $user = \App\User::pluck('name', 'id');
     $user= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');
     return view ('ticket/edit',['ticket' => $ticket, 'customer' =>$customer, 'distpoint'=>$distpoint,'user'=>$user, 'plan' => $plan, 'category'=>$category ] );

 }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Tiket  $tiket
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tiket $tiket)
    {
        //
    }

    public function vendoreditticket(Request $request, $id)
    {
     $solved_at = null;
     $member = $request->input('member');
     if ($member == null)
     {
        $member ="";
    }
    else{



        $member = implode(',', $member);
    }
    if ($request->status == 'Close')
    {

      $ticket = \App\Ticket::where('id', $id)->first();

      if( $ticket->status != 'close' )
      {
         if (env('WAPISENDER_STATUS')!="disable")
         {

                 // $message .= "\nOpen this  url to show the update : http://".env('DOMAIN_NAME')."/ticket/".$ticket->id;

           $message = "[TICKET CLOSED]";
           $message .= "\n\nTerima Kasih " . \Auth::user()->name . ",";
           $message .= "\n\nKami ingin menginformasikan bahwa tiket berikut telah di CLOSE:";
           $message .= "\n━━━━━━━━━━━━━━━";
           $message .= "\nJudul Tiket: " . $ticket->tittle;
           $message .= "\nNama Pelanggan: " . $ticket->customer->name;
           $message .= "\nNomor HP: " . $ticket->customer->phone;
           $message .= "\nAlamat: " . $ticket->customer->address;
           $message .= "\n━━━━━━━━━━━━━━━";

           $message .= "\n\nUntuk informasi lebih lanjut, silakan kunjungi tautan berikut:";
           $message .= "\nhttps://".env('DOMAIN_NAME')."/ticket/".$ticket->id;
           $message .= "\n\nSalam 😊";
           $message .= "\n\n~ " . env("SIGNATURE") . "~";

           $msgresult = WaGatewayHelper::wa_payment(env("WA_GROUP_VENDOR"), $message);

           if (isset($msgresult['status']) && $msgresult['status'] === 'success') {
            return redirect()
            ->back()
            ->with('success', $msgresult['message']);
        }

        return redirect()
        ->back()
        ->with('error', $msgresult['message']);

        //  $process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_group.py", 
        //     env("TELEGRAM_GROUP_SUPPORT"), $message]);
        //  try {
        //     // Menjalankan proses
        //     $process->run();

        //     // Memeriksa apakah proses berhasil
        //     if (!$process->isSuccessful()) {
        //         throw new ProcessFailedException($process);
        //     }

        //     // Mendapatkan output dari proses
        //     $output = $process->getOutput();

        //   //  return redirect ('/ticket/view/'.$request['id_customer'])->with('success', $output);
        // } catch (ProcessFailedException $e) {
        //     // Jika proses gagal, kembalikan pesan kesalahan
        //     $errorMessage = $e->getMessage();
        //   //  return redirect()->back()->with('error', $errorMessage);
        // }

    }

}



}


elseif ($request->status == 'Solve')
{
    $solved_at = Carbon::now();

}

\App\Ticket::where('id', $id)
->update([
    // 'tittle' => $request->tittle,
    'status' => $request->status,
    'id_categori' => $request->category,
    'assign_to' => ($request['assign_to']),
    'member' => ($member),
    'date' => ($request['date']),
    'time' => ($request['time']),
    'solved_at' => ($solved_at),



]);

 // Record changes
$note = "";
// if ($ticket->tittle != $request->tittle) {
//     $note .= 'tittle changed from ' .$ticket->tittle. ' to ' .$request->tittle ;
// }

if ($ticket->status != $request->status) {
    $note .= '<p> Status changed from ' .$ticket->status. ' to ' .$request->status.'</p>' ;
}

if($ticket->assign_to != $request['assign_to'])
{
    $newAssignee = $request['assign_to'] ? \App\User::find($request['assign_to']) : null;
    $newAssigneeName = $newAssignee ? $newAssignee->name : 'Unassigned';

    $note .= "<p>Assigned changed  to " . $newAssigneeName . '</p>';
}



if (!empty($note))
{

    \App\Ticketdetail::create([
            'id_ticket' => $id, // Assuming `id_ticket` is the ticket ID
            'description' => $note,    // Assuming a `note` field exists in the `Ticketdetail` model
            'updated_by' => \Auth::user()->name,
        ]);


}

$url ='/vendorticket/'.$request->id;
return redirect ($url)->with('success','Item updated successfully!. ');
}

public function editticket(Request $request, $id)
{

    $ticket = \App\Ticket::where('id', $id)->first();
    $member = $request->input('member');
    $solved_at = null;
    if ($member == null)
    {
        $member ="";
    }
    else{



        $member = implode(',', $member);
    }

    if ($request->status == 'Close')
    {



      if( $ticket->status != 'close' )
      {
         if (env('WAPISENDER_STATUS')!="disable")
         {


            $message = "Terima Kasih " . \Auth::user()->name . ",";
            $message .= "\n\nKami ingin menginformasikan bahwa tiket berikut telah di CLOSE";
            $message .= "\n━━━━━━━━━━━━━━━";
            $message .= "\nJudul Tiket: " . $ticket->tittle;
            $message .= "\nNama Pelanggan: " . $ticket->customer->name;
            $message .= "\nNomor HP: " . $ticket->customer->phone;
            $message .= "\nAlamat: " . $ticket->customer->address;
            $message .= "\n━━━━━━━━━━━━━━━";

            $message .= "\n\nUntuk informasi lebih lanjut, silakan kunjungi tautan berikut:";
            $message .= "\n https://".env('DOMAIN_NAME')."/ticket/".$ticket->id;
            $message .= "\n\nSalam  😊";
            $message .= "\n\n~ " . env("SIGNATURE") . "~";

            // $msgresult = WaGatewayHelper::wa_payment(env("WA_GROUP_SUPPORT"), $message);

            // if (isset($msgresult['status']) && $msgresult['status'] === 'success') {
            //     return redirect()
            //     ->back()
            //     ->with('success', $msgresult['message']);
            // }

            // return redirect()
            // ->back()
            // ->with('error', $msgresult['message']);



            
        //  $result_g = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
        //     'form_params' => [
        //         'token' => env('WAPISENDER_KEY'),
        //         'number' =>env('WAPISENDER_GROUPTICKET'),
        //         'message' =>$message,
        //     ]
        // ]);


        //  $result_g= $result_g->getBody();
        //  $array = json_decode($result_g, true);

            // $process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_group.py", 
            //     env("TELEGRAM_GROUP_SUPPORT"), $message]);
        //     try {
        //     // Menjalankan proses
        //    // $process->run();

        //     // Memeriksa apakah proses berhasil
        //     // if (!$process->isSuccessful()) {
        //     //     throw new ProcessFailedException($process);
        //     // }

        //     // // Mendapatkan output dari proses
        //     // $output = $process->getOutput();

        //   //  return redirect ('/ticket/view/'.$request['id_customer'])->with('success', $output);
        //     } catch (ProcessFailedException $e) {
        //     // Jika proses gagal, kembalikan pesan kesalahan
        //         $errorMessage = $e->getMessage();
        //   //  return redirect()->back()->with('error', $errorMessage);
        //     }






        }

    }
}
elseif ($request->status == 'Solve')
{
    $solved_at = Carbon::now();

}



\App\Ticket::where('id', $id)
->update([
    'tittle' => $request->tittle,
    'status' => $request->status,
    'id_categori' => $request->category,
    'assign_to' => ($request['assign_to']),
    'member' => ($member),
    'date' => ($request['date']),
    'time' => ($request['time']),
    'solved_at' => ($solved_at),


]);

if ($request->has('tags')) {
    $ticket->tags()->sync($request->tags);
}

 // Record changes
$note = "";
if ($ticket->tittle != $request->tittle) {
    $note .= 'tittle changed from ' .$ticket->tittle. ' to ' .$request->tittle ;
}

if ($ticket->status != $request->status) {
    $note .= '<p> Status changed from ' .$ticket->status. ' to ' .$request->status.'</p>' ;
}

if($ticket->assign_to != $request['assign_to'])
{
    $newAssignee = $request['assign_to'] ? \App\User::find($request['assign_to']) : null;
    $newAssigneeName = $newAssignee ? $newAssignee->name : 'Unassigned';

    $note .= "<p>Assigned changed  to " . $newAssigneeName . '</p>';
}



if (!empty($note))
{

    \App\Ticketdetail::create([
            'id_ticket' => $id, // Assuming `id_ticket` is the ticket ID
            'description' => $note,    // Assuming a `note` field exists in the `Ticketdetail` model
            'updated_by' => \Auth::user()->name,
        ]);


}



$url ='/ticket/'.$request->id;
return redirect ($url)->with('success','Item updated successfully! ');
}

public function updateassign(Request $request, $id)
{
    // $member = $request->input('member');
    //     if ($member == null)
    //     {
    //         $member ="";
    //     }
    //     else{



    //     $member = implode(',', $member);
    // }

    //     \App\Ticket::where('id', $id)
    //         ->update([
    //          'assign_to' => ($request['assign_to']),
    //         'member' => ($member),


    //         ]);
    //         $url ='ticket/'.$request->id;
    //    return redirect ($url)->with('success','Item updated successfully!');
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Tiket  $tiket
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tiket $tiket)
    {
        //
    }


//     public function wa_ticket(Request $request)
//     {

//        if (env('WAPISENDER_STATUS')!="disable")
//        {
//         $client = new Clients();
//         $number = $request->phone;

//         if(substr(trim($number), 0, 2)=="62"){
//             $hp    =trim($number);
//         }
//             // cek apakah no hp karakter ke 1 adalah angka 0
//         else if(substr(trim($number), 0, 1)=="0"){
//             $hp    ="62".substr(trim($number), 1);
//         }

//         $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
//             'form_params' => [
//             // 'api_key' => env('WAPISENDER_KEY'),
//             // 'device_key' => $request->device,
//             // 'destination' => $hp,
//                'token' => env('WAPISENDER_KEY'),
//                'number' => $hp,
//                'message' =>$request->message,
//            ]
//        ]);

// //Kirim pesan ke group
//         $result= $result->getBody();
//         $array = json_decode($result, true);

//     // $result_g = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
//     //     'form_params' => [
//     //         'key' => env('WAPISENDER_KEY'),
//     //         'device' => $request->device,
//     //         'group_id' =>env('WAPISENDER_GROUPTICKET'),
//     //         'message' =>$request->message,
//     //     ]
//     // ]);


//     // $result_g= $result_g->getBody();
//     // $array = json_decode($result_g, true);
// //    return redirect ('/ticket/'.$request->id_ticket)->with('success','Message '.$array['status'].' - '.$array['message']); 
//         return redirect()->back()->with('success','Message '.$array['status']); 
//     }
//     else
//     {
//         return redirect()->back()->with('error','WA Disabled');
//     }

// }

    public function datamap()
    {
        $today = Carbon::today();

        $tickets = \App\Ticket::with('customer')
        ->whereDate('date', $today)
        ->get();

        $result = $tickets->map(function ($ticket) {
        // Ambil koordinat customer dalam format "latitude,longitude"
            $coordinates = $ticket->customer->coordinate;

        // Pisahkan koordinat menjadi array
            $coords = explode(',', $coordinates);

        // Kembalikan data yang diperlukan dalam array
            return [
                'id' => $ticket->id,
                'status' => $ticket->status,
                'description' => $ticket->tittle,
            'lat' => isset($coords[0]) ? $coords[0] : null, // Pastikan ada data lat
            'lng' => isset($coords[1]) ? $coords[1] : null, // Pastikan ada data lng
            'customer_name' => $ticket->customer->name,
            'assign_to' => $ticket->assignToUser->name,
            'date' => $ticket->date,
        ];
    });

        return response()->json($result);
    }



    // public function wa_ticket(Request $request)
    // {
    //     if (env('WAPISENDER_STATUS') != "disable") {
    //         $number = trim($request->phone);

    //         if (substr($number, 0, 2) == "62") {
    //             $hp = "+" . $number;
    //         } elseif (substr($number, 0, 1) == "0") {
    //             $hp = "+62" . substr($number, 1);
    //         } else {
    //             $hp = "+" . $number;
    //         }

    //         $phone = $hp;
    //         $message = $request->message;


    //         $process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_phone.py", 
    //             $phone, $message]);


    //         try {
    //         // Menjalankan proses
    //             $process->run();

    //         // Memeriksa apakah proses berhasil
    //             if (!$process->isSuccessful()) {
    //                 throw new ProcessFailedException($process);
    //             }

    //         // Mendapatkan output dari proses
    //             $output = $process->getOutput();

    //             return redirect()->back()->with('success', $output);
    //         } catch (ProcessFailedException $e) {
    //         // Jika proses gagal, kembalikan pesan kesalahan
    //             $errorMessage = $e->getMessage();
    //             return redirect()->back()->with('error', $errorMessage);
    //         }
    //     } else {
    //         return redirect()->back()->with('error', 'Telegram Disabled');
    //     }
    // }


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


        $msgresult = WaGatewayHelper::wa_payment($hp, $request->message);


        if (isset($msgresult['status']) && $msgresult['status'] === 'success') {
            return redirect()
            ->back()
            ->with('success', $msgresult['message']);
        }

        return redirect()
        ->back()
        ->with('error', $msgresult['message']);
    }


            // if (! isset($msgresult['status']) || $msgresult['status'] !== 'success') {
            //     return response()->json(['message' => 'WhatsApp notification sent successfully.']);
            // } else {
            //     return response()->json(['message' => 'Failed to send WhatsApp notification.'], 400);
            // }





       //  $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
       //      'form_params' => [
       //      // 'api_key' => env('WAPISENDER_KEY'),
       //      // 'device_key' => $request->device,
       //      // 'destination' => $hp,
       //         'token' => env('WAPISENDER_KEY'),
       //         'number' => $hp,
       //         'message' =>$request->message,
       //     ]
       // ]);

//Kirim pesan ke group
        // $result= $result->getBody();
        // $array = json_decode($result, true);

    // $result_g = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
    //     'form_params' => [
    //         'key' => env('WAPISENDER_KEY'),
    //         'device' => $request->device,
    //         'group_id' =>env('WAPISENDER_GROUPTICKET'),
    //         'message' =>$request->message,
    //     ]
    // ]);


    // $result_g= $result_g->getBody();
    // $array = json_decode($result_g, true);
//    return redirect ('/ticket/'.$request->id_ticket)->with('success','Message '.$array['status'].' - '.$array['message']); 
    //     return redirect ('/ticket/'.$request->id_ticket)->with('success','Message '.$array['status']); 
    // }
    else
    {
        return redirect ('/ticket/'.$request->id_ticket)->with('error','WA Disabled');
    }

}
}
