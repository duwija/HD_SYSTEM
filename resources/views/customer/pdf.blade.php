<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Formulir Berlangganan Internet {{ env('COMPANY_NAME') }}</title>
  <style>
    /* Reset dan dasar font */
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #333;
      margin: 0 20px;
    }

    /* Header logo & title */
    .header-table {
      width: 100%;
      margin-bottom: 20px;
      border-collapse: collapse;
    }
    .header-table td {
      border: none;
      vertical-align: top;
    }
    .header-logo {
      width: 150px;
    }
    .header-title {
      text-align: right;
      font-weight: bold;
      font-size: 18px;
      text-decoration: underline;
      color: #004085;
    }
    .header-subtitle {
      text-align: right;
      font-style: italic;
      color: #666;
      font-size: 12px;
      margin-top: 2px;
    }
    .header-address {
      text-align: right;
      font-size: 10px;
      color: #444;
      margin-top: 1px;
      white-space: pre-line; /* agar enter di env jadi baris baru */
    }

    .container {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 30px;
    }

    .col {
      width: 40%;
      flex: 1;
      padding: 10px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }
    h4 {
      margin-bottom: 10px;
      color: #0056b3;
      border-bottom: 2px solid #0056b3;
      padding-bottom: 3px;
    }
    .field-label {
      font-weight: bold;
      margin-top: 8px;
      margin-bottom: 3px;
      color: #222;
    }
    .field-value {
      padding-left: 5px;
      color: #333;
    }

    /* Table perangkat */
    table.devices-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    table.devices-table th, table.devices-table td {
      border: 1px solid #aaa;
      padding: 6px 8px;
      text-align: left;
      font-size: 11px;
    }
    table.devices-table th {
      background-color: #e9ecef;
      color: #004085;
    }

    /* Syarat & ketentuan */
    .terms {
      font-size: 11px;
      color: #444;
      margin-bottom: 1px;
    }
    .terms ol {
      padding-left: 20px;
    }

    /* Tanda tangan dan catatan */
    .signature {
      margin-top: 20px;
      font-size: 12px;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
    }
    .signature div {
      width: 45%;
      min-width: 200px;
      box-sizing: border-box;
    }
    .signature label {
      font-weight: bold;
      display: block;
      margin-bottom: 40px;
    }

    /* QR Code section */
    .qrcode-section {
      margin-top: 20px;
      text-align: center;
    }
    .qrcode-section strong {
      display: block;
      margin-bottom: 8px;
      color: #004085;
      font-size: 14px;
    }

  </style>
</head>
<body>

  <table class="header-table">
    <tr>
      <td class="header-logo">
        <img src="{{ public_path('dashboard/dist/img/logo.jpg') }}" alt="Logo {{ env('COMPANY_NAME') }}" style="max-width: 140px;">
      </td>
      <td>
        <div class="header-title">Formulir Berlangganan Internet {{ env('COMPANY_NAME') }}</div>
        <div class="header-subtitle">{{ env('COMPANY') }}</div>
        <div class="header-address">
          {{ env('COMPANY_ADDRESS1') }}
          {{ env('COMPANY_ADDRESS2') }}
        </div>
      </td>
    </tr>
  </table>

  <table style="width: 100%; margin-bottom: 30px;">
    <tr>
      <td style="width: 50%; vertical-align: top; padding-right: 10px;">
        <h4>Data Pelanggan</h4>
        <div class="field-label">CID:</div>
        <div class="field-value">{{ $customer->customer_id}}</div>
        <div class="field-label">Nama Lengkap:</div>
        <div class="field-value">{{ $customer->name }}</div>
        <div class="field-label">No KTP:</div>
        <div class="field-value">{{ $customer->id_card }}</div>

        <div class="field-label">No. HP (WhatsApp):</div>
        <div class="field-value">{{ $customer->phone }}</div>
        <div class="field-label">Email:</div>
        <div class="field-value">{{ $customer->email }}</div>
        <div class="field-label">Alamat:</div>
        <div class="field-value">{{ $customer->address }}</div>
      </td>
      <td style="width: 50%; vertical-align: top; padding-left: 10px;">
        <h4>Informasi Layanan</h4>
        <div class="field-label">Paket:</div>
        <div class="field-value">
          {{ $customer->plan_name->name ?? '-' }} - Rp. {{ number_format($customer->plan_name->price ?? 0, 0, ',', '.') }}
        </div>
        <div class="field-label">Tanggal Pendaftaran:</div>
        <div class="field-value">{{ \Carbon\Carbon::now()->format('d-m-Y') }}</div>
        <div class="field-label">Biaya Registrasi:</div>
        <div class="field-value">{{ $data['biaya_registrasi'] ?? '-' }}</div>

        
        
      </td>
    </tr>
  </table>



  <h4>Daftar Perangkat</h4>
  <table class="devices-table">
    <thead>
      <tr>
        <th style="width: 5%;">No</th>
        <th style="width: 45%;">Keterangan</th>
        <th style="width: 25%;">SN</th>
        <th style="width: 10%;">Jumlah</th>
        <th style="width: 15%;">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($data['devices'] ?? [] as $index => $device)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $device['keterangan'] ?? '-' }}</td>
        <td>{{ $device['sn'] ?? '-' }}</td>
        <td>{{ $device['jumlah'] ?? '-' }}</td>
        <td>{{ $device['status'] ?? '-' }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="5" style="text-align:center; font-style: italic;">Tidak ada data perangkat</td>
      </tr>
      @endforelse
    </tbody>
  </table>

  <p><strong>Catatan Tambahan:</strong> {{ $data['keterangan_tambahan'] ?? '-' }}</p>


  <h4>Syarat & Ketentuan Berlangganan Layanan Internet {{ env('COMPANY_NAME') }}</h4>
  <div class="terms">
    <ol>
      <li>Berlangganan minimal 1 tahun (12 bulan)</li>
      <li>Perangkat Wifi dan kabel status dipinjamkan, wajib dikembalikan saat berhenti berlangganan</li>
      <li>Downgrade paket hanya setelah 6 bulan berlangganan</li>
      <li>Jika poin 1 & 2 dilanggar, dikenakan sanksi Rp500.000</li>
      <li>Jatuh tempo pembayaran  tanggal 20 setiap bulan</li>
      <li>Layanan akan dinonaktifkan jika tidak membayar hingga tanggal 20</li>
      <li>Hubungi CS untuk gangguan, teknis, dan informasi promo</li>
    </ol>

    Dengan menandatangani formulir ini, saya menyatakan bahwa seluruh informasi yang saya berikan adalah benar
    dan saya menyetujui syarat dan ketentuan berlangganan layanan internet {{ env('COMPANY_NAME') }}.
  </div>

  

  

  <div class="signature">



   <table style="width: 100%; margin-bottom: 30px;">
    <tr>
      <td style="width: 30%; vertical-align: top; padding-right: 10px; text-align: center;">
        <label style="display: block; font-weight: bold; margin-bottom: 60px;">Gianyar, <?php
echo date('d-m-Y'); // Output: 15-05-2025
?> <br> {{ env('COMPANY_NAME') }}</label>
<p><strong>{{ $data['ttd_nama'] ?? '-' }}</strong></p>
</td>
<td style="width: 30%; vertical-align: top; padding-right: 10px; text-align: center;">
  <label style="display: block; font-weight: bold; margin-bottom: 75px;">Pelanggan</label>
  <p><strong>{{ $customer->name }}</strong></p>
</td>
<td style="width: 30%; vertical-align: top; padding-right: 10px; text-align: center;">
 <div class="field-label">Link Pembayaran:</div>
 <img src="data:image/png;base64,{{ $qrcode }}" style="width: 150px;" alt="QR Code">
</td>
</tr>
</table>
</div>


</body>
</html>
