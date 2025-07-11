
@extends('layout.main')
@section('title','Customer List')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

  <div class="card card-info">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Show Detail Sales </h3>
    </div>
    
    <div class="card-body ">
      <div class="table-bordered p-2 rounded-sm mb-3">
       <div class="row">
        <div class="form-group col-md-3">
          <label style="width: 25%;"  for="nama" class="text-right">Name :</label>
          <a class="p-md-2">{{$sale->name}}</a>
        </div>
        <div class="form-group col-md-8">
          <label style="width: 10%;"  for="full_name" class="text-right">Full Name :  </label>
          <a class="p-md-2">{{$sale->full_name}}</a>
        </div>
      </div>

      <div class="row">
        <div class="form-group col-md-3">
          <label style="width: 25%;"  for="phone" class="text-right">Phone :</label>
          <a class="p-md-2">{{$sale->phone}}</a>

        </div>
        <div class="form-group col-md-8">
          <label style="width: 10%;"  for="address" class="text-right">Address :</label>
          <a class="p-md-2">{{$sale->address}}</a>

        </div>

      </div>
      <div class="row">
        <div class="form-group col-md-3">
         <label style="width: 25%;"  for="sale_type" class="text-right"> Sale Type : </label>
         <a  class="p-md-2">{{$sale->sale_type}}</a>

       </div>
       <div class="form-group col-md-8">
        <label style="width: 10%;"  for="description" class="text-right"> Note :</label>
        <a class="p-md-2">{{$sale->description}}</a>
      </div>
    </div>
  </div>


  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">{{$sale->name}}'s Customers </h3>

      
    </div>

    <div class="row pt-2 pl-4">


     <div class="form-group col-md-2">
      <label for="site location">  Filter By </label>
      <div class="input-group mb-3">
        <select name="filter" id="filter" class="form-control">

          <option value="name">Name</option>
          <option value="customer_id">Customer ID</option>
          <option value="address">Address</option>
          <option value="phone">Phone</option>
          <option value="id_card">Id Card</option>
          <option value="billing_start">Billing Start</option>

        </select>
      </div>
    </div>
    <div class="form-group col-md-2">
      <label for="site location">  Parameter </label>
      <div class="input-group mb-3">
        <input class="form-control" type="text" id="parameter" name="parameter" placeholder="Leave blank for all">
      </div>
    </div>

    <div class="form-group col-md-2">
      <label for="site location">  Status </label>
      <div class="input-group mb-3">
        <select name="id_status" id="id_status" class="form-control">
         <option value="">All</option> 
         @foreach ($status as $id => $name)
         <option value="{{ $id }}">{{ $name }}</option>
         @endforeach
       </select>
     </div>
   </div>
   <div class="form-group col-md-2">
    <label for="site location">  Plan </label>
    <div class="input-group mb-3">
      <select name="id_plan" id="id_plan" class="form-control">
       <option value="">All</option> 
       @foreach ($plan as $id => $name)
       <option value="{{ $id }}">{{ $name }}</option>
       @endforeach
     </select>
   </div>
 </div>
 <input type="hidden" value="{{$sale->id}}" name="id_sale" id="id_sale">
 <div class="form-group col-md-2">
  <label for="site location">   </label>

  <div class="input-group p-1 col-md-3">
   <button type="button" id="sale_customer_filter" name="customer_filter" class="btn btn-warning">Filter</button>
 </div> 
</div>
</div>

<!-- /.card-header -->
<div class="card-body">
 <form role="form" method="post" action="/sale/customer/{{$sale->id}}">
   @method('patch')
   @csrf
   <table id="table-sale-customer" class="table table-bordered table-striped">

    <thead >
      <tr>
        <th scope="col">#</th>
        <th scope="col">Customer Id</th>
        <th scope="col">Name</th>
        <th scope="col">Address</th>
        <th scope="col">Plan</th>
        <th scope="col">Price</th>
        <th scope="col">Billing Start</th>
        <th scope="col">Status</th>
        <th scope="col">Invoice</th>
        
      </tr>
    </thead>


  </select>

</table>

</form>
</div>
</div>

</section>

@endsection

