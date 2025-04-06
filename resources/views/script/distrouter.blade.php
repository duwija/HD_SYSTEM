<script>
    $(document).ready(function () {
        $('#spinner').show();
        fetchLogs();
        function fetchRouterInfo() {
            $.ajax({
                url: '/distrouter/getrouterinfo/{{$distrouter->id}}',
                type: 'GET',
                success: function (data) {
                    $('#spinner').hide();
                    if (data.success) {
                        $('#distrouter-info').html(`
                            <p><strong>Router Board-name:</strong> ${data.routerInfo[0]["board-name"]}</p>
                            <p><strong>Router uptime:</strong> ${data.routerInfo[0].uptime}</p>
                            <p><strong>Router Platform:</strong> ${data.routerInfo[0].platform}</p>
                            <p><strong>Router Version:</strong> ${data.routerInfo[0].version}</p>
                            <p><strong>CPU Count:</strong> ${data.routerInfo[0]["cpu-count"]}</p>
                            <p><strong>CPU Load:</strong> ${data.routerInfo[0]["cpu-load"]} %</p>

                            <section class="content">
                            <div class="container-fluid">
                            <div class="row">
                            <div class="col-lg-3 col-3">
                            <div class="small-box bg-info">
                            <div class="inner">
                            <h4>${data.pppUserCount}</h4>
                            <p>PPP User</p>
                            </div>
                            <div class="icon"><i class="fas fa-user"></i></div>
                            </div>
                            </div>
                            <div class="col-lg-3 col-3">
                            <div class="small-box bg-success">
                            <div class="inner">
                            <h4>${data.pppActiveCount}</h4>
                            <p>PPP Online</p>
                            </div>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                            </div>
                            </div>
                            <div class="col-lg-3 col-3">
                            <div class="small-box bg-danger">
                            <div class="inner">
                            <h4>${data.pppOfflineCount}</h4>
                            <p>PPP Offline</p>
                            </div>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                            </div>
                            </div>
                            <div class="col-lg-3 col-3">
                            <div class="small-box bg-secondary">
                            <div class="inner">
                            <h4>${data.pppDisabledCount}</h4>
                            <p>PPP Disabled</p>
                            </div>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                            </div>
                            </div>
                            </div>
                            </div>
                            </section>


                            `);


                    } else {
                        $('#distrouter-info').html('<div class="alert alert-danger">' + data.error + '</div>');
                    }
                },
                error: function () {
                    $('#spinner').hide();
                    $('#distrouter-info').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data.</div>');
                }
            });
        }




        function loadData(status) {
          $('#spinnerpppoe').show();
          $.ajax({
            url: `/distrouter/getPppoeUsers/{{$distrouter->id}}/${status}`,
            type: 'GET',
            success: function(data) {
                if (data.success) {
                    $('#spinnerpppoe').hide();
                    var pppoeData = data.data.map(function(user) {
                        // Tentukan warna badge berdasarkan status
                        let badgeClass = '';
                        switch (user.status) {
                        case 'Online': badgeClass = 'badge-success'; break;
                        case 'Offline': badgeClass = 'badge-danger'; break;
                        case 'Disabled': badgeClass = 'badge-secondary'; break;
                        }

                        return [
                            user.name,
                            user.description,
                            user.profile,
                            user.last_logout,
                            user.address,
                            user.uptime,
                            `<span class="badge ${badgeClass}">${user.status}</span>` // Tambahkan badge
                            ];
                    });

                    // Inisialisasi DataTable dan masukkan data
                    $('#pppoeTable').DataTable({
                        data: pppoeData,
                        columns: [
                            { title: "Name" },
                            { title: "Description" },
                            { title: "profile" },
                            { title: "last logout" },
                            { title: "Address" },
                            { title: "Uptime" },
                            { title: "Status" }
                            ],
                        destroy: true,
                        paging: true,
                        searching: true,
                        ordering: true
                    });
                } else {
                    alert('Error fetching data!');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching PPPoE users:', error);
            }
        });
      }
      function fetchLogs() {
        $.ajax({
        url: `/distrouter/logs/{{$distrouter->id}}/`, // Sesuaikan dengan route Laravel
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if ($.fn.DataTable.isDataTable('#logTable')) {
                $('#logTable').DataTable().destroy(); // Hapus instance DataTables jika sudah ada
            }

            let logTableBody = $('#logTable tbody');
            logTableBody.empty(); // Kosongkan tabel sebelum menampilkan data baru

            if (response.success) {
                let filteredLogs = response.logs.filter(log => 
                    !['system,info,account'].includes(log.topics)
                    );

                if (filteredLogs.length > 0) {
                    filteredLogs.forEach(log => {
                        logTableBody.append(`<tr>
                            <td>${log.time ?? 'N/A'}</td>
                            <td>${log.topics ?? 'N/A'}</td>
                            <td>${log.message ?? 'N/A'}</td>
                            </tr>`);
                    });

                    // Inisialisasi DataTables
                    $('#logTable').DataTable({
                        ordering: true,
                        pageLength: 10,
                        order: [[0, 'desc']], // Urutkan berdasarkan waktu terbaru
                    });
                } else {
                    logTableBody.html(`<tr><td colspan="3">No logs available.</td></tr>`);
                }
            } else {
                logTableBody.html(`<tr><td colspan="3">${response.message}</td></tr>`);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching logs:', error);
            $('#logTable tbody').html(`<tr><td colspan="3">Failed to fetch logs.</td></tr>`);
        }
    });
    }

    function fetchInterfaceMonitor() {
        $.ajax({
            url: '/distrouter/getrouterinterfaces/{{$distrouter->id}}',
            type: 'GET',
            success: function (response) {
                const interfaces = response.routerInterfaces;

                if (Array.isArray(interfaces)) {
                    var rows = '';
                    var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];

                    interfaces.forEach(function (interface) {
                        var name = interface.name || 'N/A';
                        var comment = interface.comment || 'N/A';
                        var running = interface.running || 'N/A';
                        var speed = interface.speed || 'N/A';
                        rows += '<tr>';
                        rows += '<td>' + name + '</td>';
                        rows += '<td>' + comment + '</td>';
                        rows += '<td>' + running + '</td>';
                        rows += '<td>' + speed + '</td>';
                        rows += '<td id="' + name + 'tx"></td>';
                        rows += '<td id="' + name + 'rx"></td>';
                        rows += '</tr>';
                    });

                    $('#interface-table tbody').html(rows);

                    interfaces.forEach(function (interface) {
                        var name = interface.name || 'N/A';

                        $.ajax({
                            url: '/distrouter/interface_monitor/{{$distrouter->id}}',
                            type: 'GET',
                            data: { interface: name },
                            success: function (trafficResponse) {
                                var tx = trafficResponse[0].data[0] || 0;
                                var rx = trafficResponse[1].data[0] || 0;

                                var txBytes = parseInt(tx) || 0;
                                var rxBytes = parseInt(rx) || 0;

                                if (txBytes == 0) tx = '0 bps';
                                else {
                                    var iTx = parseInt(Math.floor(Math.log(txBytes) / Math.log(1024)));
                                    tx = parseFloat((txBytes / Math.pow(1024, iTx)).toFixed(2)) + ' ' + sizes[iTx];
                                }

                                if (rxBytes == 0) rx = '0 bps';
                                else {
                                    var iRx = parseInt(Math.floor(Math.log(rxBytes) / Math.log(1024)));
                                    rx = parseFloat((rxBytes / Math.pow(1024, iRx)).toFixed(2)) + ' ' + sizes[iRx];
                                }

                                $('#' + name + 'tx').text(tx);
                                $('#' + name + 'rx').text(rx);
                            },
                            error: function (xhr, status, error) {
                                console.error('Error in interface monitor request:', error);
                            }
                        });
                    });
                } else {
                    console.error('Expected an array but got:', interfaces);
                    alert('Error: Data returned is not in the expected format.');
                }
            },
            error: function (xhr, status, error) {
                alert('Error: ' + xhr.responseText);
            }
        });
    }
  // Change event for status filter
    $('#statusFilter').on('change', function() {
        let selectedStatus = $(this).val();
        loadData(selectedStatus);
    });

    fetchRouterInfo();
    fetchInterfaceMonitor();
    setInterval(fetchRouterInfo, 10000);
    setInterval(fetchInterfaceMonitor, 10000);
    loadData('all');

});
</script>
<script>
    $(document).on('click', '.dropdown-item', function() {
        var command = $(this).data('command'); 
        var id = $(this).data('id'); 
         $('#spinnermk').show(); // Tampilkan loading
         executeCommand(command, id);
     });

    function executeCommand(command, id) {
        $.ajax({
            url: '/distrouter/executeCommand',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                command: command,
                id: id
            },
            success: function(response) {
                 $('#spinnermk').hide(); // Tampilkan loading
                 if (response.output && response.output.length > 0) {
                    let table = generateTable(response.output);
                    $('#commandOutput').html(table);

                    // Inisialisasi DataTables setelah tabel dibuat
                    $('#outputTable').DataTable();
                } else {
                    $('#commandOutput').html('<h5>Command Output:</h5><pre>No data received</pre>');
                }
            },
            error: function() {
                 $('#spinnermk').hide(); // Tampilkan loading
                 $('#commandOutput').html('<h5>Error:</h5><pre>An error occurred while executing the command.</pre>');
             }
         });
    }

    function generateTable(data) {
        let table = `

        <h5>Command Output:</h5>
        <table id="outputTable" class="table table-bordered table-striped">
        <thead class="bg-info">
        <tr>`;

        // Ambil header dari kunci objek pertama
        let headers = Object.keys(data[0]);
        headers.forEach(header => {
            table += `<th>${header}</th>`;
        });
        table += '</tr></thead><tbody>';

        // Tambahkan baris data
        data.forEach(row => {
            table += '<tr>';
            headers.forEach(header => {
                table += `<td>${row[header] || '-'}</td>`;
            });
            table += '</tr>';
        });

        table += '</tbody></table>';
        return table;
    }
</script>
