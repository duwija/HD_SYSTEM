@extends('layout.main')
@section('title',' Distribution Point')
@section('maps')
@inject('olt', 'App\Olt')

@endsection
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
 google.charts.load('current', {packages:["orgchart"]});
 google.charts.setOnLoadCallback(drawChart);

 function drawChart() {
  var data = new google.visualization.DataTable();
  data.addColumn('string', 'Name');
  data.addColumn('string', 'Manager');
  data.addColumn('string', 'ToolTip');

  // Your data rows
  data.addRows([
    [{'v':'{{$distpoint->id}}', 'f':'{{$distpoint->name}}<div style="color:blue; border:0px;"></div>'}, '', 'Parents'],
    @foreach($distpoint_chart as $chart)
    [{'v':'{{ $chart->id }}', 'f':'{{ $chart->name }}'}, '{{ $chart->parrent }}', '{{ $chart->name }}'],
    @endforeach
    ]);

  var chart = new google.visualization.OrgChart(document.getElementById('chart_div_distpoint'));
  chart.draw(data, {'allowHtml': true});

  // Add event listener for clicks
  google.visualization.events.addListener(chart, 'select', function() {
    var selection = chart.getSelection();
    if (selection.length > 0) {
      var selectedItem = selection[0];
      if (selectedItem) {
        var distpointId = data.getValue(selectedItem.row, 0);
        window.location.href = '/distpoint/' + distpointId; // Redirect to the desired URL
      }
    }
  });
}
</script>

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Show Detail Distribution Point </h3>
    </div>
    
    <div class="card-body">

      <div class="col-md-12 card">
        <div class="row mb-2">
          <div class="form-group col-md-4">
            <label class="font-weight-bold text-right">Name:</label>
            <span class="ml-2">{{$distpoint->name}}</span>
          </div>
          <div class="form-group col-md-8">
            <label class="font-weight-bold">Location:</label>
            <span class="ml-2">{{$site->name}}</span>
          </div>
        <!-- </div>

          <div class="row mb-2"> -->
            <div class="form-group col-md-4">
              <label class="font-weight-bold">Capacity:</label>
              <span class="ml-2">{{$distpoint->ip}}</span>
            </div>
            <div class="form-group col-md-8">
              <label class="font-weight-bold">Optic Power:</label>
              <span class="ml-2">{{$distpoint->security}}</span>
            </div>
        <!-- </div>

          <div class="row mb-2"> -->
            <div class="form-group col-md-4">
              <label class="font-weight-bold">Parent:</label>
              <a href="{{$distpoint->parrent}}" class="ml-2 text-primary">{{$distpoint_name->name}}</a>
            </div>
            <div class="form-group col-md-8">
              <label class="font-weight-bold">Description:</label>
              <textarea class="form-control" rows="3" disabled>{{$distpoint->description}}</textarea>
            </div>
          </div>
          <div class="card-footer">

            <a href="/distpoint/{{ $distpoint->id }}/edit" class="btn btn-primary btn-sm ">  Edit  </a>
            
            <form  action="/distpoint/{{ $distpoint->id }}" method="POST" class="d-inline item-delete" >
              @method('delete')
              @csrf

              <button type="submit"  class="btn btn-danger btn-sm float-right">  Delete  </button>
            </form>

          </div>
        </div>




        <div class=" col-md-12 card card-primary card-outline">
          <div class="card-header">
            <h3 class="card-title">Distpoint Member  </h3>


            <div id="chart_div_distpoint"></div>



          </div>

          <!-- /.card-header --> <h3 class="card-title">Customer Member  </h3><br>
          <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table table-bordered table-striped text-xs">

                <thead >
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">CID</th>
                    <th scope="col">Status</th>

                    <th scope="col">Name</th>
                    <th scope="col">OLT</th>
                    <th scope="col">ONU ID</th>
                    <th scope="col">Power</th>
                    <th scope="col">Plan</th>
                    <th scope="col">Address</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach( $distpoint->customer as $customer)
                  <tr>
                    <th scope="row">{{ $loop->iteration }}</th>
                    <td>
                      <a class="btn btn-primary btn-sm" href="/customer/{{$customer->id}}"> {{ $customer->customer_id }}</a></td>
                      @php

                      if ($customer->status_name->name == 'Active')
                      $badge_sts = "badge-success";
                      elseif ($customer->status_name->name == 'Inactive')
                      $badge_sts = "badge-secondary";
                      elseif ($customer->status_name->name == 'Block')
                      $badge_sts = "badge-danger";
                      elseif ($customer->status_name->name == 'Company_Properti')
                      $badge_sts = "badge-primary";
                      else
                      $badge_sts = "badge-warning";

                      @endphp


                      <td class="text-center"><a class="badge text-white {{$badge_sts}}">{{ $customer->status_name->name }}</a></td>

                      <td >
                       {{ $customer->name }}

                     </td>
                     <td >
                      {{ $customer->olt_name->name }}

                    </td><td >
                     {{ $customer->id_onu }}

                   </td>
                   <td class="ont-status" data-id-onu="{{ $customer->id_onu }}" data-id-olt="{{ $customer->id_olt }}">
                    <span id="ont_status_{{ $customer->id }}">Loading...</span>
                  </td>
                  <td >
                   {{ $customer->plan_name->name }} ( {{ number_format($customer->plan_name->price)}})

                 </td>
                 <td >
                   {{ $customer->address }}

                 </td>


                 <!-- /.modal -->






               </tr>




               @endforeach

             </tbody>
           </table>
         </div>
       </div>
     </div>








     <div class=" col-md-12 card card-primary card-outline">
      <div class="form-group">
        <label for="maps">Maps   </label>

        @if ($distpoint->coordinate == null)

        <br><a class="p-md-2">No Map set !!</a> 

        @else
        <div>

        </div>
        <div class="float-right " >
          <a href="https://www.google.com/maps/place/{{ $distpoint->coordinate }}" target="_blank" class="btn btn-info btn-sm "><i  class="fa fa-map"> </i> Show in Google Maps </a>



        </div>
        <div style="width: 100%; height: 500px;"id="map">Map Not Set !!</div> @endif
      </div>






      <div>

        {{--   @foreach( $distpoint_chart as $distpoint_chart)

        @endforeach
      </div>
      --}}







    </div>
    <!-- /.card-body -->








  </div>
  <!-- /.card -->

  <!-- Form Element sizes -->


















</div>

</section>

@endsection


@section('footer-scripts')
<script type="text/javascript">
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  $(document).ready(function() {
  // Loop untuk setiap baris dengan class 'ont-status'
    $('.ont-status').each(function() {
    // Ambil data dari elemen tersebut
      var id_onu = $(this).data('id-onu');
      var id_olt = $(this).data('id-olt');
      var ont_status_id = $(this).find('span').attr('id');

    // Pastikan id_onu, id_olt, dan ont_status_id tidak undefined
      if (id_onu && id_olt && ont_status_id) {
      // Panggil AJAX
        $.ajax({
          url: '/olt/ont_status',
          method: 'post',
          data: {
            id_onu: id_onu,
            id_olt: id_olt
          },
          success: function(data) {
          $('#' + ont_status_id).html(data); // Update HTML dengan data yang diterima
        },
        error: function(xhr, status, error) {
          console.log('Error: ' + error); // Handle error jika ada
        }
      });
      } else {
        console.log('Data attributes are missing or invalid');
      }
    });
  });




  var center = @json($center);
  var locations = @json($locations);

// Ubah koordinat pusat menjadi array [lat, lng]
  var coordinates = center.coordinate.split(',').map(Number);

// Inisialisasi peta
  var map = L.map('map').setView(coordinates, center.zoom);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

// Variabel untuk menyimpan marker utama
  var mainMarker = null;
var mainCoords = null; // Koordinat marker utama

// Iterasi lokasi
locations.forEach(location => {
    var coords = location.customer.split(',').map(Number); // Ubah string koordinat jadi [lat, lng]
    var marker;

    // Tentukan apakah marker ini adalah marker utama
    if (location.icon && !mainMarker) { // Jika lokasi memiliki icon dan marker utama belum ditentukan
      var mainIcon = L.icon({
            iconUrl: location.icon, // URL ikon dari lokasi
            iconSize: [48, 48], // Ukuran ikon utama lebih besar
            iconAnchor: [24, 48], // Titik jangkar (bagian bawah ikon)
            popupAnchor: [0, -48] // Posisi popup relatif terhadap ikon
          });

        mainMarker = L.marker(coords, { icon: mainIcon }).addTo(map); // Tambahkan marker utama
        mainCoords = coords; // Simpan koordinat marker utama
        mainMarker.bindPopup(`<b>Marker Utama</b><br>${location.name}`);
      } else {
        // Marker lainnya (member)
        marker = L.marker(coords).addTo(map) // Marker default
        .bindPopup(location.name);

        // Tambahkan garis dari member ke marker utama jika marker utama sudah ada
        if (mainCoords) {
            L.polyline([mainCoords, coords], { color: 'blue', weight: 2 }).addTo(map); // Tambahkan garis
          }
        }
      });




    </script>
    @endsection



