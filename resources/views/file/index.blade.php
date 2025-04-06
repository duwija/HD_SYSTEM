@extends('layout.main')
@section('title','Backup File')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Backup File  </h3>
      
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <table id="example1" class="table table-bordered table-striped">

        <thead >
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">File Date</th>
            <th scope="col">Action</th>
            <!-- <th scope="col">Action</th> -->
          </tr>
        </thead>
        <tbody>
         @foreach($files as $file)
         <tr>
           <th scope="row">{{ $loop->iteration }}</th>
           <td>   {{ $file->getFilename() }}</td>
           <td> {{ date('Y-m-d H:i:s', $file->getMTime()) }} </td>
           <td> 
            <div class="row " >
              <a class="btn btn-primary m-2" href="{{ route('file.download', $file->getFilename()) }}"> Download </a>
              <form action="{{ route('file.delete', $file->getFilename()) }}" method="post">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger m-2" type="submit">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach

      </tbody>
    </table>
  </div>
</div>

</section>

@endsection
