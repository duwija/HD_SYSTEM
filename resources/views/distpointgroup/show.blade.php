@extends('layout.main')
@section('title','Distpoint Group Detail')

@section('content')
<section class="content-header">
  <div class="col-md-12 mx-auto mb-4">
    <div class="card shadow-lg rounded-4 h-100 border-info">
      <div class="card-header bg-info text-white d-flex justify-content-between align-items-center rounded-top">
        <h5 class="mb-0">
          <i class="fas fa-sitemap"></i> Informasi Group: {{ $distpointgroup->name }}
        </h5>
      </div>
      <div class="card-body">
        <div class="row col-md-12">
          <div class="mb-4 col-md-4">
            <strong>Kapasitas</strong>
            <div class="font-weight-bold">{{ $customer_group_count }}/{{ $distpointgroup->capacity }}</div>
            @php
            $percentage = ($customer_group_count / $distpointgroup->capacity) * 100;
            $progressClass = $percentage <= 69 ? 'bg-success' : ($percentage <= 89 ? 'bg-warning' : 'bg-danger');
            @endphp
            <div class="progress mb-2">
              <div class="progress-bar {{ $progressClass }}" role="progressbar"
              style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}"
              aria-valuemin="0" aria-valuemax="100">
              {{ number_format($percentage, 2) }}%
            </div>
          </div>
        </div>

        <div class="mb-3 col-md-4">
          <strong>Jumlah ODP</strong>
          <div class="d-flex align-items-center">
            <span class="btn btn-sm bg-secondary p-1">
              <i class="fas fa-network-wired p-1"></i> {{ $group_distpoint_count }}
            </span>
          </div>
        </div>

        <div class="mb-3 col-md-4">
          <strong>Total Port ODP</strong>
          <div class="d-flex align-items-center">
            <span class="btn btn-sm badge-primary text-white p-1">
              <i class="fas fa-plug p-1"></i> {{ $group_total_capacity }}
            </span>
          </div>
        </div>
      </div>

      <div id="chart_div_distpoint" class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
        <div style="min-width: max-content;"></div>
      </div>
    </div>

    <div class="card-footer">
      <a href="/distpointgroup/{{ $distpointgroup->id }}/edit" class="btn btn-primary btn-sm">Edit</a>
      <form action="/distpointgroup/{{ $distpointgroup->id }}" method="POST" class="d-inline item-delete">
        @csrf
        @method('delete')
        <button type="submit" class="btn btn-danger btn-sm float-right">Delete</button>
      </form>
    </div>
  </div>
</div>

<div class="col-md-12 mx-auto mb-4">
  <div class="card shadow-lg rounded-4 h-100 border-info">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center rounded-top">
      <h5 class="mb-0">
        <i class="fas fa-map-marked-alt"></i> Peta Lokasi ODP & Customer Group: {{ $distpointgroup->name }}
      </h5>
    </div>
    <div class="">



      <div id="map" style="height: 700px; width: 100%; border:1px solid #ccc;" class="rounded shadow"></div>
    </div>
  </div>
</div>
</section>
@endsection

@section('footer-scripts')
<!-- Org Chart Styles -->
<style>
  .google-visualization-orgchart-node {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 10px;
    padding: 8px;
    box-shadow: 2px 2px 6px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
  }

  .google-visualization-orgchart-node:hover {
    background-color: #e3f2fd !important;
    transform: scale(1.02);
    cursor: pointer;
  }

  .google-visualization-orgchart-lineleft,
  .google-visualization-orgchart-lineright,
  .google-visualization-orgchart-linetop,
  .google-visualization-orgchart-linebottom {
    border-color: #adb5bd !important;
  }
  .custom-control-box {
    background: white;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    font-size: 14px;
    min-width: 180px;
  }
</style>

<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
  google.charts.load('current', {packages:["orgchart"]});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Name');
    data.addColumn('string', 'Manager');
    data.addColumn('string', 'ToolTip');
    data.addRows([
      @foreach ($distpoint_chart as $dp)
      [
        { v: '{{ $dp->id }}', f: `{!! $dp->name !!} @if($dp->id == $distpointgroup->id)<div style="color:blue; font-weight:bold;">(selected)</div>@endif` },
        {!! json_encode((string) $dp->parrent) !!},
        {!! json_encode($dp->name) !!}
        ]@if (!$loop->last),@endif
      @endforeach
      ]);
    var chart = new google.visualization.OrgChart(document.getElementById('chart_div_distpoint'));
    chart.draw(data, {allowHtml: true});
    google.visualization.events.addListener(chart, 'select', function() {
      var selection = chart.getSelection();
      if (selection.length > 0) {
        var distpointId = data.getValue(selection[0].row, 0);
        window.location.href = '/distpoint/' + distpointId;
      }
    });
  }
