

<script>
 
  $('input[name=makeinv]').click(function(e) {

    alert('test');
  });


  var input = document.getElementById("parameter");

// Execute a function when the user presses a key on the keyboard
  input.addEventListener("keypress", function(event) {
  // If the user presses the "Enter" key on the keyboard
    if (event.key === "Enter") {
    // Cancel the default action, if needed
      event.preventDefault();
    // Trigger the button element with a click
      
      
      document.getElementById("invoice_filter").click();
    }
  });




  $('#invoice_filter').click(function() 
  {
   document.getElementById("search_var").value = "";
        //$('#invoice-customer').DataTable().ajax.reload();
   $('#table-invoice').DataTable().ajax.reload();

 });

  var table = $('#table-invoice').DataTable({
    "responsive": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<span class='fa-stack fa-lg'>\n\
      <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
      </span>&emsp;Processing ..."
    },
    dom: 'lBfrtip',
    buttons: [
      'copy', 'excel', 'pdf', 'csv', 'print'
      ],
    "lengthMenu": [[200, 500, 1000], [200, 500, 1000]],
    processing: true,
    serverSide: true,
    ajax: {
      url: '/customer/table_invoice',
      method: 'POST',
        // },
      data: function ( d ) {
       return $.extend( {}, d, {
        "search_var" : $("#search_var").val(),
        "filter": $("#filter").val(),
        "parameter": $("#parameter").val(),
        "id_status": $("#id_status").val(),
        "id_plan": $("#id_plan").val(),  
        "id_merchant": $("#id_merchant").val(),             
      } );
     }
   },
   'columnDefs': [

   {
      "targets": 5, // your case first column
      "className": "text-center",

    },
    {
      "targets": 6, // your case first column
      "className": "text-center",

    // },
    // {
    //   "targets": 7, // your case first columnzZxZ
    //   "className": "text-center",

    // },
    // {
    //   "targets": 8, // your case first columnzZxZ
    //   "className": "text-center",

    // },
    // {
    //   "targets": 9, // your case first columnzZxZ
    //   "className": "text-center",

    // },
    // {
    //   "targets": 10, // your case first columnzZxZ
    //   "className": "text-center",

    }
    ],
   columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    {data: 'customer_id', name: 'customer_id'},
    {data: 'name', name: 'name'},
    {data: 'address', name: 'address'},
    {data: 'plan', name: 'plan'},
    {data: 'billing_start', name: 'billing_start'},
    // {data: 'infra', name: 'infra'},
    // {data: 'link_type', name: 'link_type'},
    // {data: 'snote', name: 'snote'},
    {data: 'status_cust', name: 'status_cust'},
    // {data: 'select', name: 'select'},
    {data: 'invoice', name: 'invoice'},
    // {data: 'action', name: 'action'}


    ],

 });






</script>