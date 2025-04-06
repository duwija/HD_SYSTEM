<?php

namespace App\Http\Controllers;
use \App\Distpoint;
use DataTables;

use Illuminate\Http\Request;

class DistpointController extends Controller
{
 public function __construct()
 {
    $this->middleware('auth');
        $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,marketing'); // Daftar privilege
        $this->middleware('checkPrivilege:admin,noc')->only(['create', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */




    public function table_distpoint_list(Request $request){


        $distpoint = \App\Distpoint::withCount('customer') // Use the correct method name for the relationship
        ->whereNotIn('id', [1])
        ->orderBy('name', 'ASC')
        ->get();
        return DataTables::of($distpoint)
        ->addIndexColumn()
        ->editColumn('name',function($distpoint)
        {

            return ' <a href="/distpoint/'.$distpoint->id.'" title="distpoint" class="badge badge-primary text-center  "> '.$distpoint->name. '</a>';
        })
        ->addColumn('site', function($distpoint){

          return '<a class="text-center">'.$distpoint->site_name->name.'</a>';

      })
        ->editColumn('parrent',function($distpoint){
            return '<a href="/distpoint/'.$distpoint->id.'" class="badge badge-info">'.$distpoint->distpoint_name->name.'</a>';
        })
        ->addColumn('customer_count', function($distpoint) {

            $threshold = floor(0.6 * $distpoint->ip);
            if($distpoint->customer_count < $threshold)
            {
                $color='badge-success';
            }
            elseif($distpoint->customer_count >= $threshold)
            {
                $color='badge-warning';
            }
            else
            {
                $color='badge-error';
            }

            return '<a class="badge '.$color.'">'.$distpoint->customer_count.'</a>';
        })

        ->rawColumns(['DT_RowIndex','name','site','ip','security','parrent','description','customer_count'])

        ->make(true);
    }
    public function index()
    {
        //
        // $distpoint = \App\Distpoint::all();
     $distpoint = \App\Distpoint::WhereNotIn('id',[1])->get();


     return view ('distpoint/index',['distpoint' =>$distpoint]);
 }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $site = \App\Site::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        //
        $config['center'] = env('COORDINATE_CENTER');
        $config['zoom'] = '13';
        $config['apikey'] = env('GOOGLE_MAPS_API_KEY');
        //$this->googlemaps->initialize($config);

        $marker = array();
        $marker['position'] = env('COORDINATE_CENTER');
        $marker['draggable'] = true;
        $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

        app('map')->initialize($config);
        app('map')->add_marker($marker);
        $map = app('map')->create_map();

        
        return view ('distpoint/create',['map' => $map, 'site' => $site, 'distpoint' => $distpoint ] );


    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //protected $fillable =['name','id_site', 'ip', 'security','parrent','coordinate','created_at','description'];
        $request ->validate([
            'name' => 'required|unique:distpoints',
            'id_site' => 'required',
            'parrent' => 'required',
            'id_site' => 'required',
            'coordinate' => 'required',
            // 'create_at' => 'required',
            // 'description' => 'required'
        ]);


        \App\Distpoint::create($request->all());

        return redirect ('/distpoint')->with('success','Item created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $distpoint = \App\Distpoint::findOrFail($id);
        $site = \App\Site::findOrFail($distpoint->id_site);
        $distpoint_name = \App\Distpoint::findOrFail($distpoint->parrent);
        $distpoint_chart = \App\Distpoint::where('parrent', $id)->get();

    // Mengatur koordinat pusat
        $coordinate = $distpoint->coordinate ?? env('COORDINATE_CENTER');


        $center = [
            'coordinate' => $coordinate,
            'zoom' => 15
        ];
$locations = []; // Inisialisasi variabel locations

// Tambahkan lokasi untuk titik distribusi
$locations[] = [
    'customer' => $distpoint->coordinate,
    'name' => $distpoint->name, // Pastikan menggunakan $distpoint
    'icon' => url('img/odp1.png') // Ikon untuk titik distribusi
];

// Ambil semua pelanggan yang terkait dengan titik distribusi
$customers = \App\Customer::where('id_distpoint', $id)->get();

// Tambahkan lokasi untuk setiap pelanggan
foreach ($customers as $customer) {
    if ($customer->coordinate) {
        // Tambahkan logika untuk menentukan ikon pelanggan
        // $icon = url('img/default_icon.png'); // Ikon default
        // if ($customer->type === 'premium') {
        //     $icon = url('img/premium_icon.png'); // Ikon untuk pelanggan premium
        // } elseif ($customer->type === 'standard') {
        //     $icon = url('img/standard_icon.png'); // Ikon untuk pelanggan standar
        // }

        $locations[] = [
            'customer' => $customer->coordinate,
            'name' => $customer->name,
            // 'icon' => $icon // Menambahkan ikon khusus
        ];
    }
}



return view('distpoint.show', [
    'distpoint' => $distpoint,
    'site' => $site,
    'center' => $center,
    'locations' => $locations,
    'distpoint_name' => $distpoint_name,
    'distpoint_chart' => $distpoint_chart
]);

}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {


        $site = \App\Site::pluck('name', 'id');
        $distpoint_name = \App\Distpoint::pluck('name', 'id');
        $distpoint = \App\Distpoint::findOrFail($id);

        
        if ($distpoint->coordinate == null)
        {
            $coordinate = env('COORDINATE_CENTER');
        }
        else
        {
            $coordinate = $distpoint->coordinate;
        }
        //

        $config['center'] = $coordinate;
        $config['zoom'] = '13';

        $marker = array();
        $marker['position'] = $coordinate;
        $marker['draggable'] = true;
        $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

        app('map')->initialize($config);
        
        app('map')->add_marker($marker);
        $map = app('map')->create_map();

        return view ('distpoint.edit',['distpoint' => $distpoint,'site' => $site,'map' => $map, 'distpoint_name' => $distpoint_name] );
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
        $request ->validate([
          'name' => 'required',
          'id_site' => 'required',
          'parrent' => 'required',
          'coordinate' => 'required',
            // 'description' => 'required'
      ]);


        $check_dispoint = \App\Distpoint::where('name', $request->name)->first();
        
        if ($check_dispoint)

        {
           if  ($id != $check_dispoint->id )
           {

            return redirect ('/distpoint')->with('error','The Name already exists with a different id!');
        }
        else
        {

          \App\Distpoint::where('id', $id)->update([
            'name' => $request->name,
            'id_site' => $request->id_site,
            'parrent' => $request->parrent,
            'ip' => $request->ip,
            'security' => $request->security,
            'coordinate' => $request->coordinate,
            'description' => $request->description



        ]);
          return redirect ('/distpoint')->with('success','Item updated successfully!');
      }
  }

  else
  {

      \App\Distpoint::where('id', $id)->update([
        'name' => $request->name,
        'id_site' => $request->id_site,
        'parrent' => $request->parrent,
        'ip' => $request->ip,
        'security' => $request->security,
        'coordinate' => $request->coordinate,
        'description' => $request->description



    ]);
      return redirect ('/distpoint')->with('success','Item updated successfully!');
  }




}
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //      <div class="form-group">
      \App\Distpoint::destroy($id);
      return redirect ('distpoint')->with('success','Item deleted successfully!');
  }  

  
}
