<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use \RouterOS\Client;
use \RouterOS\Query;
Use GuzzleHttp\Clients;
use \App\Customer;
use \App\Suminvoice;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;
class CustomerController extends Controller
{


    /**
     * Display a listing of the resource.s
     *
     * @return \Illuminate\Http\Response

     */
    public function __construct()
    {
        $this->middleware('auth');


        
    // // Terapkan middleware untuk semua metode
    //     $this->middleware('checkPrivilege:admin')->only(['adminDashboard']);
    //     $this->middleware('checkPrivilege:accounting')->only(['accountingDashboard']);
    //     $this->middleware('checkPrivilege:marketing')->only(['marketingDashboard']);
    //     $this->middleware('checkPrivilege:payment')->only(['paymentDashboard']);
    //     $this->middleware('checkPrivilege:noc')->only(['nocDashboard']);
    //     $this->middleware('checkPrivilege:user')->only(['userDashboard']);
    //     $this->middleware('checkPrivilege:vendor')->only(['vendorDashboard']);
        
    //     // Hanya merchant yang bisa akses metode ini
    //     $this->middleware('checkPrivilege:merchant')->only(['customerMerchant', 'table_CustomerMerchant']);
        
    //     // Semua pengguna dengan privilege selain merchant bisa akses semua metode
    //     $this->middleware('checkPrivilege:admin,accounting,marketing,payment,noc,user,vendor')->except(['customerMerchant', 'table_CustomerMerchant']);
    }
    public function search(Request $request)
    {
      $request ->validate([
        'search' => 'required|min:4',
    ]);
      $val =$request->search ;
      $customer = \App\Customer::orderBy('id', 'DESC')
      ->where('name', 'LIKE', "%".$val."%") 
      ->orWhere('customer_id', 'LIKE', "%".$val."%") 
      ->orWhere('address', 'LIKE', "%".$val."%") 
      ->orWhere('pppoe', 'LIKE', "%".$val."%") 
      ->orWhere('phone', 'LIKE', "%".$val."%") 
      ->get();


      return view ('customer/search',['customer' =>$customer]);
  }

  public function filter(Request $request){
    $filter =$request->filter ;
    $parameter =$request->parameter ;
    $id_status =$request->id_status ;
    $id_plan =$request->id_plan ;
    $customer = \App\Customer::orderBy('id', 'DESC')
    ->where($filter, 'LIKE', "%".$parameter."%") 
    ->Where('id_status', 'LIKE', "%".$id_status."%") 
    ->Where('id_plan', 'LIKE', "%".$id_plan."%") 
    ->get();

    return DataTables::of($customer)
    ->editColumn('customer_id',function($customer){
        return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
    })
    ->addIndexColumn()
    ->addColumn('select', function($customer)
    {
      if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))

      {
       return '<input   type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'"></td>';
   }

   else
   {}

})
    ->addColumn('plan', function($customer){

      return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.') </a>';

  })
    ->addColumn('status_cust', function($customer){
      if ($customer->status_name->name == 'Active')
        {$badge_sts = "badge-success";}
    elseif ($customer->status_name->name == 'Inactive')
       {  $badge_sts = "badge-secondary";}
   elseif ($customer->status_name->name == 'Block')
    {     $badge_sts = "badge-danger";}
elseif ($customer->status_name->name == 'Company_Properti')
 {$badge_sts = "badge-primary";}
else
 {$badge_sts = "badge-warning";}

return '<a class="badge text-white text-center  '.$badge_sts.'">'.$customer->status_name->name.'</a>';


})
    ->addColumn('invoice',function($customer)
    {
      $count_inv = new \App\Suminvoice();
      $result = $count_inv->countinv($customer->id);
      if ( $result >= 1)
      {

          return ' <a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm   "> '.$result. '</a>';
      }

  })
    ->addColumn('action',function($customer){
        $create_ticket = url('/ticket/'.$customer->id.'/create');







    })
    ->rawColumns(['DT_RowIndex','customer_id','plan','status_cust','select','invoice','action'])
    ->make(true);
}


public function searchforjurnal(Request $request) {
    // Log::info('Data yang diterima:', $request->all());

    // Ambil data customer berdasarkan pencarian
    $customers = Customer::where('name', 'LIKE', "%{$request->q}%")
    ->orWhere('customer_id', 'LIKE', "%{$request->q}%")
    ->limit(100)
    ->get();

    return response()->json($customers);
}

public function index()
{
        //
  $status = \App\Statuscustomer::pluck('name', 'id');
  $merchant = \App\Merchant::pluck('name', 'id');
  $plan = \App\Plan::pluck('name', 'id');

  return view ('customer/index',['status'=>$status, 'plan'=>$plan, 'merchant'=>$merchant]);
}


public function customermerchant()
{
        //

    $status = \App\Statuscustomer::pluck('name', 'id');
    // $merchant = \App\Merchant::pluck('name', 'id');
    // $plan = \App\Plan::pluck('name', 'id');

    return view ('customer/customermerchant',['status'=>$status]);
}
public function unpaid()
{
        //
  $status = \App\Statuscustomer::pluck('name', 'id');
  $plan = \App\Plan::pluck('name', 'id');
  $merchant = \App\Merchant::pluck('name', 'id');
  return view ('customer/unpaid',['status'=>$status, 'plan'=>$plan,'merchant'=>$merchant]);
}
public function isolir()
{
        //
  $status = \App\Statuscustomer::pluck('name', 'id');
  $plan = \App\Plan::pluck('name', 'id');
  $customer_count = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at', DB::raw('COUNT(customers.id) as customers.id'))
  ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
  ->where("suminvoices.payment_status", "=", 0)
  ->where("customers.id_status", "=", 2)
  ->groupBy('customers.id')
  ->get();

  return view ('customer/isolir',['status'=>$status, 'plan'=>$plan, 'customer_count'=>$customer_count]);
}


public function table_customer(Request $request)
{
    // Start building the query
    $customerQuery = \App\Customer::select('id', 'customer_id', 'name', 'address', 'id_merchant', 'billing_start', 'isolir_date', 'id_plan', 'id_status', 'id_sale','id_distrouter');
// Hitung jumlah pelanggan berdasarkan status
    $customerCounts = \App\Customer::select('id_status', \DB::raw('count(*) as total'))
    ->when(!empty($request->filter), function ($query) use ($request) {
        $query->where($request->filter, 'LIKE', "%{$request->parameter}%");
    })
    ->when(!empty($request->id_status), function ($query) use ($request) {
        $query->where('id_status', $request->id_status);
    })
    ->when(!empty($request->id_plan), function ($query) use ($request) {
        $query->where('id_plan', $request->id_plan);
    })
    ->when(!empty($request->id_merchant), function ($query) use ($request) {
        $query->where('id_merchant', $request->id_merchant);
    })
    ->groupBy('id_status')
    ->pluck('total', 'id_status');

    $potensial = $customerCounts[1] ?? 0;
    $active = $customerCounts[2] ?? 0;
    $inactive = $customerCounts[3] ?? 0;
    $block = $customerCounts[4] ?? 0;
    $company_Properti = $customerCounts[5] ?? 0;
    $unknown = array_sum($customerCounts->toArray()) - ($potensial + $active + $inactive + $block + $company_Properti);

    // Apply filtering if the filter is not empty
    if (!empty($request->filter)) {
        $filter = $request->filter;
        $parameter = $request->parameter;

        $customerQuery->where($filter, 'LIKE', "%{$parameter}%");

        // Apply status, plan, and merchant filters if provided
        if (!empty($request->id_status)) {
            $customerQuery->where('id_status', $request->id_status);
        }

        if (!empty($request->id_plan)) {
            $customerQuery->where('id_plan', $request->id_plan);
        }

        if (!empty($request->id_merchant)) {
            $customerQuery->where('id_merchant', $request->id_merchant);
        }
    }

    // Order the results
    $customerQuery->orderBy('id', 'DESC');

    // Get the results

    return DataTables::of($customerQuery)
    ->editColumn('customer_id', function ($customer) {
        return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
    })
    ->addColumn('billing_start', function ($customer) {
        return '<a>'.$customer->billing_start.'</a>';
    })
    ->addIndexColumn()
    ->addColumn('select', function ($customer) {
        if (in_array($customer->status_name->name, ['Active', 'Block'])) {
            return '<input type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'">';
        }
            return ''; // Return empty string if not Active or Block
        })
    ->addColumn('plan', function ($customer) {
        return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.')</a>';
    })
    ->editColumn('id_merchant', function ($customer) {
        return $customer->merchant_name ? 
        '<a class="text-center">' . $customer->merchant_name->name . '</a>' : 
        '<a class="text-center">No Merchant</a>';
    })
    ->addColumn('status_cust', function ($customer) {


        $badgeClass = match ($customer->status_name->name) {
            'Active' => 'badge-success',
            'Inactive' => 'badge-secondary',
            'Block' => 'badge-danger',
            'Company_Properti' => 'badge-primary',
            default => 'badge-warning',
        };
        return '<a class="badge text-white text-center '.$badgeClass.'">'.$customer->status_name->name.'</a>';
    })
    ->addColumn('invoice', function ($customer) {
        $count_inv = new \App\Suminvoice();
        $result = $count_inv->countinv($customer->id);
        return $result >= 1 ? '<a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm">'.$result.'</a>' : '';
    })
    ->addColumn('action', function ($customer) {
        $create_ticket = url('/ticket/'.$customer->id.'/create');
        return '<a href="'.$create_ticket.'" class="badge badge-success">Create Ticket</a>';

        

    })
    ->rawColumns(['customer_id', 'id_merchant', 'plan', 'billing_start', 'status_cust', 'select', 'invoice', 'action'])
    ->with('potensial',$potensial)
    ->with('active',$active)
    ->with('inactive',$inactive)
    ->with('block',$block)
    ->with('company_Properti',$company_Properti)
    ->with('unknown',$unknown)
    ->make(true);
    






    
}

public function table_customermerchant(Request $request)
{
    // Start building the query
$id_merchant = \Auth::user()->id_merchant; // Perbaiki nama variabel dari id_idmerchant ke id_merchant

$customerQuery = \App\Customer::select('id', 'customer_id', 'name', 'address', 'id_merchant', 'billing_start', 'isolir_date', 'id_plan', 'id_status', 'id_sale');

if (!is_null($id_merchant)) {
    $customerQuery->where('id_merchant', $id_merchant);
}
    // Apply filtering if the filter is not empty
if (!empty($request->filter)) {
    $filter = $request->filter;
    $parameter = $request->parameter;

    $customerQuery->where($filter, 'LIKE', "%{$parameter}%");

        // Apply status, plan, and merchant filters if provided
    if (!empty($request->id_status)) {
        $customerQuery->where('id_status', $request->id_status);
    }


}

    // Order the results
$customerQuery->orderBy('id', 'DESC');

    // Get the results
return DataTables::of($customerQuery)
->editColumn('customer_id', function ($customer) {
    return '<a>'.$customer->customer_id.'</a>';
})

->addIndexColumn()
->addColumn('select', function ($customer) {
    if (in_array($customer->status_name->name, ['Active', 'Block'])) {
        return '<input type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'">';
    }
            return ''; // Return empty string if not Active or Block
        })

->editColumn('id_merchant', function ($customer) {
    return $customer->merchant_name ? 
    '<a class="text-center">' . $customer->merchant_name->name . '</a>' : 
    '<a class="text-center">No Merchant</a>';
})
->addColumn('status_cust', function ($customer) {
    $badgeClass = match ($customer->status_name->name) {
        'Active' => 'badge-success',
        'Inactive' => 'badge-secondary',
        'Block' => 'badge-danger',
        'Company_Properti' => 'badge-primary',
        default => 'badge-warning',
    };
    return '<a class="badge text-white text-center '.$badgeClass.'">'.$customer->status_name->name.'</a>';
})


->rawColumns(['customer_id', 'id_merchant', 'plan', 'status_cust'])
->make(true);
}


public function table_plan_group(Request $request)
{
    // Start building the query
 // Start building the query
    $customerQuery = \App\Customer::select('id_plan', \DB::raw('count(*) as count'))
    ->groupBy('id_plan');

// Apply filtering if the filter is not empty
    if (!empty($request->filter)) {
        $filter = $request->filter;
        $parameter = $request->parameter;

        $customerQuery->where($filter, 'LIKE', "%{$parameter}%");
    }

    if (!empty($request->id_status)) {
        $customerQuery->where('id_status', $request->id_status);
    }

    if (!empty($request->id_plan)) {
        $customerQuery->where('id_plan', $request->id_plan);
    }

    if (!empty($request->id_merchant)) {
        $customerQuery->where('id_merchant', $request->id_merchant);
    }


$customerQuery->orderBy('id_plan', 'DESC'); // Change 'id' to 'id_plan' since you're grouping by id_plan

// Execute the query and get the results
$results = $customerQuery->get();

    // Get the results
return DataTables::of($customerQuery)
->editColumn('id_plan', function ($customer) {
    return '<a>'.$customer->plan_name->name.'</a>';
})
    // ->addColumn('billing_start', function ($customer) {
    //     return '<a>'.$customer->billing_start.'</a>';
    // })
->addIndexColumn()
    // ->addColumn('select', function ($customer) {
    //     if (in_array($customer->status_name->name, ['Active', 'Block'])) {
    //         return '<input type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'">';
    //     }
    //         return ''; // Return empty string if not Active or Block
    //     })
    // ->addColumn('plan', function ($customer) {
    //     return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.')</a>';
    // })
    // ->editColumn('id_merchant', function ($customer) {
    //     return $customer->merchant_name ? 
    //     '<a class="text-center">' . $customer->merchant_name->name . '</a>' : 
    //     '<a class="text-center">No Merchant</a>';
    // })
    // ->addColumn('status_cust', function ($customer) {
    //     $badgeClass = match ($customer->status_name->name) {
    //         'Active' => 'badge-success',
    //         'Inactive' => 'badge-secondary',
    //         'Block' => 'badge-danger',
    //         'Company_Properti' => 'badge-primary',
    //         default => 'badge-warning',
    //     };
    //     return '<a class="badge text-white text-center '.$badgeClass.'">'.$customer->status_name->name.'</a>';
    // })
    // ->addColumn('invoice', function ($customer) {
    //     $count_inv = new \App\Suminvoice();
    //     $result = $count_inv->countinv($customer->id);
    //     return $result >= 1 ? '<a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm">'.$result.'</a>' : '';
    // })
    // ->addColumn('action', function ($customer) {
    //     $create_ticket = url('/ticket/'.$customer->id.'/create');
    //     return '<a href="'.$create_ticket.'" class="btn btn-success">Create Ticket</a>';
    // })
->rawColumns(['id_plan','count'])
->make(true);
}

