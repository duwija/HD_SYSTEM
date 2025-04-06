
@extends('layout.main')
@section('title','Customer On Trash List')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">TRASH </h3>

     
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      
      <table id="example1" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Customer Id</th>
            <th scope="col">Name</th>
             <th scope="col">Address</th>
            <th scope="col">Plan</th>
            {{-- <th scope="col">Status</th> --}}
            <th scope="col">Delete At</th>

            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
         @foreach( $customer as $customer)
         <tr>
          <th scope="row">{{ $loop->iteration }} </th>

          {{-- <td><a class="btn btn-primary btn-sm" href="/customer/{{ $customer->id }}" >{{ $customer->customer_id }}</a></td> --}}
           <td><a class="btn btn-primary btn-sm">{{ $customer->customer_id }}</a></td>
          <td>{{ $customer->name }} </td>

            <td> <a style="font-size: 13px"> {{ $customer->address }}</a></td>

          @if( $customer->id_plan == null)


          <td> none</td>


          @else
          <td>{{ $customer->plan_name->name }} ( {{ number_format($customer->plan_name->price)}})</td>

          @endif

         {{--  
          @if( $customer->id_status == null)


          <td> none</td>


          @else


@php

             if ($customer->status_name->name == 'Active')
        $badge_sts = "badge-success";
      elseif ($customer->status_name->name == 'Inactive')
         $badge_sts = "badge-secondary";
       elseif ($customer->status_name->name == 'Block')
         $badge_sts = "badge-danger";
       elseif ($customer->status_name->name == 'Company_Properti')
         $badge_sts = "badge-primary";
       else
         $badge_sts = "badge-warning";

      @endphp
     



          <td class="text-center"><a class="badge text-white {{$badge_sts}}">{{ $customer->status_name->name }}</a></td>

          @endif --}}
          <td class="text-center"><a>{{ $customer->deleted_at }}</a></td>
       
          <td >
            <div class="float-right " >
            
             
              {{-- <a href="https://www.google.com/maps/place/{{ $customer->coordinate }}" target="_blank" class="btn btn-info btn-sm "><i title="show map" class="fa fa-map"> </i> </a> --}}

             <form  action="/customer/restore/{{$customer->id}}" method="POST" class="d-inline item-restore " >
                @method('patch')
                @csrf

                <button title="Restore" type="submit"  class="btn btn-warning btn-sm float-right">  Restore </button>
              </form>

             {{--  <a href="/customer/{{ $customer->id }}" title="detail" class="btn btn-primary btn-sm "> <i class="fa fa-list-ul"> </i> </a> --}}
             {{--  <a href="/customer/{{ $customer->id }}/edit" title="edit" class="btn btn-primary btn-sm "> <i class="fa fa-edit"> </i> </a>
 --}}

            {{--   <form  action="/customer/{{ $customer->id }}" method="POST" class="d-inline customer-delete" >
                @method('delete')
                @csrf

                <button title="Delete" type="submit"  class="btn btn-danger btn-sm"> <i class="fa fa-times"> </i> </button>
              </form> --}}

            </div>
          </td>

        </tr>
        @endforeach
  

      </tbody>
          
 
</select>

    </table>


  </div>
</div>

</section>

@endsection
 