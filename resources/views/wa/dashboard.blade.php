@extends('layout.main')
@section('title',' Wa Gateway')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"> Whatsapp Gateway </h3>
    </div>

    <div class="card-body">

        <div class="row mb-4 align-items-end">
            <div class="col-md-6">
                <a href="{{ url('/wa/logs') }}" class="btn btn-info">
                    📬 Log Pesan
                </a>
            </div>
            <div class="col-md-6 text-right">
                <form id="addSessionForm" class="form-inline justify-content-end">
                    <div class="form-group mr-2">
                        <input type="text" id="newSession" class="form-control" placeholder="Nama session baru" required>
                    </div>
                    <button type="submit" class="btn btn-success">+ Tambah Session</button>
                </form>
            </div>
        </div>



        <table class="table table-bordered" id="sessionTable">
            <thead>
                <tr>
                    <th>Session</th>
                    <th>Status</th>
                    <th>Nomor</th>
                    <th>Nama</th>
                    <th>Platform</th>
                    <th>Pesan Terkirim</th>

                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="sessionList"></tbody>
        </table>
    </div>

    <!-- Modal QR -->
    <div class="modal fade" id="qrModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Scan QR WhatsApp</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Wrapper untuk center-kan QR -->
                    <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 200px;">
                        <div id="qrLoading" class="text-center">Loading QR...</div>
                        <img id="qrImage" src="" alt="QR Code" class="img-fluid mt-2" style="display:none">
                        <div id="qrCountdown" class="mt-2 text-muted"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus Session</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Yakin ingin menghapus session <strong id="sessionToDelete"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection

@section('footer-scripts')
<script>
    let deleteTarget = null;

    async function fetchSessions() {
        try {
            console.log('📡 Memuat dashboard WA...');
            const health = await fetch('/wa/status').then(r => r.json());
            console.log('✅ Data /wa/status:', health);
            const sessions = health.sessions || [];
            console.log('📋 Daftar session:', sessions);

            const tbody = document.getElementById('sessionList');
            tbody.innerHTML = '';

            for (const session of sessions) {
                console.log(`🔍 Mengecek session: ${session}`);
                const row = document.createElement('tr');
                let status = 'loading';
                let number = '-', name = '-', platform = '-';
                let messageCount = '-';
                try {
                    const stats = await fetch(`/wa/${session}/stats`).then(r => r.json());
                    messageCount = stats.count ?? '-';
                } catch (e) {
                    console.warn(`⚠️ Gagal ambil statistik untuk ${session}`);
                }


                try {
                    const response = await fetch(`/wa/${session}/status`);
                    const data = await response.json();
                    console.log(`✅ Status ${session}:`, data);
                    status = data.status;
                    number = data.number ?? '-';
                    name = data.name ?? '-';
                    platform = data.platform ?? '-';

                    let badgeClass = 'badge-secondary';
                    if (status === 'authenticated') badgeClass = 'badge-success';
                    else if (status === 'not_authenticated') badgeClass = 'badge-warning';
                    else if (status === 'initializing') badgeClass = 'badge-info';

                    const deleteBtn = `<button class="btn btn-outline-danger btn-sm ml-2" onclick="openDeleteModal('${session}')">Hapus</button>`;

                    let actionBtn = '';
                    if (status === 'authenticated') {
                        actionBtn = `
                        <form method="POST" action="/wa/${session}/logout" style="display:inline-block">
                        @csrf
                        <button class="btn btn-danger btn-sm">Logout</button>
                        </form>
                        <form method="POST" action="/wa/${session}/restart" style="display:inline-block; margin-left: 5px">
                        @csrf
                        <button class="btn btn-warning btn-sm">Restart</button>
                        </form>
                        ${deleteBtn}
                        `;
                    } else if (status === 'not_authenticated') {
                        actionBtn = `
                        <button class="btn btn-primary btn-sm" onclick="showQr('${session}')">Scan QR</button>
                        ${deleteBtn}
                        `;
                    } else {
                        actionBtn = `<span class="text-muted">${status}</span>${deleteBtn}`;
                    }

                      

                    row.innerHTML = `
                    <td>
                    <a href="/wa/chat?session=${session}" class="btn btn-info btn-sm mr-1">${session}</a></td>
                    <td><span class="badge ${badgeClass}">${status}</span></td>
                    <td>${number}</td>
                    <td>${name}</td>
                    <td>${platform}</td>
                    <td>${messageCount}</td>
                    <td>${actionBtn}</td>
                    `;
                } catch (err) {
                    console.error(`❌ Gagal ambil status untuk ${session}:`, err);
                    row.innerHTML = `<td>${session}</td><td colspan="5" class="text-danger">Gagal mengambil status</td>`;
                }

                tbody.appendChild(row);
            }
        } catch (error) {
            console.error('❌ Gagal memuat dashboard:', error);
        }
    }

    function openDeleteModal(session) {
        deleteTarget = session;
        document.getElementById('sessionToDelete').textContent = session;
        $('#confirmDeleteModal').modal('show');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
        if (!deleteTarget) return;
        await fetch(`/wa/${deleteTarget}/logout`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        $('#confirmDeleteModal').modal('hide');
        fetchSessions();
    });

    let qrInterval, countdownInterval;

    async function showQr(session) {
        $('#qrModal').modal('show');
        document.getElementById('qrImage').style.display = 'none';
        document.getElementById('qrLoading').style.display = 'block';
        document.getElementById('qrCountdown').innerText = '';

        let countdown = 60;
        document.getElementById('qrCountdown').innerText = `QR akan diperbarui dalam ${countdown} detik`;

        countdownInterval = setInterval(() => {
            countdown--;
            if (countdown <= 0) countdown = 60;
            document.getElementById('qrCountdown').innerText = `QR akan diperbarui dalam ${countdown} detik`;
        }, 1000);

        qrInterval = setInterval(async () => {
            const res = await fetch(`/wa/${session}/status`).then(r => r.json());
            if (res.status === 'not_authenticated' && res.qr) {
                document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(res.qr)}`;
                document.getElementById('qrLoading').style.display = 'none';
                document.getElementById('qrImage').style.display = 'block';
            } else if (res.status === 'authenticated') {
                clearInterval(qrInterval);
                clearInterval(countdownInterval);
                $('#qrModal').modal('hide');
                fetchSessions();
            }
        }, 3000);
    }

    $('#qrModal').on('hidden.bs.modal', function () {
        clearInterval(qrInterval);
        clearInterval(countdownInterval);
    });

    document.getElementById('addSessionForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const session = document.getElementById('newSession').value.trim();
        if (!session) return alert('Nama session tidak boleh kosong');

        const response = await fetch('/wa/start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session })
        });
        const data = await response.json();

        if (data.status === 'started' || data.status === 'already_running') {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Session "' + session + '" siap digunakan. QR akan muncul jika belum login.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Gagal',
                text: data.message || 'Tidak diketahui',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }

    });

    console.log('📡 Memuat dashboard WA...');
    fetchSessions();
    console.log('✅ fetchSessions() dipanggil');
</script>
@endsection
