@extends('layout.main')
@section('title','KASN & BANK')
@section('content')
<div class="container">
  <div class="row mb-12">
    <div>
      <h2 class="mb-4">Kas & Bank </h2>
    </div>
    <div class="ml-auto p-2">
      <div class="nav-item dropdown ">
        <button class="btn btn-primary dropdown-toggle" type="button" id="transactionDropdown" data-toggle="dropdown" aria-expanded="false">
         Transaksi
       </button>
       <ul class="dropdown-menu" aria-labelledby="transactionDropdown">
        <li><a class="dropdown-item" href="/jurnal/kasmasuk"><i class="fas fa-hand-holding-usd"></i> Kas Masuk</a></li>
        <li><a class="dropdown-item" href="/jurnal/kaskeluar"><i class="fas fa-money-bill-wave"></i> Kas Keluar</a></li>
        <li><a class="dropdown-item" href="/jurnal/transferkas"><i class="fas fa-random"></i> Transfer Kas</a></li>
      </ul>
    </div>
  </div>

</div>
<div>
  <form method="GET" action="{{ url()->current() }}" class="form-inline mb-4">
    <div class="form-group mr-2">
      <label for="date_from" class="mr-2">Dari</label>
      <input type="date" name="date_from" id="date_from" class="form-control"
      value="{{ request('date_from', \Carbon\Carbon::today()->toDateString()) }}">
    </div>
    <div class="form-group mr-2">
      <label for="date_to" class="mr-2">s/d</label>
      <input type="date" name="date_to" id="date_to" class="form-control"
      value="{{ request('date_to', \Carbon\Carbon::today()->toDateString()) }}">
    </div>
    <button type="submit" class="btn btn-primary ml-2">Cari</button>
  </form>
</div>
<!-- Grafik Chart.js -->
<p class="mb-2"><strong>Periode:</strong> {{ $date_from }} s/d {{ $date_to }}</p>
<div class="row mb-6">


  <div class="col-md-3">
    <div class="card mb-4">
      <div class="card-body">
        <div id="kasPieApex" style="height:420px"></div>
      </div>
    </div>
  </div>
  <div class="col-md-9">
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Grafik Posisi Kas & Bank</h5>

        <div id="kasBankApex" style="height:420px"></div>



      </div>
    </div>
  </div>
</div>



<!-- Tabel Data Per Akun -->
<div class="card">
  <div class="card-body">
    <h5 class="card-title">Detail Transaksi Per Akun</h5>
    <table id="example1" class="table table-bordered">
      <thead class="table-dark">
        <tr>
          <th>ID Akun</th>
          <th>Nama Akun</th>
          <th>Total Debit</th>
          <th>Total Kredit</th>
          <th>Saldo</th>
        </tr>
      </thead>
      <tbody>
        @foreach($transactionsByAccount as $transaction)
        <tr>
          <td>{{ $transaction->id_akun }}</td>
          <td>{{ $transaction->akun_name }}</td>
          <td>Rp{{ number_format($transaction->total_debit, 0, ',', '.') }}</td>
          <td>Rp{{ number_format($transaction->total_kredit, 0, ',', '.') }}</td>
          <td>Rp{{ number_format($transaction->saldo, 0, ',', '.') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Script untuk Chart.js dan DataTables -->
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script> -->
<script>
    // Inisialisasi Chart.js
  var ctx = document.getElementById('cashChart').getContext('2d');
  var cashChart = new Chart(ctx, {
    type: 'doughnut',
    data: {!! json_encode($chartData) !!},
  });

    // Inisialisasi DataTables
  $(document).ready(function() {
    $('#transactionsTable').DataTable();
  });
</script>

<script>
  var options = {
    chart: {
      type: 'bar',
      height: 400,
      stacked: true,
      toolbar: { show: false }
    },
    plotOptions: {
      bar: { horizontal: false }
    },
    dataLabels: { enabled: false },
    series: [
    {
      name: 'Total Debit',
      data: {!! json_encode($transactionsByAccount->pluck('total_debit')) !!}
    },
    {
      name: 'Total Kredit',
      data: {!! json_encode($transactionsByAccount->pluck('total_kredit')) !!}
    }
    ],
    xaxis: {
      categories: {!! json_encode($transactionsByAccount->pluck('akun_name')) !!},
      labels: {
        rotate: -45
      }
    },
    yaxis: {
      labels: {
        formatter: function(value) {
          return 'Rp ' + value.toLocaleString('id-ID', {minimumFractionDigits: 2});
        }
      }
    },
    tooltip: {
      y: {
        formatter: function(value) {
          return 'Rp ' + value.toLocaleString('id-ID', {minimumFractionDigits: 2});
        }
      }
    },
    legend: {
      position: 'top'
    },
    colors: ['#28a745', '#dc3545']
  };

  var chart = new ApexCharts(document.querySelector("#kasBankApex"), options);
  chart.render();


</script>
<script>
  var optionsPie = {
    chart: {
      type: 'pie',
      height: 350,
      toolbar: { show: false }
    },
    labels: ['Debit', 'Kredit'],
    series: [{{ $transactionsByAccount->sum('total_debit') }}, {{ $transactionsByAccount->sum('total_kredit') }}],
    colors: ['#28a745', '#dc3545'],
    tooltip: {
      y: {
        formatter: function(val){ return 'Rp ' + val.toLocaleString('id-ID', {minimumFractionDigits:2}) }
      }
    },
    legend: { position: 'bottom' },
    dataLabels: {
      formatter: function (val, opts) {
        let valNum = opts.w.config.series[opts.seriesIndex];
        return 'Rp ' + valNum.toLocaleString('id-ID', {minimumFractionDigits:2});
      }
    }
  };
  var pieChart = new ApexCharts(document.querySelector("#kasPieApex"), optionsPie);
  pieChart.render();
</script>ut.render();
</script>

@endsection
