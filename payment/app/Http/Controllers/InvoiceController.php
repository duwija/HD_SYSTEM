<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        dd($request);

        $filter ='customers.'.$request->filter;
        $val =$request->parameter ;
        $customer_count = \App\Customer::where($filter,$val)->get();

        if ($customer_count->count() > 1)
        {
            return view ('customer/index',['customer' =>$customer_count]);
        }
        else
        {

            $customer = \App\Customer::where($filter,$val)

            ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
            ->Join('plans', 'customers.id_plan', '=', 'plans.id')
            ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')->first();

            if ($customer == null)
            {
               return redirect ('/home')->with('error',' Data client '.$val.' Tidak Ditemukan !!');
           }
           else
           {
            $suminvoice = \App\Suminvoice::where('id_customer', $customer->id)
            ->orderBy('date','DESC')
            ->limit(15)
            ->get();
            
            

            
            return view ('invoice/show',['suminvoice' =>$suminvoice, 'customer'=>$customer]);
        }
    }
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
