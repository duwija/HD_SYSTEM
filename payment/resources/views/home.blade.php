@extends('layouts.main')

@section('content')
<div class="container mt-5">

  <div class="row justify-content-md-center">
    <div class="col-md-8">
      <div class="card border-primary">
        <div class="card-header text-center">
          <h2>Cari Data Pelanggan</h2>
        </div>
        <div class="card-body">
          <form role="form" method="post" action="/invoice/search" enctype="multipart/form-data">
            @method('POST')
            @csrf
            <div class="form-group">
              <label for="filter">Pilih Kriteria Pencarian</label>
              <select name="filter" id="filter" class="form-control form-control-lg">
                <option value="customer_id">CID / Kode Pelanggan</option>
                <option value="name">Nama sesuai KTP</option>
                <option value="phone">No Telepon</option>
                <option value="id_card">No KTP</option>
              </select>
            </div>

            <div class="input-group mb-3">
              <input name="parameter" id="parameter" type="search" class="form-control form-control-lg" placeholder="Masukkan kata kunci" required>
              <div class="input-group-append">
                <button type="submit" class="btn btn-lg btn-success">
                  <i class="fa fa-search"></i> Cari
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection