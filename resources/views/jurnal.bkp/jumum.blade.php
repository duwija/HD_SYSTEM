@extends('layout.main')
@section('title','JURNAL')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold">Laporan Jurnal  </h3>

<!--       <div class="float-right">
        <div class="input-group">
          <form role="form" method="post" action="/jurnal/create">
            @csrf
            <select style="border: 1px solid blue"  name="akuntransaction" id="akuntransaction" class="form-control-sm">
              @foreach ($akuntransaction as $id => $name)
              <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
            <button type="submit" class="float-right btn bg-primary btn-sm">Add New Jurnal</button>
          </form>
        </div>
      </div> -->
      <br>
      <hr>

      <div class="row pt-2 pl-4">
        <div class="form-group col-md-3">
          <label for="site location">  Transaction Date Start </label>
          <div class="input-group mb-3">
            <div class="input-group p-1  date" id="reservationdate" data-target-input="nearest">
              <input type="text" name="date_from" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{ now()->toDateString() }}" />
              <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group col-md-3">
          <label for="site location">  Transaction Date Start </label>
          <div class="input-group mb-3">
           <div class="input-group p-1 date" id="reservationdate" data-target-input="nearest">
            <input type="text" name="date_end" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{date('Y-m-d')}}" />
            <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group col-md-2">
        <label for="site location">   </label>

        <div class="input-group p-1 col-md-3">

          <button type="button" class="btn mt-2   bg-gradient-primary  btn-primary"  id="jurnal">Filter
          </button>
        </div> 
      </div>
    </div>

    <hr>

    <div class="card-body">
      <div id="totals" style="margin-bottom: 10px;">
        <!-- <strong>Total Debet:</strong> <span id="total-debet">0</span> |
          <strong>Total Kredit:</strong> <span id="total-kredit">0</span> -->
        </div>
        <table id="jurnal-table" class="table table-bordered table-striped">
          <thead>
           <tr>

            <th colspan="12"class="text-right border-0" >
              <div class="row float-right">


              <!--   <div class="bg-green p-2 rounded-sm m-1 " ><h5>Rp. <span name='total-debet' id='total-debet'>0 </span></h5>

                  <p>Total Debet</p>
                </div>

                <div class="bg-navy p-2 rounded-sm m-1" ><h5>Rp. <span name='total-kredit' id='total-kredit'>0 </span></h5>
                  <p>Total Kredit</p>
                </div>  -->

              </div>
            </th>




          </tr>

          <tr>
            <th scope="col">#</th>
            <th scope="col">Date</th>

            <th scope="col">Akun</th>


            <th scope="col">Total Rp. <span name='total-debet' id='total-debet'>0 </span><p> Debet   </th>
              <th scope="col">Total Rp. <span name='total-kredit' id='total-kredit'>0 </span><p> Kredit</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </section>
    @endsection

    @section('footer-scripts')

    <script>
     var index = 0;
     $('#jurnal').click(function() 
     {
      var index=0;
      $('#jurnal-table').DataTable().ajax.reload();

    });
     
     var table = $('#jurnal-table').DataTable({
       "responsive": true,
       "autoWidth": false,
       // "searching": true,
       "language": {
        "processing": "<span class='fa-stack fa-lg'>\n\
        <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
        </span>&emsp;Processing ..."
      },
      dom: 'Bfrtip',
      buttons: [
        'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
        ],
      "lengthMenu": [[20,50, 100, 200, 500, 1000], [20,50, 100, 200, 1000]],
      processing: true,
      serverSide: true,
      ajax: {

        url: '/jurnal/getjurnaldata',
        type: 'POST',
        data: function ( d ) {
         return $.extend( {}, d, {
          "date_from": $(document.querySelector('[name="date_from"]')).val(),
          "date_end": $(document.querySelector('[name="date_end"]')).val(),

        } );
       },
       dataSrc: function (json) {
                // Update totals in the div
        $('#total-debet').text(json.totals.debet.toLocaleString());
        $('#total-kredit').text(json.totals.kredit.toLocaleString());
        table.start = json.start;
        return json.data; 

      },
    },
    columns: [

      { data: null, name: null, orderable: false, searchable: false, className: 'dt-center' },
      { data: 'date', name: 'date', className: 'dt-left', orderable: false, searchable: false},
      { data: 'akun_name', name: 'akun_name', className: 'dt-left', orderable: false, searchable: false },
      { data: 'debet', name: 'debet', className: 'dt-right' },
      { data: 'kredit', name: 'kredit', className: 'dt-right' },
      ],
    rowCallback: function (row, data) {

      if (data.is_group) {
       index++; 
          // Set nomor dan description untuk baris grup
       $(row).addClass('bg-light text-bold');

           $('td', row).eq(0).html(data.index); // Nomor dan description
           $('td:gt(1)', row).remove(); // Hapus kolom lain di baris grup
           $('td', row).eq(1).attr('colspan', 4).html(`
            <strong>${data.description}</strong>
            `);
         }
         else if (data.akun_name === 'Subtotal') {
          // Baris Subtotal
          $(row).addClass('bg-secondary');
          $('td', row).eq(0).html('');
          $('td', row).eq(2).html('<strong>Subtotal</strong>'); // Kolom akun_name
          $('td', row).eq(3).html(`<strong>${data.debet}</strong>`); // Kolom debet
          $('td', row).eq(4).html(`<strong>${data.kredit}</strong>`); // Kolom kredit
        }
        else {
          $('td', row).eq(0).html(''); // Kosongkan nomor untuk baris biasa
        }
      },
      // drawCallback: function (settings) {
        // Nomor hanya untuk baris grup
        // var api = this.api();
        // var rows = api.rows({ page: 'current' }).nodes();
         // var startIndex = settings._iDisplayStart; // Offset data dari DataTables
    // var index = startIndex + 1; // Hitungan nomor dimulai dari offset


         // api.column(0, { page: 'current' }).data().each(function (data, i) {
         //  if (data.is_group) {
            // Set nomor dan tambahkan description
        // $(rows).eq(i).find('td:first').html(`<strong>${index++}</strong>`);
        //   }
        // });
       // },
    });

  </script>


  @endsection