public function trash()
{
        //
    $customer = \App\Customer::onlyTrashed()->get();
        //$customer =DB::table('customers')->get();
       // dump($customer);
    return view ('customer/trash',['customer' =>$customer]);
}
public function restore($id)
{


        //
  $userdata = \App\Customer::onlyTrashed()->findOrFail($id);

  if (!is_null($userdata)) {

      $result=   $userdata->restore();
  }

  if($result)
  {

     return redirect ('/customer/'.$id)->with('success','Item restore successfully!'); 
 }
 else
 {
   return redirect ('/customer/trash')->with('error','Item restore failed!'); 
}
}




public function createinv()
{
        //


  $status = \App\Statuscustomer::pluck('name', 'id');
  $plan = \App\Plan::pluck('name', 'id');
  $search_var ='';
  return view ('invoice/index',['status'=>$status, 'plan'=>$plan, 'search_var'=>$search_var]);

      //
      // $customer = \App\Customer::orderBy('id','DESC')
      //       ->where('id_status', '2')
      //       ->orWhere('id_status', '4') 
      //  ->get();



      //   return view ('invoice/createinv',['customer' =>$customer]);



}


public function table_invoice(Request $request){

   $month = now()->format('mY');

        //    $check_inv = new \App\Invoice();
        //   $result = $check_inv->checkinv($customer->id);
        //   if ( $result >= 1)

        // $customer = \App\Customer::select('customers.id','customer_id','name','address','id_plan','id_status','billing_start')
        //   ->Join('invoices', 'customers.id', '=', 'invoices.id_customer')
        //   ->where('invoices.periode', '=', $month)


   if (empty($request->filter))
   {
      $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')
      ->where('id_status', '2')
      ->orWhere('id_status', '4') 
      ->orderBy('id','DESC');
          //  $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','customers.id_plan','customers.id_status')
          // ->leftJoin("invoices", "invoices.id_customer", "=", "customers.id")
          // ->where('invoices.id_customer',null)
          // ->where('invoices.periode', '=', $month)
          // // ->where('customers.id_status', '2')
          // // ->orWhere('customers.id_status', '4') 
          //  ->groupBy('customers.id');

  }
  elseif ((empty($request->id_status))and (empty($request->id_plan)))
  {
    $filter =$request->filter ;
    $parameter =$request->parameter ;

    $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')

    ->where($filter, 'LIKE', "%".$parameter."%") 
    ->where('id_status', '2')
    ->orWhere('id_status', '4') 

    ->orderBy('id', 'DESC');

        // $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','customers.id_plan','customers.id_status')
        //   ->leftjoin('invoices', 'customers.id', '=', 'invoices.id_customer')
        //   ->where('invoices.periode', '<>', $month)
        //    ->where('invoices.monthly_fee','<>','1')
        //    ->groupBy('customerss.id');
}
elseif ((empty($request->id_status))and (!empty($request->id_plan)))
{
    $filter =$request->filter ;
    $parameter =$request->parameter ;

    $id_plan =$request->id_plan ;
    $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')

    ->where($filter, 'LIKE', "%".$parameter."%") 
    ->where('id_status', '2')
    ->orWhere('id_status', '4') 

    ->Where('id_plan', $id_plan) 
    ->orderBy('id', 'DESC');
}
elseif ((!empty($request->id_status))and (empty($request->id_plan)))
{
    $filter =$request->filter ;
    $parameter =$request->parameter ;
    $id_status =$request->id_status ;

    $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')

    ->where($filter, 'LIKE', "%".$parameter."%") 
    ->Where('id_status', $id_status) 
    ->where('id_status', '2')
    ->orWhere('id_status', '4') 
    ->orderBy('id', 'DESC');
}
else
{
    $filter =$request->filter ;
    $parameter =$request->parameter ;
    $id_status =$request->id_status ;
    $id_plan =$request->id_plan ;
    $customer = \App\Customer::select('id','customer_id','name','address','billing_start','id_plan','id_status')

    ->where($filter, 'LIKE', "%".$parameter."%") 
    ->Where('id_status', $id_status) 
    ->Where('id_plan', $id_plan) 
    ->where('id_status', '2')
    ->orWhere('id_status', '4') 
    ->orderBy('id', 'DESC');
}








return DataTables::of($customer)


->editColumn('customer_id',function($customer){
    return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
})
->addIndexColumn()

->addColumn('plan', function($customer){

  return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.') </a>';

})
->addColumn('status_cust', function($customer){
  if ($customer->status_name->name == 'Active')
    {$badge_sts = "badge-success";}
elseif ($customer->status_name->name == 'Inactive')
   {  $badge_sts = "badge-secondary";}
elseif ($customer->status_name->name == 'Block')
    {     $badge_sts = "badge-danger";}
elseif ($customer->status_name->name == 'Company_Properti')
 {$badge_sts = "badge-primary";}
else
 {$badge_sts = "badge-warning";}

return ' <a class="badge text-white text-center  '.$badge_sts.'">'.$customer->status_name->name.'</a>';


})
->addColumn('invoice',function($customer)
{
  $check_inv = new \App\Invoice();
  $result = $check_inv->checkinv($customer->id);
  if ( $result >= 1)
  {

      return '<a class="badge text-white text-center  badge-secondary"> Created</a>';
  }else
  {


             // $button = '<div id=inv'.$customer->id.'><input type="button" name="createinv" id="'.$customer->id.'" value="create" class="btn btn-success"></div>';


      return '<div id=inv'.$customer->id.'><button type="button" onclick="myFunction('.$customer->id.')" class="btn btn-success">Create</button></div>';




  }

})

->rawColumns(

  ['DT_RowIndex','customer_id','plan','billing_start','status_cust','invoice'])
->make(true);
}
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $status = \App\Statuscustomer::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        $distrouter = \App\Distrouter::pluck('name', 'id');
        $olt = \App\Olt::pluck('name', 'id');
        $sale = \App\Sale::pluck('name', 'id');
        $merchant = \App\Merchant::pluck('name', 'id');
        $plan = \App\Plan::select('name', 'id', 'price')
        ->orderBy('price', 'ASC')
        ->get();
        //
//         $config['center'] = env('COORDINATE_CENTER');
//         $config['zoom'] = '13';
// //$this->googlemaps->initialize($config);

//         $marker = array();
//         $marker['position'] = env('COORDINATE_CENTER');
//         $marker['draggable'] = true;
//         $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

//         app('map')->initialize($config);
        
//         app('map')->add_marker($marker);
//         $map = app('map')->create_map();

        return view ('customer/create',['status' => $status, 'sale'=>$sale,'distpoint' => $distpoint, 'distrouter' => $distrouter, 'olt' => $olt, 'plan' => $plan, 'merchant'=>$merchant  ] );
        // return view ('customer/create',['map' => $map, 'status' => $status, 'sale'=>$sale,'distpoint' => $distpoint, 'distrouter' => $distrouter, 'olt' => $olt, 'plan' => $plan, 'merchant'=>$merchant  ] );

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //dd($request);
        $request ->validate([

            'customer_id' => 'required|unique:customers',
            'name' => 'required',
            'id_card'  => 'nullable',
            'contact_name' => 'required',
            'phone' => 'required|numeric',
            'address' => 'required',
            'npwp'  => 'nullable',
            'tax' => 'required|numeric',
            'email' => 'nullable|email',
            'merchant'  => 'nullable',
        ]);

        $messege ='';
        try
        {

            $status = \App\Statuscustomer::Where('id',$request->id_status)->first();
            if ((!empty($request->id_distrouter)) and (!empty($request->id_plan)) and ($status->name == 'Active')) 
            {

              $distrouter = \App\Distrouter::Where('id',$request->id_distrouter)->first();
              $plan = \App\Plan::Where('id',$request->id_plan)->first();


              \App\Distrouter::mikrotik_addprofile($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$plan->name,$plan->speed,$plan->description);
              \App\Distrouter::mikrotik_addsecreate($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$request->pppoe,$request->password,$plan->name,$request->name);




          }              
      } catch (Exception $e)

      { 
        $messege =" Field to Access to Router, please check connection between System and Router";

    } finally {


        try{
           \App\Customer::create($request->all());
       }
       catch (Exeption $e)
       {
           $messege .=" * Field add user on system, please remove manually user on Router";
           return redirect ('/home')->with('warning',$messege);
       }finally {

        $messege .=" *  User Successfuly added on system ";
        $customer_check = \App\Customer::where('customer_id', $request->customer_id) 
        
        ->first();
        return redirect ('/customer/'.$customer_check->id)->with('success',$messege);
    }

}


     //  else
     //  {
     //     $messege ='Item created successfully ** User In Distribution Router NOT created ** !!';
     // }


       // $this->mikrotik($request->name,$request->customer_id,$request->password);
       // \App\Distrouter::mikrotik_addsecreate($ip,$user,$pass,$port,$request->customer_id,$pass,$request->name);



   // return redirect ('/customer/')->with('success',$messege);
        // return view ('customer/'.$customer_check->id);





}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $customer = \App\Customer::findOrFail($id);
        //$customer =DB::table('customers')->get();
      // dd($customer);

      $countpppoe = \App\Customer::where('pppoe', $customer->pppoe)->count();

      $countpppoe = ($countpppoe > 1) ? $countpppoe : 1;

      if  (\App\Customer::findOrFail($id) ->coordinate == null)
      {
        $coordinate =env('COORDINATE_CENTER');
    }
    else
    {
        $coordinate =\App\Customer::findOrFail($id) ->coordinate;
    }


    $config['center'] = $coordinate;
    $config['zoom'] = '13';

    $center = [
        'coordinate' => $coordinate,
        'name' => $customer->name,
        'zoom' => 13
    ];
    $locations = [
        ['customer' => $customer->coordinate, 'name' => $customer->name],
    ];

    return view('customer.show', [
        'customer' => $customer,
        'countpppoe' => $countpppoe,
        
        'locations' => $locations,
        'center' => $center,
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
        $status = \App\Statuscustomer::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        $distrouter = \App\Distrouter::pluck('name', 'id');
        $sale = \App\Sale::pluck('name', 'id');
        $plan = \App\Plan::select('name', 'id', 'price')
        ->orderBy('price', 'ASC')
        ->get();
        $merchant = \App\Merchant::pluck('name', 'id');
        $olt = \App\Olt::pluck('name', 'id');

        // $topologycustomer = \App\topologycustomer::findOrFail($id);
       //  $customer_coordinate = \App\Customer::findOrFail($id);


        if  (\App\Customer::findOrFail($id)->coordinate == null)
        {
            $coordinate =env('COORDINATE_CENTER');
        }
        else
        {
            $coordinate =\App\Customer::findOrFail($id)->coordinate;
        }
        //
        $config['center'] =  $coordinate;
        $config['zoom'] = '13';
//$this->googlemaps->initialize($config);

        $marker = array();
        $marker['position'] = $coordinate;
        $marker['draggable'] = true;
        $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

        app('map')->initialize($config);
        
        app('map')->add_marker($marker);
        $map = app('map')->create_map();

        
        return view ('customer/edit',['customer' => \App\Customer::findOrFail($id),'map' => $map, 'status' => $status, 'distpoint' => $distpoint,'sale' =>$sale, 'distrouter' => $distrouter, 'plan' => $plan, 'olt' =>$olt, 'merchant'=>$merchant ] );

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */





//======================================================================================

//     public function table_unpaid_customer(Request $request){


//         if (empty($request->filter))
//         {
//          $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
//          ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//          ->where("suminvoices.payment_status", "=", 0)
//          ->where("customers.id_status", "=", 2)
//          ->groupBy('customers.id');
//      }
//      else
//      {

//         $filter = $request->filter;
//         $parameter = $request->parameter;
//         $id_status = $request->id_status;
//         // $id_plan = $request->id_plan;
//         $deleted_at = $request->deleted_at;

//         if($deleted_at == "yes")

//         {
//             if(empty($id_status)){
//                 $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status','customers.deleted_at')
//                 ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//                 ->where("suminvoices.payment_status", "=", 0)
//                 ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
//                 ->whereNotNull("customers.deleted_at")
//                 ->groupBy('customers.id')
//                 ->withTrashed();

//             } 
//             else
//             {
//                 $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status','customers.deleted_at')
//                 ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//                 ->where("suminvoices.payment_status", "=", 0)
//                 ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
//                 ->where("customers.id_status", '=', $id_status)
//                 ->whereNotNull("customers.deleted_at")
//                 ->groupBy('customers.id')
//                 ->withTrashed();
//             }

//         }
//         else
//         {
//             if (empty($id_status)) {
//               $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
//               ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//               ->where("suminvoices.payment_status", "=", 0)
//               ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
//               ->groupBy('customers.id'); 
//           }
//           else
//           {
//              $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
//              ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
//              ->where("suminvoices.payment_status", "=", 0)
//              ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
//              ->where("customers.id_status", "=", $id_status)
//         // ->where("customers.id_status", "=", $id_status)
//         // ->where("customers.id_plan", "=", $id_plan)
//              ->groupBy('customers.id');   
//          }



//      }



//  }




//  return DataTables::of($customer)

//  ->editColumn('customer_id',function($customer){
//     if (empty($customer->deleted_at)) {
//         return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
//     }
//     else
//     {
//         return '<a href="/customer/'.$customer->id.'" class="btn btn-secondary">'.$customer->customer_id.'</br>'.$customer->deleted_at.'</a>';
//     }

// })
//  ->addColumn('billing_start',function($customer){
//     return '<a>'.$customer->billing_start.'</a>';
// })  
//  ->addIndexColumn()
//  ->addColumn('select', function($customer)
//  {
//   if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))

//   {
//      return '<input   type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'"></td>';
//  }

//  else
//  {}

// })
//  ->addColumn('plan', function($customer){

//   return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.') </a>';

// })
//  ->addColumn('status_cust', function($customer){
//   if ($customer->status_name->name == 'Active')
//     {$badge_sts = "badge-success";}
// elseif ($customer->status_name->name == 'Inactive')
//  {  $badge_sts = "badge-secondary";}
// elseif ($customer->status_name->name == 'Block')
//     {     $badge_sts = "badge-danger";}
// elseif ($customer->status_name->name == 'Company_Properti')
//    {$badge_sts = "badge-primary";}
// else
//    {$badge_sts = "badge-warning";}

// return '<a class="badge text-white text-center  '.$badge_sts.'">'.$customer->status_name->name.'</a>';


// })
//  ->addColumn('invoice',function($customer)
//  {
//   $count_inv = new \App\Suminvoice();
//   $result = $count_inv->countinv($customer->id);
//   if ( $result >= 1)
//   {

//     return ' <a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm text-center  "> '.$result. '</a>';
// }

// })
//  ->addColumn('Total Inv',function($customer){
//     $create_ticket = url('/ticket/'.$customer->id.'/create');

//     $button = '<a href="'.$create_ticket.'" class="btn btn-success">Create Ticket</a>';
//     $balance_inv = new \App\Suminvoice();
//     $result = $balance_inv->balanceinv($customer->id);
//     $balance = json_decode($result);
//     return ($balance[0]->total);


// })
//  ->rawColumns(['DT_RowIndex','customer_id','plan','billing_start','status_cust','select','invoice','action'])
//  ->make(true);
// }



    public function table_unpaid_customer(Request $request) {
        $customerQuery = \App\Customer::select(
            'customers.id',
            'customers.customer_id',
            'customers.name',
            'customers.id_merchant',
            'customers.address',
            'customers.billing_start',
            'isolir_date',
            'customers.id_plan',
            'customers.tax',
            'customers.id_status',
            'customers.deleted_at'
        )
        ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
        ->where("suminvoices.payment_status", "=", 0)
        ->groupBy('customers.id');

    // Apply filters if provided
        if (!empty($request->filter)) {
            $filter = $request->filter;
            $parameter = $request->parameter;
            $id_status = $request->id_status;
            $id_plan = $request->id_plan;
            $deleted_at = $request->deleted_at;
            $countinv=$request->countinv;

            $customerQuery->where("customers." . $filter, 'LIKE', "%" . $parameter . "%");

            if ($deleted_at == "yes") {
                $customerQuery->whereNotNull("customers.deleted_at");
            } else {
                $customerQuery->whereNull("customers.deleted_at");
            }

            if (!empty($id_status)) {
                $customerQuery->where("customers.id_status", "=", $id_status);
            }
            if (!empty($id_plan)) {
                $customerQuery->where("customers.id_plan", "=", $id_plan);
            }
            if (!empty($request->id_merchant)) {
                $customerQuery->where('id_merchant', $request->id_merchant);
            }
            if (!empty($countinv)) {
                $customerQuery->whereHas('suminvoices', function ($query) use ($countinv) {
    $query->where('payment_status', 0)  // Menambahkan kondisi untuk status invoice 0
          ->havingRaw('COUNT(*) = ?', [$countinv]);  // Menghitung jumlah invoice
      });
            }
        } else {
        // Default condition for id_status if no filter is applied
            $customerQuery->where("customers.id_status", "=", 2);
        }

    // Execute the query
        $customers = $customerQuery->withTrashed()->get();

        return DataTables::of($customers)
        ->editColumn('customer_id', function($customer) {
            $btnClass = empty($customer->deleted_at) ? 'btn-primary' : 'btn-secondary';
            return '<a href="/customer/' . $customer->id . '" class="btn ' . $btnClass . '">' . $customer->customer_id . '</a>';
        })
        ->addColumn('billing_start', function($customer) {
            return '<a>' . $customer->billing_start . '</a>';
        })
        ->addIndexColumn()

        ->editColumn('id_merchant', function ($customer) {
            // return $customer->merchant_name ? 
            // $customer->merchant_name->name : 
            // 'No Merchant';
        })
        ->addColumn('plan', function($customer) {
            return '<a class="text-center">' . $customer->plan_name->name . '(' . $customer->plan_name->price . ')</a>';
        })
        ->addColumn('status_cust', function($customer) {
            $badgeClass = match ($customer->status_name->name) {
                'Active' => 'badge-success',
                'Inactive' => 'badge-secondary',
                'Block' => 'badge-danger',
                'Company_Properti' => 'badge-primary',
                default => 'badge-warning',
            };
            return '<a class="badge text-white text-center ' . $badgeClass . '">' . $customer->status_name->name . '</a>';
        })


//         ->addColumn('invoice', function($customer) {
//             $count_inv = new \App\Suminvoice();
//             $result = $count_inv->countinv($customer->id);
//             if ($result < 1) {
//                 return '';
//             }

//     $btnClass = 'btn-info'; // Default untuk $result = 1

//     if ($result == 2) {
//         $btnClass = 'btn-warning';
//     } elseif ($result >= 3) {
//         $btnClass = 'btn-danger';
//     }

//     return '<a href="/invoice/' . $customer->id . '" title="Invoice" class="btn '.$btnClass.'  btn-sm text-center">' . $result . '</a>';

// })


//         ->addColumn('Total Inv', function($customer) {
//             $create_ticket = url('/ticket/' . $customer->id . '/create');
//             $balance_inv = new \App\Suminvoice();
//             $result = $balance_inv->balanceinv($customer->id);
//             $balance = json_decode($result);
//             return number_format(($balance[0]->total ?? 0), 2, ',', '.'); // Default to 0 if balance is null
//         })



        ->addColumn('invoice', function ($customer) {
            $result = \App\Suminvoice::countinv($customer->id);

            if ($result < 1) {
                return '';
            }

            $btnClass = match (true) {
                $result == 1 => 'btn-info',
                $result == 2 => 'btn-warning',
                $result >= 3 => 'btn-danger',
                default => 'btn-secondary'
            };

            return '<a href="/invoice/' . $customer->id . '" title="Invoice" class="btn ' . $btnClass . ' btn-sm text-center">' . $result . '</a>';
        })

        ->addColumn('Total Inv', function ($customer) {
    //         $balance = \App\Suminvoice::balanceinv($customer->id);
    // return number_format($balance, 2, ',', '.'); // Format angka dengan koma
})
        ->rawColumns(['customer_id', 'plan', 'billing_start', 'status_cust', 'select', 'invoice'])
        ->make(true);
    }


//======================================================================================

//======================================================================================

    public function table_isolir_customer(Request $request){


        if (empty($request->filter))
        {
           $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
           ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
           ->where("suminvoices.payment_status", "=", 0)
           ->where("customers.id_status", "=", 2)
           ->groupBy('customers.id');
       }
       else
       {

        $filter = $request->filter;
        $parameter = $request->parameter;
        $id_status = $request->id_status;
        // $id_plan = $request->id_plan;
        $deleted_at = $request->deleted_at;

        if($deleted_at == "yes")

        {
            if(empty($id_status)){
                $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status','customers.deleted_at')
                ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
                ->where("suminvoices.payment_status", "=", 0)
                ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
                // ->whereNotNull("customers.deleted_at")
                ->groupBy('customers.id');
                // ->withTrashed();

            } 
            else
            {
                $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status','customers.deleted_at')
                ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
                ->where("suminvoices.payment_status", "=", 0)
                ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
                ->where("customers.id_status", '=', $id_status)
                // ->whereNotNull("customers.deleted_at")
                ->groupBy('customers.id');
                // ->withTrashed();
            }

        }
        else
        {
            if (empty($id_status)) {
              $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
              ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
              ->where("suminvoices.payment_status", "=", 0)
              ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
              ->groupBy('customers.id'); 
          }
          else
          {
           $customer = \App\Customer::select('customers.id','customers.customer_id','customers.name','customers.address','customers.billing_start','isolir_date','customers.id_plan','customers.tax','customers.id_status','suminvoices.payment_status', 'customers.deleted_at')
           ->leftJoin("suminvoices", "suminvoices.id_customer", "=", "customers.id")
           ->where("suminvoices.payment_status", "=", 0)
           ->where("customers.".$filter, 'LIKE', "%".$parameter."%")
           ->where("customers.id_status", "=", $id_status)
        // ->where("customers.id_status", "=", $id_status)
        // ->where("customers.id_plan", "=", $id_plan)
           ->groupBy('customers.id');   
       }



   }



}




return DataTables::of($customer)

->editColumn('customer_id',function($customer){
    if (empty($customer->deleted_at)) {
        return '<a href="/customer/'.$customer->id.'" class="btn btn-primary">'.$customer->customer_id.'</a>';
    }
    else
    {
        return '<a href="/customer/'.$customer->id.'" class="btn btn-secondary">'.$customer->customer_id.'</br>'.$customer->deleted_at.'</a>';
    }

})
->addColumn('billing_start',function($customer){
    return '<a>'.$customer->billing_start.'</a>';
})  
->addIndexColumn()
->addColumn('select', function($customer)
{
  if (($customer->status_name->name == 'Active')Or ($customer->status_name->name == 'Block'))

  {
   return '<input   type="checkbox" id="id_cust" name="id[]" value="'. $customer->id .'"></td>';
}

else
{}

})
->addColumn('plan', function($customer){

  return '<a class="text-center">'.$customer->plan_name->name.'('.$customer->plan_name->price.') </a>';

})
->addColumn('status_cust', function($customer){
  if ($customer->status_name->name == 'Active')
    {$badge_sts = "badge-success";}
elseif ($customer->status_name->name == 'Inactive')
   {  $badge_sts = "badge-secondary";}
elseif ($customer->status_name->name == 'Block')
    {     $badge_sts = "badge-danger";}
elseif ($customer->status_name->name == 'Company_Properti')
 {$badge_sts = "badge-primary";}
else
 {$badge_sts = "badge-warning";}

return '<a class="badge text-white text-center  '.$badge_sts.'">'.$customer->status_name->name.'</a>';


})
->addColumn('invoice',function($customer)
{
  $count_inv = new \App\Suminvoice();
  $result = $count_inv->countinv($customer->id);
  if ( $result >= 1)
  {

    return ' <a href="/invoice/'.$customer->id.'" title="Invoice" class="btn btn-warning btn-sm text-center  "> '.$result. '</a>';
}

})
->addColumn('Total Inv',function($customer){
    $create_ticket = url('/ticket/'.$customer->id.'/create');

    $button = '<a href="'.$create_ticket.'" class="btn btn-success">Create Ticket</a>';
    $balance_inv = new \App\Suminvoice();
    $result = $balance_inv->balanceinv($customer->id);
    $balance = json_decode($result);
    return ($balance[0]->total);


})
->rawColumns(['DT_RowIndex','customer_id','plan','billing_start','status_cust','select','invoice','action'])
->make(true);
}




//======================================================================================

public function isolir_customer($id, $status)
{
    try
    {

      if($status==2)
      {
          foreach ($id as $id) 
          {

              $customers = \App\Customer::Where('id',$id)->first();
              $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
              \App\Customer::where('id', $id)->update([
                'id_status' => 4,

            ]);
              \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

          }
      }
      if ($status==1) {
        foreach ($id as $id) 
        {

          $customers = \App\Customer::Where('id',$id)->first();
          $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
          \App\Customer::where('id', $id)->update([
            'id_status' => 2,

        ]);
          \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

      }

  }
  return redirect ('/customer/')->with('success','Item Updates successfully!'); 
}
catch (Exception $ex)
{
  return redirect ('/customer/')->with('warning','Item Updates FIELD!'); 
}




}

//======================================================================================
public function update_status(Request $request)
{
    try
    {
      $id = $request->id;
      $status = $request->status;
      if($status==0)
      {
          foreach ($id as $id) 
          {

              $customers = \App\Customer::Where('id',$id)->first();
              $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
              \App\Customer::where('id', $id)->update([
                'id_status' => 4,

            ]);
              \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

          }
      }
      if ($status==1) {
        foreach ($id as $id) 
        {

          $customers = \App\Customer::Where('id',$id)->first();
          $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
          \App\Customer::where('id', $id)->update([
            'id_status' => 2,

        ]);
          \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

      }

  }
  return redirect ('/customer/')->with('success','Item Updates successfully!'); 
}
catch (Exception $ex)
{
  return redirect ('/customer/')->with('warning','Item Updates FIELD!'); 
}




}
    //===============================================================================

public function update_status_2(Request $request)
{
    try
    {
      $id = $request->id;
      $status = $request->status;
      if($status==0)
      {
          foreach ($id as $id) 
          {

              $customers = \App\Customer::Where('id',$id)->first();
              $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
              \App\Customer::where('id', $id)->update([
                'id_status' => 4,

            ]);
              \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

          }
      }
      if ($status==1) {
        foreach ($id as $id) 
        {

          $customers = \App\Customer::Where('id',$id)->first();
          $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
          \App\Customer::where('id', $id)->update([
            'id_status' => 2,

        ]);
          \App\Distrouter::mikrotik_enable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

      }

  }
  return redirect ('/customer/unpaid')->with('success','Item Updates successfully!'); 
}
catch (Exception $ex)
{
  return redirect ('/customer/unpaid')->with('warning','Item Updates FIELD!'); 
}




}
    //===============================================================================
public function update(Request $request, $id)
{

    $request ->validate([
        'name' => 'required',
        'id_card'  => 'nullable',
        'contact_name' => 'required',
        'phone' => 'required|numeric',
        'address' => 'required',
        'isolir_date' => 'required|numeric',
        'npwp'  => 'nullable',
        'tax' => 'nullable|numeric',
        'email' => 'nullable|email',
        'merchant'  => 'nullable',
    ]);

    $customers = \App\Customer::Where('id',$id)->first();
    $oldData = $customers->toArray();
    $plan = \App\Plan::withTrashed()->Where('id',$customers->id_plan)->first();

    $status = \App\Statuscustomer::withTrashed()->Where('id',$request->id_status)->first();
    $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
      //  $distrouter_new = \App\distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();


    if ((($status->name == 'Active') OR ($status->name == 'Company_Properti')) and ($plan->id == $request->id_plan) and ($distrouter->id == $request->id_distrouter))
    {
        try
        {
          $plan_new = \App\Plan::Where('id',$request->id_plan)->first();
          $distrouter_new = \App\Distrouter::Where('id',$request->id_distrouter)->first();
          \App\Distrouter::mikrotik_addprofile($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$plan_new->name,$plan_new->speed,$plan_new->description);
          \App\Distrouter::mikrotik_addsecreate($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$request->pppoe,$request->password,$plan_new->name,$request->name);
      }catch (Exception $ex) {

      }
  }

  else if ((($status->name != 'Active') OR ($status->name == 'Company_Properti')) and ($plan->id == $request->id_plan) and ($distrouter->id == $request->id_distrouter))
  {
    try {
      echo '2';

      \App\Distrouter::mikrotik_disable($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);
  }

  catch (Exception $ex) {

  }
}
else if ((($status->name == 'Active') OR ($status->name == 'Company_Properti')) and (($plan->id != $request->id_plan) or ($distrouter->id != $request->id_distrouter) or ($customers->pppoe != $request->pppoe)))
{

    try
    {
      echo '3';
      $plan_new = \App\Plan::Where('id',$request->id_plan)->first();
      $distrouter_new = \App\Distrouter::Where('id',$request->id_distrouter)->first();
      \App\Distrouter::mikrotik_remove($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);
      \App\Distrouter::mikrotik_addprofile($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$plan_new->name,$plan_new->speed,$plan_new->description);
      \App\Distrouter::mikrotik_addsecreate($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$customers->pppoe,$request->password,$plan_new->name,$request->name);
      \App\Distrouter::mikrotik_remove_active_connection($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);

  }
  catch (Exception $ex) {

  }
}

else if ((($status->name != 'Active') OR ($status->name == 'Company_Properti')) and (($plan->id != $request->id_plan) or ($distrouter->id != $request->id_distrouter) or ($customers->pppoe != $request->pppoe)))
{

    try{
      echo '4';

      $plan_new = \App\Plan::Where('id',$request->id_plan)->first();
      $distrouter_new = \App\Distrouter::Where('id',$request->id_distrouter)->first();
      \App\Distrouter::mikrotik_remove($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);
      \App\Distrouter::mikrotik_addprofile($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$plan_new->name,$plan_new->speed,$plan_new->description);
      \App\Distrouter::mikrotik_addsecreate($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$customers->customer_id,$request->password,$plan_new->name,$request->name);
      \App\Distrouter::mikrotik_disable($distrouter_new->ip,$distrouter_new->user,$distrouter_new->password,$distrouter_new->port,$customers->pppoe);

  }
  catch (Exception $ex) {

  }
}

try
{



    $newData = [

        'name' => $request->name,
        'id_card' => $request->id_card,
        'date_of_birth' => $request->date_of_birth,
        'pppoe' => $request->pppoe,
        'password' => $request->password,
        'contact_name' => $request->contact_name,
        'phone' => $request->phone,
        'address' => $request->address,
        'id_merchant' => $request->id_merchant,
        'npwp'  => $request->npwp,
        'tax' => $request->tax,
        'billing_start' => $request->billing_start,
        'isolir_date' => $request->isolir_date,
        'email' => $request->email,
        'id_sale' => $request->id_sale,
        'id_plan' => $request->id_plan,
        'id_distpoint' => $request->id_distpoint,
        'id_distrouter' => $request->id_distrouter,
        'id_status' => $request->id_status,
        'coordinate' => $request->coordinate,
        'id_olt' => $request->id_olt,
        'id_onu' => $request->id_onu,
        'note' => $request->note,
        'updated_by' => $request->updated_by,
        'updated_at' => $request->updated_at,


    ];


    \App\Customer::where('id', $id)
    ->update(($newData));


    $changes = [];
    foreach ($newData as $key => $value) {
        if ($oldData[$key] != $value) {
            switch ($key) {
                case 'id_plan':
                $oldName = \App\Plan::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Plan::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Plan'] = ['old' => $oldName, 'new' => $newName];
                break;
                
                case 'id_status':
                $oldName = \App\Statuscustomer::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Statuscustomer::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Status'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_distrouter':
                $oldName = \App\Distrouter::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Distrouter::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Router'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_distpoint':
                $oldName = \App\Distpoint::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Distpoint::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Distpoint'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_merchant':
                $oldName = \App\Merchant::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Merchant::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['Merchant'] = ['old' => $oldName, 'new' => $newName];
                break;

                case 'id_olt':
                $oldName = \App\Olt::withTrashed()->find($oldData[$key])->name ?? 'Unknown';
                $newName = \App\Olt::withTrashed()->find($value)->name ?? 'Unknown';
                $changes['OLT'] = ['old' => $oldName, 'new' => $newName];
                break;

                default:
                $changes[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value
                ];
            }
        }
    }

// Ambil nama customer untuk log
    $customerName = \App\Customer::find($id)->name ?? "Unknown";

// Cek apakah perubahan dilakukan oleh user atau job
    $updatedBy = Auth::user() ? Auth::user()->name : 'System';

  //  $logFile = "customers/customer_{$id}.log";
    $logMessage = now() . " - {$customerName} updated by {$updatedBy} - Changes: " . json_encode($changes) . PHP_EOL;

// Simpan ke file
   // Storage::append($logFile, $logMessage);

    \App\Customerlog::create([
        'id_customer' => $id,
        'date' => now(),
        'updated_by' => $updatedBy,
        'topic' => 'customerdata',
        'updates' => json_encode($changes),
    ]);


    return redirect ('/customer/'.$id)->with('success','Item Updates successfully!'); 
}
catch (Exception $ex) {
    return redirect ('/customer/'.$id)->with('success','Item Updates FIELD!!'.$ex); 
}
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function log($id)
    {
        $customers = \App\Customer::Where('id',$id)->first();
    // Ambil data log dari database berdasarkan customer_id
        $logEntries = \App\Customerlog::where('id_customer', $id)
        ->orderBy('date', 'desc')
        ->get();

        if ($logEntries->isEmpty()) {
            return back()->with('error', 'Log tidak ditemukan');
        }

        return view('/customer/log', compact('logEntries', 'id', 'customers'));
    }
    public function destroy($id)
    {
      try
      {
          $customers = \App\Customer::Where('id',$id)->first();
          $distrouter = \App\Distrouter::withTrashed()->Where('id',$customers->id_distrouter)->first();
          \App\Customer::destroy($id);
          \App\Distrouter::mikrotik_remove($distrouter->ip,$distrouter->user,$distrouter->password,$distrouter->port,$customers->pppoe);
          return redirect ('/home/')->with('success','Item deleted successfully!');
      }
      catch (Exception $ex) {
        return redirect ('/home/')->with('error','Field!');
    }
}

//   public function mikrotik($name, $customerid, $password)
//   {


//     $client = new Client([
//         'host' => '202.169.255.3',
//         'user' => 'duwija',
//         'pass' => 'rh4ps0dy',
//         'port' =>  8728
//     ]);


// // Create "where" Query object for RouterOS
//     $query =
//     // (new Query('/ip/hotspot/ip-binding/print'))
//     //     ->where('mac-address', 'B0:4E:26:44:B5:35');


//     (new Query('/ppp/secret/add '))
//     ->equal('name', $customerid)
//     ->equal('password', $password)
//     ->equal('comment', $name);

// // (new Query('/ppp/secret/print'))

// //  ->where('name', 'mikrotikApi');



// // $secrets = $client->query($query)->read();

// // echo "Before update" . PHP_EOL;


// //        foreach ($secrets as $secret) {

// //     // Change password
// //     $query = (new Query('/ppp/secret/set'))
// //         ->equal('.id', $secret['.id'])
// //         ->equal('disabled', 'false');

// //     // Update query ordinary have no return
// //     $client->query($query)->read();
// //     echo "User Was  Update" . PHP_EOL;
// //     print_r($secret['disabled']);

// // }

// // Send query and read response from RouterOS
//     $response = $client->query($query)->read();

//  // var_dump($response);
// }



public function wa_customer(Request $request)
{
// dd ($request->message);
  if (env('WAPISENDER_STATUS')!="disable")
  {
    $client = new Clients(); 
    $nohp =$request->phone;
    if(substr(trim($nohp), 0, 2)=="62"){
        $hp=trim($nohp);
    }
            // cek apakah no hp karakter ke 1 adalah angka 0
    else if(substr(trim($nohp), 0, 1)=="0"){
        $hp="62".substr(trim($nohp), 1);
    }
    else
    {
        $hp=$nohp;
    }
    $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
        'form_params' => [
            // 'api_key' => env('WAPISENDER_KEY'),
            // 'device_key' => $request->device,

            // 'destination' =>$hp,
           'token' => env('WAPISENDER_KEY'),


           'number' =>$hp,
           'message' =>$request->message,


       ]
   ]);

// echo $result->getStatusCode();
//         // 200
//          $result->getHeader('content-type');
        // // 'application/json; charset=utf8'
    $result= $result->getBody();
    $array = json_decode($result, true);

    // return redirect ('/customer/'.$request->id_customer)->with('success','Message '.$array['status'].' - '.$array['message']); 
    return redirect()->back()->with('success','Message '.$array['status']);

}
else
{
  return redirect ('/customer/'.$request->id_customer)->with('error','WA Disabled');
}

}

public function allow()
{
}


public function createTunnel(Request $request)
{
    $remoteIp = '10.27.13.17';  // IP yang ingin di-tunnel
    $port = rand(8001, 9000);  // Pilih port acak antara 8001-9000
    $sshUser   = 'root';  // Pengguna SSH dari environment
    $serverIp = '103.187.113.21';  // IP server dari environment

    Log::info("Creating SSH Tunnel to {$remoteIp} on port {$port} via server {$serverIp}");

    // Check if the port is already in use
    if ($this->isPortInUse($port)) {
        return response()->json(['success' => false, 'message' => 'Port is already in use.'], 400);
    }

    $logFile = "/tmp/ssh_tunnel_{$port}.log";
    $command = "nohup ssh -f -o StrictHostKeyChecking=no -N -L {$port}:{$remoteIp}:80 {$sshUser }@{$serverIp} > $logFile 2>&1 & echo $!";

    // Menjalankan perintah SSH
    $process = Process::fromShellCommandline($command);
    $process->run();

    if ($process->isSuccessful()) {
        Log::info("SSH Tunnel successfully created on port {$port}");
        return response()->json(['success' => true, 'port' => $port]);
    } else {
        Log::error("SSH Tunnel failed: " . $process->getErrorOutput());
        return response()->json(['success' => false, 'message' => 'Failed to create SSH tunnel.'], 500);
    }
}

private function isPortInUse($port)
{
    $command = "lsof -i :{$port}";
    $process = Process::fromShellCommandline($command);
    $process->run();
    
    // Check if the process output contains any results
    return $process->isSuccessful() && !empty($process->getOutput());
}
}
