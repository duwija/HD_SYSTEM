<?php

namespace App\Http\Controllers;

use App\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function show(Request $request)
    {
        $filter ='customers.'.$request->filter;
        $val =$request->parameter ;
        $customer_count = \App\Customer::where($filter,$val)->get();

        if ($customer_count->count() > 1)
        {
            return view ('payment/index',['customer' =>$customer_count]);
        }
        else
        {

            $customer = \App\Customer::where($filter,$val)

            ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
            ->Join('plans', 'customers.id_plan', '=', 'plans.id')
            ->select('customers.*','statuscustomers.name as status_name','plans.name as plan_name','plans.price as plan_price')->first();

            if ($customer == null)
            {
                return redirect ('/payment')->with('error',' Data client '.$val.' Tidak Ditemukan !!');
            }
            else
            {
                $suminvoice = \App\Suminvoice::where('id_customer', $customer->id)
                ->orderBy('date','DESC')
                ->limit(15)
                ->get();
                
                



                return view ('payment/show',['suminvoice' =>$suminvoice, 'customer'=>$customer]);
            }
        }
    }
    public function search()
    {
        return view ('payment/search');
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
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
