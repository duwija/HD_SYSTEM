@extends('layout.main')

@section('content')
<div class="container">



  <div class="row justify-content-md-center">


    <div class="col-7 pt-5">
      <h2 class="text-center">Cari Data Pelanggan </h2>
      <div class="row pt-5">
        <div class="col-12">
          <form role="form" method="post" action="/payment/show" enctype="multipart/form-data">
            @method('POST')
            @csrf
            <div class="input-group">
              <select name="filter" id="filter" class="form-control form-control-lg" required>
                <option value="customer_id">CID/ Kode Pelanggan</option>
                <option value="name">Nama sesuai KTP</option>
                <option value="phone">No tlp </option>
                <!-- <option value="id_card">No KTP</option> -->

              </select>

              <input name="parameter" id="parameter" type="search" class="form-control form-control-lg " placeholder="Masukkan kata kunci" required>
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
    <div>

    </div>
  {{--  </section> --}}
</div>











<!-- /.content-wrapper -->
</div>
@endsection