</script>

<!-- Leaflet & Plugins -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-measure@3.3.0/dist/leaflet-measure.css" />
<script src="https://unpkg.com/leaflet-measure@3.3.0/dist/leaflet-measure.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<link href="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.13.0/dist/leaflet-geoman.css" rel="stylesheet" />
<script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.13.0/dist/leaflet-geoman.min.js"></script>

<script>
  const map = L.map('map').setView([-6.2, 106.8], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

    // Menangani klik pada peta untuk mendapatkan koordinat
  map.on('contextmenu', function(e) {
    const latlng = e.latlng; // Mendapatkan koordinat lat, lng
    const lat = latlng.lat.toFixed(6); // Membulatkan menjadi 6 digit
    const lng = latlng.lng.toFixed(6);
      // Menampilkan koordinat dalam popup
    L.popup()
    .setLatLng(latlng)
    .setContent(`Koordinat yang Anda klik:<br> ${lat}, ${lng}`)
    .openOn(map);

    // Atau menampilkan dalam console
    console.log(`Koordinat yang Anda klik: Lat: ${lat}, Lng: ${lng}`);
  });


// Aktifkan kontrol Geoman (pengukuran & penggambaran)
  map.pm.addControls({
    position: 'topleft',
    drawCircle: false,
    drawMarker: false,
  drawPolyline: true, // aktifkan menggambar garis
  editMode: true,
  dragMode: false,
  cutPolygon: false,
  removalMode: true
});
  const iconRed = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });

  const iconBlue = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });

  const locations = @json($locations);

  const odpMarkers = [];
  const customerMarkers = [];
  const odpLines = [];
  const customerLines = [];
  const allMarkers = [];

  // Custom control box di dalam map
  const filterControl = L.control({ position: 'topright' });

  filterControl.onAdd = function (map) {
    const div = L.DomUtil.create('div', 'custom-control-box leaflet-bar');

    div.innerHTML = `
    <div class="form-check mb-1">
    <input class="form-check-input" type="checkbox" id="toggle-odp" checked>
    <label class="form-check-label text-dark" for="toggle-odp">ODP (Merah)</label>
    </div>
    <div class="form-check mb-1">
    <input class="form-check-input" type="checkbox" id="toggle-customer" checked>
    <label class="form-check-label text-dark" for="toggle-customer">Customer (Biru)</label>
    </div>
    <input type="text" id="search-input" class="form-control form-control-sm mt-1" placeholder="Cari ODP / Customer...">
    `;

    // Supaya checkbox & input bisa diklik tanpa memicu drag map
    L.DomEvent.disableClickPropagation(div);
    return div;
  };

  filterControl.addTo(map);

  locations.forEach(loc => {
    if (!loc.coordinate) return;
    const [lat, lng] = loc.coordinate.split(',').map(Number);
    const icon = loc.type === 'distpoint' ? iconRed : iconBlue;

    let popupContent = '';
    if (loc.type === 'distpoint') {
    // Popup untuk ODP (Distribution Point)
      popupContent = `
      <a href="/distpoint/${loc.id}" target="_blank">${loc.name}</a> <br>
      <b>Port:</b> ${loc.capacity ?? ''}<br>
      ${loc.description ?? ''}<br>
      
      `;
    } else {
    // Popup untuk Customer
      popupContent = `
      CID:<a href="/customer/${loc.id}" target="_blank"><b> ${loc.cid}</b></a><br>
      <b>Nama:</b> ${loc.name}<br>
      <b>Status:</b> ${loc.status}<br>
      
      `;
    }

    const marker = L.marker([lat, lng], { icon }).bindPopup(popupContent);
    marker.options.customName = loc.name.toLowerCase();

    allMarkers.push(marker);
    if (loc.type === 'distpoint') {
      odpMarkers.push(marker);
      marker.addTo(map);
    } else {
      customerMarkers.push(marker);
      marker.addTo(map);
    }

    if (loc.parent_coordinate) {
      const [plat, plng] = loc.parent_coordinate.split(',').map(Number);
      const line = L.polyline([[lat, lng], [plat, plng]], {
        color: loc.type === 'customer' ? 'blue' : 'red',
        weight: 1.5,
        opacity: 0.7,
        dashArray: loc.type === 'customer' ? '5, 5' : null
      }).addTo(map);

      if (loc.type === 'distpoint') {
        odpLines.push(line);
      } else {
        customerLines.push(line);
      }
    }
  });

  if (allMarkers.length > 0) {
    const group = new L.featureGroup(allMarkers);
    map.fitBounds(group.getBounds().pad(0.2));
  }

  // Event toggle ODP (merah)
  document.getElementById('toggle-odp').addEventListener('change', e => {
    if (e.target.checked) {
      odpMarkers.forEach(marker => map.addLayer(marker));
      odpLines.forEach(line => map.addLayer(line));
    } else {
      odpMarkers.forEach(marker => map.removeLayer(marker));
      odpLines.forEach(line => map.removeLayer(line));
    }
  });

  // Event toggle Customer (biru)
  document.getElementById('toggle-customer').addEventListener('change', e => {
    if (e.target.checked) {
      customerMarkers.forEach(marker => map.addLayer(marker));
      customerLines.forEach(line => map.addLayer(line));
    } else {
      customerMarkers.forEach(marker => map.removeLayer(marker));
      customerLines.forEach(line => map.removeLayer(line));
    }
  });

  // Search input filter marker (hanya marker, tanpa garis)
  document.getElementById('search-input').addEventListener('input', e => {
    const keyword = e.target.value.toLowerCase();
    allMarkers.forEach(marker => {
      const match = marker.options.customName.includes(keyword);
      if (match) map.addLayer(marker);
      else map.removeLayer(marker);
    });
  });

  // =========================
  // Tambah beberapa tools Leaflet populer
  // =========================

  // 1. Leaflet.MeasureControl (harus load plugin measure terlebih dahulu)
  // Contoh plugin measure: https://github.com/ljagis/leaflet-measure
  if (typeof L.Control.Measure === 'function') {
    L.control.measure({
      primaryLengthUnit: 'meters',
      primaryAreaUnit: 'sqmeters',
      activeColor: '#db4a29',
      completedColor: '#9b2d14'
    }).addTo(map);
  }

  // 2. Leaflet.Control.Geocoder (harus load plugin geocoder)
  // Contoh plugin geocoder: https://github.com/perliedman/leaflet-control-geocoder
  if (typeof L.Control.Geocoder === 'function') {
    const geocoder = L.Control.geocoder({
      defaultMarkGeocode: false
    }).on('markgeocode', function(e) {
      const bbox = e.geocode.bbox;
      map.fitBounds(bbox);
    }).addTo(map);
  }

  // === Geocoder (Search) ===
  let searchMarker = null;

  L.Control.geocoder({
    defaultMarkGeocode: false,
  position: 'topleft' // dipindah ke kiri atas agar tidak tertutup
})

  .on('markgeocode', function (e) {
    const latlng = e.geocode.center;

  // Hapus marker sebelumnya jika ada
    if (searchMarker) {
      map.removeLayer(searchMarker);
    }

  // Tambahkan marker baru
    searchMarker = L.marker(latlng)
    .addTo(map)
    .bindPopup(`Hasil Pencarian:<br><strong>${e.geocode.name}</strong>`)
    .openPopup();

  map.setView(latlng, 16); // Atur zoom dan pindah ke lokasi
})

  .addTo(map);

