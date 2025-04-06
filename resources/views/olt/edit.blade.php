@extends('layout.main')
@section('title', 'Edit OLT')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Edit OLT</h3>
    </div>

    <div class="card-body">
      @if ($errors->any())
      <div class="alert alert-danger">
        <ul>
          @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
      @endif

      <form role="form" action="{{url ('olt')}}/{{ $olt->id }}" method="POST">
        @method('patch')
        @csrf

        <div class="card-body row">
          <div class="form-group col-md-3">
            <label for="nama">Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror " name="name" id="name"  placeholder="Enter OLT Name" value="{{$olt->name}}">
            @error('name')
            <div class="error invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group col-md-3">
            <label for="site location">  Olt Type </label>
            <div class="input-group mb-3">
              <select name="type" id="type" class="form-control">
               <option value="zte" {{ $olt->type == 'zte' ? 'selected' : '' }}>ZTE</option>
               <option value="huawei" {{ $olt->type == 'huawei' ? 'selected' : '' }}>HUAWEI</option>
               <option value="vsol" {{ $olt->type == 'vsol' ? 'selected' : '' }}>VSOL</option>
               <option value="hioso" {{ $olt->type == 'hioso' ? 'selected' : '' }}>HIOSO</option>

             </select>
           </div>

         </div>
         <div class="form-group col-md-3">
          <label for="ip">IP Address</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="ip" id="ip"  placeholder="Enter IP Address" value="{{$olt->ip}}">

        </div>
        <div class="form-group col-md-3">
          <label for="port">Telnet Port</label>
          <input type="text" class="form-control @error('port') is-invalid @enderror" name="port" id="port"  placeholder="Enter Telnet Port default: 23" value="{{$olt->port}}">
        </div>
        <div class="form-group col-md-3">
          <label for="user">Telnet user</label>
          <input type="text" class="form-control @error('user') is-invalid @enderror" name="user" id="user"  placeholder="Enter Telnet user" value="{{$olt->user}}">
        </div>
        <div class="form-group col-md-3">
          <label for="password">Password</label>
          <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password"  placeholder="Enter Telnet password" value="{{$olt->password}}">
        </div>
        <div class="form-group col-md-3">
          <label for="community_ro">Read Community</label>
          <input type="text" class="form-control @error('community_ro') is-invalid @enderror" name="community_ro" id="community_ro"  placeholder="Enter Read Community" value="{{$olt->community_ro}}">
        </div>
        <div class="form-group col-md-3">
          <label for="community_rw">Write Community</label>
          <input type="text" class="form-control @error('community_rw') is-invalid @enderror" name="community_rw" id="community_rw"  placeholder="Enter Write Community" value="{{$olt->community_rw}}">
        </div>
        <div class="form-group col-md-3">
          <label for="snmp_port">Snmp Port</label>
          <input type="text" class="form-control @error('snmp_port') is-invalid @enderror" name="snmp_port" id="snmp_port"  placeholder="Enter Telnet snmp port default :161" value="{{$olt->snmp_port}}">
        </div>





      </div>
      <!-- /.card-body -->

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="{{url('olt')}}" class="btn btn-default float-right">Cancel</a>
      </div>
    </form>
  </div>
</div>
</section>
@endsection
