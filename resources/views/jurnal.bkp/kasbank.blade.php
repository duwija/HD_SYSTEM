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

<!-- Grafik Chart.js -->
<div class="row mb-6">


  <div class="col-md-3">
    <div class="card mb-4">
      <div class="card-body">
        <canvas id="cashChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-9">
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Grafik Posisi Kas & Bank</h5>
        <canvas id="kasBankChart"></canvas>
      </div>
    </div>
  </div>
</div>



<!-- Tabel Data Per Akun -->
<div class="card">
  <div class="card-body">
    <h5 class="card-title">Detail Transaksi Per Akun</h5>
    <table class="table table-bordered">
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
  document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById("kasBankChart").getContext("2d");

    const kasBankChart = new Chart(ctx, {
      type: "bar",
      data: {
        labels: {!! json_encode($transactionsByAccount->pluck('akun_name')) !!},
        datasets: [
        {
          label: "Total Debit",
          data: {!! json_encode($transactionsByAccount->pluck('total_debit')) !!},
          backgroundColor: "#28a745",
        },
        {
          label: "Total Kredit",
          data: {!! json_encode($transactionsByAccount->pluck('total_kredit')) !!},
          backgroundColor: "#dc3545",
        }
        ]
      },
      options: {
        responsive: true,
        scales: {
          x: { 
            stacked: true 
          },
          y: { 
            beginAtZero: true, 
            stacked: true 
          }
        }
      }
    });
  });
</script>

@endsection