// === Tombol Lokasi Saya ===
  const locateControl = L.control({ position: 'topleft' });

  locateControl.onAdd = function (map) {
    const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
    div.innerHTML = `
    <button title="Lokasi Saya" class="leaflet-control-locate" style="background-color: transparent; border: none; cursor: pointer;">
    <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" alt="Lokasi Saya" style="width: 24px; height: 24px;" />
    </button>
    `;
    div.onclick = function (e) {
      e.preventDefault();
      map.locate({ setView: true, maxZoom: 16 });
    };
    return div;
  };

  locateControl.addTo(map);

  map.on('locationfound', function (e) {
    L.circleMarker(e.latlng, {
      radius: 8,
      color: 'blue',
      fillColor: '#30f',
      fillOpacity: 0.5
    }).addTo(map)
    .bindPopup("Lokasi Anda Sekarang")
    .openPopup();
  });

  map.on('locationerror', function (e) {
    alert("Tidak bisa mendapatkan lokasi Anda. Pastikan izin lokasi diaktifkan.");
  });

  // 3. Layer control (optional, karena kamu sudah punya filter sendiri)
  // Bisa aktifkan layer dasar lain jika ingin
  /*
  const baseLayers = {
    "OpenStreetMap": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
  };
  L.control.layers(baseLayers).addTo(map);
  */

</script>

@endsection
