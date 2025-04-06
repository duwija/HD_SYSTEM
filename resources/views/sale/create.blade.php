@extends('layout.main')
@section('title','Add New Sales')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Add New Sles </h3>
    </div>
    <form role="form" method="post" action="/sale" enctype="multipart/form-data">
      @csrf
      <div class="card-body">
        <div class="row">
          <div class="form-group col-sm-3" >
            <label for="nama">Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Sales Name" value="{{old('name')}}">
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group col-sm-3" >
            <label for="nama">full Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="full_name" id="full_name"  placeholder="Employee Full Name" value="{{old('full_name')}}">
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group col-sm-3">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror " name="date_of_birth" id="date_of_birth" value="{{old('date_of_birth')}}">
            @error('date_of_birth')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="row">
     
     <div class="form-group col-sm-3">
       <label for="email"> Email  </label>
       <input type="text" class="form-control @error('email') is-invalid @enderror" name="email"  id="email" placeholder="email" value="{{old('email')}}">
       @error('email')
       <div class="error invalid-feedback">{{ $message }}</div>
       @enderror
     </div>
     <div class="form-group col-sm-3">
     <label for="password"> Password </label>
     <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"  id="password" placeholder="password" value="{{old('password')}}">
     @error('password')
     <div class="error invalid-feedback">{{ $message }}</div>
     @enderror
   </div>
        
   </div>
       {{--  <div class="row">
     
        
      </div> --}}
      <div class="row">
         <div class="form-group col-sm-3">
          <label for="employee_type"> Sales Type </label>
          <div class="input-group mb-3">
            
              <select name="sale_type" id="sale_type" class="form-control">
            <option value="Full Time">Full Time</option>
             <option value="Part Time">Part Time</option>
              <option value="Fixed-Term Contract">Fixed-Term Contract</option>
             
           </select>
            
            
          </div>
        </div>
        <div class="form-group col-sm-3">
         <label for="join_date"> Join Date </label>
         <input type="date" class="form-control @error('join_date') is-invalid @enderror" name="join_date"  id="join_date" value="{{old('join_date')}}">
         @error('join_date')
         <div class="error invalid-feedback">{{ $message }}</div>
         @enderror
       </div>
       <div class="form-group col-sm-3">
         <label for="address"> Address </label>
         <input type="text" class="form-control @error('address') is-invalid @enderror" name="address"  id="address" placeholder="address" value="{{old('address')}}">
         @error('address')
         <div class="error invalid-feedback">{{ $message }}</div>
         @enderror
       </div>
     </div>
    
   <div class="row">
     <div class="form-group col-sm-3">
       <label for="phone"> Phone </label>
       <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone"  id="phone" placeholder="phone" value="{{old('phone')}}">
       @error('phone')
       <div class="error invalid-feedback">{{ $message }}</div>
       @enderror
     </div>
   <div class="form-group col-sm-6">
    <label for="description">Note  </label>
    <input type="text" class="form-control @error('description') is-invalid @enderror" name="description" id="description" placeholder="Note " value="{{old('description')}}">
    @error('description')
    <div class="error invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>
<div class="form-group">
  <label>Upload Photo</label>
  <input type="file" class="form-control-file m-3" name="photo" id="photo">
</div>










<div class="form-group">
  <input type="hidden" name="create_at" value="{{now()}}" >
</div>



</div>
<!-- /.card-body -->
<div class="card-footer">
                  <button type="submit" class="btn btn-primary">Submit</button>
                  <a href="{{url('sale')}}" class="btn btn-default float-right">Cancel</a>
                </div>

</form>






<!-- /.card -->
</div>
<!-- Form Element sizes -->


</div>

</section>

@endsection