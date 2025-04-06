@extends('layout.main')
@section('title', 'Laporan Neraca')

@section('content')
<div class="container">
  <h2 class="text-center">Laporan Neraca</h2>
  <hr>

  <div class="row">
    <!-- Aktiva -->
    <div class="col-md-6">
      <h4>Aktiva</h4>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Akun</th>
            <th>Saldo</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($aktiva as $akun)

          @include('jurnal.akun_row', ['akun' => $akun, 'level' => 0])
          @php
          
          @endphp
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <th>Total Aktiva</th>
            <th>{{ number_format($totalAktiva, 2) }}</th>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Kewajiban dan Ekuitas -->
    <div class="col-md-6">
      <h4>Kewajiban dan Ekuitas</h4>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Akun</th>
            <th>Saldo</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="2"><strong>Kewajiban</strong></td>
          </tr>
          
          @foreach ($kewajiban as $akun)
          @include('jurnal.akun_row', ['akun' => $akun, 'level' => 0])
          @endforeach

          <tr>
            <td colspan="2"><strong>Ekuitas</strong></td>
          </tr>
          
          @foreach ($ekuitas as $akun)
          @include('jurnal.akun_row', ['akun' => $akun, 'level' => 0])
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <th>Total Kewajiban dan Ekuitas</th>
            <th>{{ number_format($totalKewajiban + $totalEkuitas, 2) }}</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection
