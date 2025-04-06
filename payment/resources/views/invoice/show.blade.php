@extends('layout.main')
@section('title','Invoice List')
@section('content')
@inject('invoicecalc', 'App\Invoice')
<section class="content-header">
<div class="row col-12 justify-content-md-center">



<div class="col-5">
        <h2 class="text-center">Cari Data Pelanggan </h2>
        <div class="row pt-5">
          <div class=" border-primary border">
           <form role="form" method="post" action="/invoice/search" enctype="multipart/form-data">
            @method('POST')
            @csrf
            <div class="input-group">
              <select name="filter" id="filter" class="col-5 form-control form-control-lg">
                <option value="customer_id">CID/ Kode Pelanggan</option>
                <option value="name">Nama sesuai KTP</option>
                <option value="phone">No tlp </option>
                <option value="id_card">No KTP</option>

              </select>

              <input name="parameter" id="parameter" type="search" class="form-control form-control-lg " placeholder="Masukkan kata kunci">
              <div class="input-group-append">
                <button type="submit" class="btn btn-lg btn-default bg-success">
                  <i class="fa fa-search"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>








  <div class="card card-primary card-outline col-10 mt-5  ">
    <div class="card-header">
      <h3 class="card-title">Daftar Invoice  </h3>
     
    </div>
    <div class="card-body row">

   

  <table class="table table-borderless col-md-6 table-sm">
  
  <tbody>
    
      <tr class="col-md-6">

         <tr>
     <th style="width: 35%" class="text-right">CID / Kode Pelanggan :</th>
      <td><strong>{{$customer->customer_id}} </strong></td>
      
    </tr>
     <tr>
      <th style="width: 35%" class="text-right">Nama :</th>
      <td><strong>{{$customer->name}}</strong> </td>
      
    </tr>
    <tr>
      <th style="width: 35%" class="text-right">No Tlp :</th>
      <td>{{$customer->phone}} </td>
      
    </tr>
   <tr>
      <th style="width: 35%" class="text-right">Alamat :</th>
      <td>{{$customer->address}} </td>
      
    </tr>
    
      
    </tr>
  


  </tbody>

</table>
  <table class="table table-borderless col-md-6 table-sm">
  
  <tbody>
    
      <tr class="col-md-6">
    <tr>
      <th style="width: 25%" class="text-right">Status :</th>
      <td><strong>{{$customer->status_name}}</strong> 
       </td>
      
    </tr>
    <tr>
     <th style="width: 25%" class="text-right">Paket :</th>
      <td><strong>{{$customer->plan_name}} </strong> </td>
      
       </tr>
  <tr>
     <th style="width: 25%" class="text-right">NPWP :</th>
      <td><strong>{{strtoupper($customer->npwp)}} </strong> </td>
    </tr>
  
  


  </tbody>

</table>




</div>






<!-- /.card -->
    <!-- /.card-header --> <div class="card-body">

{{-- SUM INVOICE --}}

    <div class="">
     
         
      <table id="example1" class="table table-bordered table-striped">
       {{--  <div class="card card-primary card-outline"> --}}
   

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">No Invoice #</th>
            <th scope="col">Tanggal Invoice</th>
            
            <th scope="col">Total</th>
            <th scope="col">Status Pembayaran</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
         
      <input type="hidden" name="id_customer" value={{$customer->id}}>
    
         @foreach( $suminvoice as $suminvoice)
     
         <tr>
          <th scope="row">{{ $loop->iteration }}</th>
          
          <td>{{ $suminvoice->number }}</td>
          <td>{{ $suminvoice->date }}</td>
      
         @php
          $sub_total = $invoicecalc->balanceinv($suminvoice->tempcode, $customer->id);
          $tax = $suminvoice->tax;
           $pph = $sub_total * $suminvoice->pph/100;
          $sum_total = ($sub_total * $tax/100) + $sub_total - $pph;
         @endphp
          <td>{{number_format($sum_total, 0, ',', '.')}} </td>

          @if($suminvoice->payment_status == 0)
          
            <td style="color:white;" ><a class="badge badge-danger">{{ 'Belum Dibayar' }}</a></td>
             {{-- <td>
            <input type="checkbox" name="suminvoice_item[]" value={{ $suminvoice->id }}>
          </td> --}}
          @elseif($suminvoice->payment_status == 1)
           <td style="color:white; " ><a class="badge badge-secondary">{{ 'Sudah Dibayar' }}</a></td>
            @elseif($suminvoice->payment_status == 2)
           <td style="color:white;" ><a class="badge badge-warning">{{ 'Dibatalkan' }}</a></td>
          @endif
          <td>
         <a href="/suminvoice/{{ $suminvoice->tempcode }}" title="detail" class="btn btn-primary btn-sm "> <i class="fa fa-list-ul"> </i> Tampilkan </a>
          </td>


        </tr>
        @endforeach
        {{-- <tr> <td colspan="3"> <strong> Total</strong></td>
          <td>
       <strong> {{ number_format($total, 0, ',', '.') }} </strong> </td></tr>
        --}}
      
      </tbody>
    </table>
    

  </div>


      {{-- ITEM INVOICE --}}
   {{--  <div class="col-md-6">
     
         <form role="form" method="post" action="/invoice/make">
      @csrf
      <table id="example1" class="table table-bordered table-striped">
     

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Created At</th>
            <th scope="col">Description</th>
            <th scope="col">Price</th>
            <th scope="col">Periode</th>
            <th scope="col">Payment Status</th>
            <th scope="col">Select</th>
          </tr>
        </thead>
        <tbody>
         
      <input type="hidden" name="id_customer" value={{$customer->id}}>
      @php $total=0; @endphp
         @foreach( $invoice as $invoice)
         @php $total = $total + $invoice->amount @endphp
         <tr>
          <th scope="row">{{ $loop->iteration }}</th>
          <td>{{ $invoice->created_at }}</td>
          <td>{{ $invoice->description }}</td>
          
          <td>{{ number_format($invoice->amount, 0, ',', '.') }}</td>
          <td>{{ $invoice->periode }}</td>

          @if($invoice->payment_status == 0)
          
            <td style="color:white; background-color: blue" >{{ 'Un Invoice' }}</td>
             <td>
            <input type="checkbox" name="invoice_item[]" value={{ $invoice->id }}>
          </td>
          @elseif($invoice->payment_status == 3)
           <td style="color:white; background-color: green" >{{ 'Invoiced' }}</td>
          @endif
          


        </tr>
        @endforeach
        <tr> <td colspan="3"> <strong> Total</strong></td>
          <td>
       <strong> {{ number_format($total, 0, ',', '.') }} </strong> </td></tr>
       
      
      </tbody>
    </table>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Submit</button>
        </div>
  

</form>
  </div> --}}
