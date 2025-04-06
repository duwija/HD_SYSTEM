@extends('layout.main')

@section('title', 'Laporan Neraca Saldo')

@section('content')
<div class="container mt-5">
  <h2 class="text-center">Laporan Neraca Saldo</h2>
  <form action="/jurnal/neracasaldo" method="GET" class="mb-4">
    <div class="row">
      <div class="col-md-3">
        <label for="tanggal_awal">Tanggal Awal</label>
        <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}">
      </div>
      <div class="col-md-3">
        <label for="tanggal_akhir">Tanggal Akhir</label>
        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
      </div>
      <div class="col-md-2">
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-primary btn-block">Filter</button>
      </div>
    </div>
  </form>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Daftar Akun</th>
        <th colspan="2" class="text-center">Saldo Awal</th>
        <th colspan="2" class="text-center">Pergerakan</th>
        <th colspan="2" class="text-center">Saldo Akhir</th>
      </tr>
      <tr>
        <th>Nama Akun</th>
        <th>Debit</th>
        <th>Kredit</th>
        <th>Debit</th>
        <th>Kredit</th>
        <th>Debit</th>
        <th>Kredit</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($data as $item)
      <tr>
        <td>{{ $item['kode'] }} - {{ $item['nama'] }}</td>
        <td>{{ number_format($item['saldo_awal_debit'], 2) }}</td>
        <td>{{ number_format($item['saldo_awal_kredit'], 2) }}</td>
        <td>{{ number_format($item['pergerakan_debit'], 2) }}</td>
        <td>{{ number_format($item['pergerakan_kredit'], 2) }}</td>
        <td>{{ number_format($item['saldo_akhir_debit'], 2) }}</td>
        <td>{{ number_format($item['saldo_akhir_kredit'], 2) }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th>Total</th>
        <th>{{ number_format($data->sum('saldo_awal_debit'), 2) }}</th>
        <th>{{ number_format($data->sum('saldo_awal_kredit'), 2) }}</th>
        <th>{{ number_format($data->sum('pergerakan_debit'), 2) }}</th>
        <th>{{ number_format($data->sum('pergerakan_kredit'), 2) }}</th>
        <th>{{ number_format($data->sum('saldo_akhir_debit'), 2) }}</th>
        <th>{{ number_format($data->sum('saldo_akhir_kredit'), 2) }}</th>
      </tr>
    </tfoot>
  </table>
</div>
@endsection
