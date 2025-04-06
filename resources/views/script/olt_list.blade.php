
<script>

 

  var table = $('#table-olt-list').DataTable({
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
      url: '/olt/table_olt_list',
      method: 'POST',

      
    },

    'columnDefs': [

    {
      "targets": 1, // your case first column
      "className": "text-center",

    },
    {
      "targets": 2, // your case first column
      "className": "text-center",

    },
    {
      "targets": 3, // your case first columnzZxZ
      "className": "text-center",

    },
    {
      "targets": 4, // your case first columnzZxZ
      "className": "text-center",

    },
    {
      "targets": 7, // your case first column
      "className": "text-center",

    },
    
    // {
    //   "targets": 7, // your case first columnzZxZ
    //   "className": "text-center font-weight-bold",

    // },
    ],
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {data: 'name', name: 'name'},
      {data: 'type', name: 'type'},
      {data: 'ip', name: 'ip'},
      {data: 'port', name: 'port'},
      {data: 'community_ro', name: 'community_ro'},
      {data: 'community_rw', name: 'community_rw'},
      {data: 'snmp_port', name: 'snmp_port'},

      ],

  });






</script>