</div>
</div>
</div>

</section>


{{-- 
<div class="modal fade" id="modal-additeminvoice">
  <div class="modal-dialog modal-lg">
    <div class="modal-content ">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           <div class="modal-body ">
           <div class="content-header">

<div class="card card-primary card-outline p-5">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Add Invoice Item </h3>
    </div>

<div class="form-group col-md-12">
</div>
<form role="form" method="post" action="/invoice">
                
                @csrf
      <input type="hidden" name="id_customer" value="{{$customer->id}}">
      <div class="card-body row">
        <div class="form-group col-md-12">
          <label for="description">Description</label>
          <input type="text" class="form-control @error('description') is-invalid @enderror " name="description" id="description"  placeholder="Item Description" value="{{old('description')}}">
          @error('description')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>


         <div class="form-group col-md-6">
          <label for="invoice_type">  Invoice type </label>
         <div class="input-group mb-3">
          <select name="monthly_fee" id="monthly_fee" class="form-control" onchange="typeChange();">
                   
                    <option value="0">General</option>
                     <option value="1">Monthly Fee</option>
                   
                  
          </select>
        </div>

      </div>
        <div class="form-group col-md-6">
          <label for="periode">  Periode </label>
         <div class="input-group mb-4">
          
                   
<?php $monthArray = array(
                    "01" => "January", "02" => "February", "03" => "March", "04" => "April",
                    "05" => "May", "06" => "June", "07" => "July", "08" => "August",
                    "09" => "September", "10" => "October", "11" => "November", "12" => "December",
                );
$yearArray = range(2021, 2030);
?>
<select name="periode_month" id="_periode_month" class="form-control">

    @foreach ($monthArray as $index => $month) 
        <option value={{$index}} >{{$month}}</option>';
    
    @endforeach
    
                  
          </select>
          <select name="periode_year" id="periode_year" class="form-control">
    @foreach ($yearArray as $year) 
        <option >{{$year}}</option>';
    
    @endforeach 
          </select>
        </div>

      </div>
    
         <div class="form-group col-md-6 ">
          <label for="qty">qty</label>
          <input type="text" class="form-control @error('qty') is-invalid @enderror " name="qty" id="qty"  placeholder="Item qty" value="{{old('qty')}}">
          @error('qty')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-group col-md-6">
          <label for="amount">amount</label>
          <input type="text" class="form-control @error('amount') is-invalid @enderror " name="amount" id="amount"  placeholder="Item amount" value="{{old('amount')}}">
          @error('amount')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>
        
<div class="card-footer col-md-12">
  <button type="submit" class="btn btn-primary">Submit</button>
  <a href="{{url('invoice').'/' .$customer->id}}" class="btn btn-default float-right">Cancel</a>
  </div>'
    </form>

  </div>

          </div>
          
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->


</div> --}}

@endsection
{{-- 
<script type="text/javascript">
  
   function typeChange() {
  
   var str = document.getElementById('monthly_fee').value;

      if (str==1)
      {
        document.getElementById('description').value="Monthly Fee";
      
        document.getElementById('amount').value=(JSON.parse("{{ json_encode($customer->plan_price) }}"));
        document.getElementById('qty').value="1";
      }
   
 }

</script> --}}