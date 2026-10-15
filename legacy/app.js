const $ = s => document.querySelector(s);
const state = { user: null, page: 'dashboard', rows: [], cache: {}, search: '', formKind: null };
const titles = { dashboard: 'Dashboard', alat: 'Data Alat', pelanggan: 'Data Pelanggan', penyewaan: 'Data Penyewaan', pembayaran: 'Data Pembayaran', pengembalian: 'Pengembalian & Denda', users: 'Pengguna Sistem', laporan: 'Laporan' };

const kinds = {
    pelanggan: { label: 'Pelanggan', endpoint: 'api_post_pelanggan.php', fields: [['nama_pelanggan', 'Nama pelanggan', 'text', true], ['alamat', 'Alamat', 'textarea', true], ['nomor_telepon', 'Nomor telepon', 'tel', true]] },
    alat: { label: 'Alat', endpoint: 'api_post_alat.php', fields: [['kode_alat', 'Kode alat', 'text', true], ['nama_alat', 'Nama alat', 'text', true], ['kategori', 'Kategori', 'text', true], ['stok', 'Stok', 'number', true], ['kondisi', 'Kondisi', 'select', true, ['Baik', 'Perlu Perbaikan', 'Rusak']], ['tarif_sewa', 'Tarif sewa per hari (Rp)', 'number', true]] },
    penyewaan: { label: 'Penyewaan', endpoint: 'api_post_sewa.php', fields: [] },
    pembayaran: { label: 'Pembayaran', endpoint: 'api_post_pembayaran.php', fields: [] },
    pengembalian: { label: 'Pengembalian', endpoint: 'api_post_pengembalian.php', fields: [] }
};

function api(file, params = {}) {
    const u = new URL(file, location.href);

    Object.entries(params).forEach(([k, v]) => {
        u.searchParams.set(k, v);
    });

    return fetch(u.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
    }).then(async r => {
        const t = await r.text();
        let j;

        try {
            j = JSON.parse(t);
        } catch {
            throw new Error('Respons server bukan JSON. Periksa PHP error dan koneksi database.');
        }

        if (!r.ok) throw new Error(j.message || `HTTP ${r.status}`);
        return j;
    });
}

function post(file, data) {
    return fetch(file, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    }).then(async r => {
        const t = await r.text();
        let j;

        try {
            j = JSON.parse(t);
        } catch {
            throw new Error('Respons server bukan JSON. Periksa error PHP.');
        }

        if (!r.ok) throw new Error(j.message || `HTTP ${r.status}`);
        return j;
    });
}

function showMessage(text, error = false) {
    const n = $('#notice');
    n.textContent = text;
    n.classList.toggle('error', error);
    n.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => n.classList.add('hidden'), 5000);
}

function escapeHtml(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[c]));
}

function money(v) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(Number(v || 0));
}

// Menerima 30000, 30.000, Rp 30.000, dan 30,000.
function parseRupiahInput(value) {
    let text = String(value ?? '').trim();

    if (!text) return NaN;

    text = text.replace(/^rp\.?\s*/i, '').replace(/\s/g, '');

    if (/^\d+$/.test(text)) {
        const amount = Number(text);
        return Number.isSafeInteger(amount) && amount > 0 ? amount : NaN;
    }

    if (/^\d{1,3}(\.\d{3})+$/.test(text)) {
        const amount = Number(text.replace(/\./g, ''));
        return Number.isSafeInteger(amount) && amount > 0 ? amount : NaN;
    }

    if (/^\d{1,3}(,\d{3})+$/.test(text)) {
        const amount = Number(text.replace(/,/g, ''));
        return Number.isSafeInteger(amount) && amount > 0 ? amount : NaN;
    }

    return NaN;
}

function date(v) {
    if (!v) return '—';
    return escapeHtml(String(v).slice(0, 10));
}

function statusPill(v) {
    const s = String(v || '—');
    let c = '';

    if (/rusak|bermasalah|batal|belum lunas/i.test(s)) c = 'bad';
    else if (/berjalan|perbaikan/i.test(s)) c = 'warn';

    return `<span class="pill ${c}">${escapeHtml(s)}</span>`;
}

function setUser(user) {
    state.user = user;
    localStorage.setItem('ac_user', JSON.stringify(user));
    $('#loginView').classList.add('hidden');
    $('#appView').classList.remove('hidden');
    $('#profileName').textContent = user.nama || user.nama_user || user.username;
    $('#profileRole').textContent = user.role || '';
    applyRole();
    openPage('dashboard');
}

function applyRole() {
    const role = state.user?.role;

    document.querySelectorAll('.nav-item').forEach(button => {
        const page = button.dataset.page;
        let hide = false;

        if (role === 'Admin') {
            hide = !['dashboard', 'alat', 'pelanggan', 'penyewaan', 'pembayaran', 'pengembalian', 'users'].includes(page);
        }

        if (role === 'Pemilik') {
            hide = !['dashboard', 'laporan'].includes(page);
        }

        if (role === 'Pelanggan') {
            hide = !['dashboard', 'alat'].includes(page);
        }

        button.classList.toggle('hidden', hide);
    });
}

$('#loginForm').addEventListener('submit', async e => {
    e.preventDefault();
    $('#loginBtn').disabled = true;
    $('#loginMessage').textContent = 'Memeriksa akun…';

    try {
        const res = await post('api_login.php', {
            username: $('#username').value.trim(),
            password: $('#password').value,
            role: $('#loginRole').value
        });

        if (res.status !== 'success') throw new Error(res.message || 'Login gagal');

        const d = res.data || {};
        const role = d.role || res.role;

        if (role !== $('#loginRole').value) {
            throw new Error('Role akun tidak sesuai pilihan login.');
        }

        setUser({
            id_user: d.id_user ?? res.id_user ?? 0,
            id_pelanggan: d.id_pelanggan ?? res.id_pelanggan ?? null,
            nama: d.nama_user || res.nama || $('#username').value,
            username: d.username || $('#username').value,
            role
        });
    } catch (err) {
        $('#loginMessage').textContent = err.message;
    } finally {
        $('#loginBtn').disabled = false;
    }
});

$('#logoutBtn').addEventListener('click', () => {
    fetch('api_logout.php', { method: 'POST' }).catch(() => {});
    localStorage.removeItem('ac_user');
    state.user = null;
    $('#appView').classList.add('hidden');
    $('#loginView').classList.remove('hidden');
    $('#password').value = '';
});

$('#nav').addEventListener('click', e => {
    const b = e.target.closest('[data-page]');
    if (b) openPage(b.dataset.page);
});

$('#refreshBtn').addEventListener('click', () => openPage(state.page));

function openPage(page) {
    state.page = page;
    state.search = '';

    $('#pageTitle').textContent =
        state.user?.role === 'Pelanggan' && page === 'alat'
            ? 'Katalog Alat'
            : (titles[page] || page);

    document.querySelectorAll('.nav-item').forEach(button => {
        button.classList.toggle('active', button.dataset.page === page);
    });

    if (state.user?.role === 'Pelanggan') {
        if (page === 'dashboard') {
            renderCustomerHome();
            return;
        }

        if (page === 'alat') {
            renderCustomerCatalog();
            return;
        }

        $('#content').innerHTML =
            '<div class="panel empty">Halaman ini tidak tersedia untuk akun pelanggan.</div>';
        return;
    }

    if (state.user?.role === 'Pemilik' && page === 'dashboard') {
        loadOwnerDashboard();
        return;
    }

    if (page === 'dashboard') loadDashboard();
    else if (page === 'laporan') renderReports();
    else loadTable(page);
}

async function loadDashboard() {
    const content = $('#content');
    content.innerHTML = '<div class="panel empty">Memuat dashboard…</div>';

    try {
        const res = await api('api_dashboard.php');

        if (res.status && res.status !== 'success') {
            throw new Error(res.message || 'Gagal memuat dashboard');
        }

        const d = res.data || res;

        content.innerHTML = `
            <div class="stats">
                <div class="stat">
                    <div class="stat-head">Total pelanggan <span class="stat-icon">♙</span></div>
                    <strong>${Number(d.total_pelanggan || 0)}</strong>
                    <small>Pelanggan terdaftar</small>
                </div>
                <div class="stat">
                    <div class="stat-head">Stok tersedia <span class="stat-icon">▣</span></div>
                    <strong>${Number(d.total_alat || 0)}</strong>
                    <small>Unit alat bukan kondisi rusak</small>
                </div>
                <div class="stat">
                    <div class="stat-head">Transaksi aktif <span class="stat-icon">▤</span></div>
                    <strong>${Number(d.transaksi_aktif || 0)}</strong>
                    <small>Penyewaan berjalan</small>
                </div>
                <div class="stat">
                    <div class="stat-head">Pendapatan bulan ini <span class="stat-icon">◈</span></div>
                    <strong style="font-size:21px">${money(d.pendapatan_bulan)}</strong>
                    <small>Pembayaran berstatus lunas</small>
                </div>
            </div>

            <div class="two-col">
                <div class="panel">
                    <div class="panel-head">
                        <div><h3>Transaksi terbaru</h3><p>Aktivitas penyewaan terbaru</p></div>
                        <button class="btn light" data-jump="penyewaan">Lihat semua</button>
                    </div>
                    <div class="table-wrap">
                        ${tableHtml(d.terbaru || [], [
                            ['no_transaksi', 'No. transaksi'],
                            ['nama_pelanggan', 'Pelanggan'],
                            ['tanggal_sewa', 'Tanggal sewa'],
                            ['tanggal_kembali', 'Tanggal kembali'],
                            ['status_penyewaan', 'Status']
                        ])}
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><div><h3>Ringkasan</h3><p>Status operasional</p></div></div>
                    <div class="list-row">
                        <div><b>Transaksi terlambat</b><small>Lewat tanggal kembali</small></div>
                        ${statusPill(d.terlambat || 0)}
                    </div>
                    <div class="list-row">
                        <div><b>Alat paling sering disewa</b><small>${escapeHtml((d.alat_terlaris || [])[0]?.nama_alat || 'Belum ada data')}</small></div>
                        <b>${Number((d.alat_terlaris || [])[0]?.total || 0)} unit</b>
                    </div>
                    <div class="list-row">
                        <div><b>Role aktif</b><small>Sesuai akun masuk</small></div>
                        ${statusPill(state.user.role)}
                    </div>
                </div>
            </div>
        `;

        content.querySelectorAll('[data-jump]').forEach(b => {
            b.onclick = () => openPage(b.dataset.jump);
        });
    } catch (e) {
        content.innerHTML = `
            <div class="panel empty">
                Tidak dapat memuat dashboard: ${escapeHtml(e.message)}<br><br>
                Pastikan database tersambung dan file API berada satu folder dengan index.html.
            </div>`;
    }
}

async function renderCustomerHome() {
    const content = $('#content');
    content.innerHTML = '<div class="panel empty">Memuat pesanan kamu…</div>';

    try {
        const res = await api('api_customer.php');

        if (res.status !== 'success') {
            throw new Error(res.message || 'Gagal memuat pesanan');
        }

        const d = res.data || {};
        const orders = d.pesanan || [];

        const cards = orders.length
            ? orders.map(o => {
                const totalBiaya = Math.max(
                    0,
                    Number(o.total_biaya || 0)
                );

                // Menggunakan nama kolom yang dikirim API.
                const totalPembayaranTercatat = Math.max(
                    0,
                    Number(
                        o.total_pembayaran_tercatat ??
                        o.total_dibayar ??
                        0
                    )
                );

                const totalTerverifikasi = Math.max(
                    0,
                    Number(o.total_terbayar || 0)
                );

                const sisaTagihan = Math.max(
                    0,
                    Number(
                        o.sisa_tagihan ??
                        (totalBiaya - totalPembayaranTercatat)
                    )
                );

                const sudahLunas = sisaTagihan <= 0;

                // Sesuai aturan API: transaksi harus berstatus
                // Menunggu Pembayaran agar pembayaran dapat diajukan.
                const bisaBayar =
                    o.bisa_bayar === true ||
                    Number(o.bisa_bayar) === 1 ||
                    (
                        sisaTagihan > 0 &&
                        o.status_penyewaan === 'Menunggu Pembayaran'
                    );

                const statusPembayaran =
                    o.status_pembayaran || 'Belum ada pembayaran';

                const metodeStatus = Number(
                    o.pembayaran_menunggu || 0
                ) > 0
                    ? 'Ada pembayaran menunggu verifikasi Admin.'
                    : '';

                return `
                    <article class="order-card">
                        <div class="order-top">
                            <div>
                                <span class="eyebrow">KODE TRANSAKSI</span>
                                <h3>${escapeHtml(o.no_transaksi)}</h3>
                                <p>
                                    ${date(o.tanggal_sewa)}
                                    —
                                    ${date(o.tanggal_kembali)}
                                </p>
                            </div>

                            ${statusPill(o.status_penyewaan)}
                        </div>

                        <div class="order-total">
                            <span>Total harga</span>
                            <strong>${money(totalBiaya)}</strong>
                        </div>

                        <div class="order-items">
                            <h4>Detail pesanan</h4>

                            ${(o.items || []).length
                                ? o.items.map(i => `
                                    <div class="order-item">
                                        <span>
                                            ${escapeHtml(i.nama_alat)}
                                            <small>× ${Number(i.jumlah)}</small>
                                        </span>

                                        <span>
                                            ${money(
                                                Number(i.jumlah) *
                                                Number(i.tarif_sewa) *
                                                Math.max(
                                                    1,
                                                    Number(o.durasi_hari || 1)
                                                )
                                            )}
                                        </span>
                                    </div>
                                `).join('')
                                : '<p class="muted small">Detail alat belum tersedia.</p>'
                            }
                        </div>

                        ${o.status_penyewaan === 'Menunggu Pembayaran'
                            ? `
                                <div class="payment-box">
                                    <div>
                                        <span class="eyebrow">
                                            INFORMASI PEMBAYARAN
                                        </span>

                                        <h4>Transfer bank</h4>

                                        <p>
                                            Bank BCA · No. rekening
                                            <b>1234567890</b>
                                        </p>

                                        <p>
                                            Atas nama
                                            <b>Adventure Camp Rental</b>
                                        </p>

                                        <p>
                                            Kode bayar
                                            <b>
                                                ${escapeHtml(
                                                    o.kode_bayar ||
                                                    ('AC-' + o.no_transaksi)
                                                )}
                                            </b>
                                        </p>
                                    </div>

                                    <div>
                                        <button
                                            class="btn light copy-payment"
                                            data-copy="${escapeHtml(
                                                o.kode_bayar ||
                                                ('AC-' + o.no_transaksi)
                                            )}">
                                            Salin kode
                                        </button>

                                        ${bisaBayar
                                            ? `
                                                <button
                                                    class="btn primary confirm-payment"
                                                    data-no-transaksi="${escapeHtml(o.no_transaksi)}"
                                                    data-sisa-tagihan="${sisaTagihan}"
                                                    style="margin-top:8px">
                                                    ${totalPembayaranTercatat > 0
                                                        ? 'Bayar Sisa Tagihan'
                                                        : 'Konfirmasi Pembayaran'}
                                                </button>
                                            `
                                            : `
                                                <p class="muted small" style="margin-top:8px">
                                                    ${sudahLunas
                                                        ? 'Tagihan sudah lunas.'
                                                        : 'Pembayaran belum dapat dilakukan untuk status ini.'}
                                                </p>
                                            `
                                        }
                                    </div>
                                </div>

                                <div class="order-total">
                                    <span>Total pembayaran tercatat</span>
                                    <strong>${money(totalPembayaranTercatat)}</strong>
                                </div>

                                <div class="order-total">
                                    <span>Total sudah diverifikasi</span>
                                    <strong>${money(totalTerverifikasi)}</strong>
                                </div>

                                <div class="order-total">
                                    <span>Sisa tagihan</span>
                                    <strong>${money(sisaTagihan)}</strong>
                                </div>

                                ${metodeStatus
                                    ? `<p class="muted small">${metodeStatus}</p>`
                                    : ''
                                }
                            `
                            : `
                                <div class="payment-box">
                                    <div>
                                        <span class="eyebrow">
                                            INFORMASI PEMBAYARAN
                                        </span>

                                        <h4>
                                            ${o.status_penyewaan === 'Menunggu Konfirmasi'
                                                ? 'Menunggu persetujuan admin'
                                                : o.status_penyewaan === 'Ditolak'
                                                    ? 'Pengajuan ditolak'
                                                    : o.status_penyewaan === 'Selesai'
                                                        ? 'Penyewaan selesai'
                                                        : o.status_penyewaan === 'Lunas'
                                                            ? 'Penyewaan lunas'
                                                            : 'Informasi pembayaran belum tersedia'
                                            }
                                        </h4>

                                        <p>
                                            ${o.status_penyewaan === 'Menunggu Konfirmasi'
                                                ? 'Informasi pembayaran akan ditampilkan setelah pengajuan disetujui.'
                                                : o.status_penyewaan === 'Ditolak'
                                                    ? 'Silakan hubungi Admin jika membutuhkan penjelasan.'
                                                    : o.status_penyewaan === 'Selesai'
                                                        ? 'Terima kasih telah menyewa alat camping kami.'
                                                        : o.status_penyewaan === 'Lunas'
                                                            ? 'Periksa status pembayaran dan konfirmasi dari Admin.'
                                                            : 'Silakan tunggu informasi selanjutnya dari Admin.'
                                            }
                                        </p>
                                    </div>
                                </div>

                                <div class="order-total">
                                    <span>Total pembayaran tercatat</span>
                                    <strong>${money(totalPembayaranTercatat)}</strong>
                                </div>

                                <div class="order-total">
                                    <span>Sisa tagihan</span>
                                    <strong>${money(sisaTagihan)}</strong>
                                </div>
                            `
                        }

                        <div class="order-bottom">
                            <span>
                                Status pembayaran:
                                ${statusPill(
                                    sudahLunas && totalBiaya > 0
                                        ? 'Lunas'
                                        : statusPembayaran
                                )}
                            </span>

                            <span>
                                ${o.no_pembayaran
                                    ? `No. pembayaran terakhir: ${escapeHtml(o.no_pembayaran)}`
                                    : 'Belum ada catatan pembayaran'
                                }
                            </span>
                        </div>
                    </article>
                `;
            }).join('')
            : '<div class="panel empty">Belum ada pesanan untuk akun ini. Silakan hubungi Admin untuk membuat penyewaan.</div>';

        content.innerHTML = `
            <div class="customer-welcome">
                <div>
                    <p class="eyebrow">AREA PELANGGAN</p>
                    <h2>Halo, ${escapeHtml(d.nama || state.user.nama)} 👋</h2>
                    <p>
                        Pantau detail pesanan, total biaya, dan
                        instruksi pembayaran di sini.
                    </p>
                </div>

                <div class="customer-stat">
                    <small>Total pesanan</small>
                    <strong>${orders.length}</strong>
                </div>

                <div class="customer-stat">
                    <small>Total tagihan</small>
                    <strong>
                        ${money(
                            orders.reduce(
                                (s, o) => s + Number(o.total_biaya || 0),
                                0
                            )
                        )}
                    </strong>
                </div>
            </div>

            <div class="section-heading">
                <h3>Pesanan saya</h3>
                <span>${orders.length} transaksi</span>
            </div>

            ${cards}
        `;

        // ========================================
        // SALIN KODE PEMBAYARAN
        // ========================================

        content.querySelectorAll('.copy-payment').forEach(b => {
            b.onclick = async () => {
                try {
                    await navigator.clipboard.writeText(b.dataset.copy);
                    showMessage('Kode bayar berhasil disalin.');
                } catch {
                    showMessage('Kode bayar: ' + b.dataset.copy);
                }
            };
        });

        // ========================================
        // KONFIRMASI PEMBAYARAN
        // ========================================

        content.querySelectorAll('.confirm-payment').forEach(b => {
            b.onclick = async () => {
                const noTransaksi = b.dataset.noTransaksi;
                const sisaTagihan = Number(
                    b.dataset.sisaTagihan || 0
                );

                if (sisaTagihan <= 0) {
                    showMessage(
                        'Tagihan transaksi ini sudah lunas.',
                        true
                    );

                    await renderCustomerHome();
                    return;
                }

                const jumlahInput = prompt(
                    `Sisa tagihan transaksi ${noTransaksi}: ${money(sisaTagihan)}\n\n` +
                    'Masukkan jumlah yang ingin dibayar.\n' +
                    'Contoh: 30000 atau 30.000\n\n' +
                    'Pembayaran boleh sebagian, tetapi tidak boleh melebihi sisa tagihan.',
                    String(sisaTagihan)
                );

                if (
                    jumlahInput === null ||
                    jumlahInput.trim() === ''
                ) {
                    return;
                }

                const jumlahPembayaran =
                    parseRupiahInput(jumlahInput);

                if (
                    !Number.isSafeInteger(jumlahPembayaran) ||
                    jumlahPembayaran <= 0
                ) {
                    showMessage(
                        'Jumlah pembayaran tidak valid. Masukkan contoh 30000 atau 30.000.',
                        true
                    );
                    return;
                }

                if (jumlahPembayaran > sisaTagihan) {
                    showMessage(
                        `Pembayaran tidak boleh melebihi sisa tagihan ${money(sisaTagihan)}.`,
                        true
                    );
                    return;
                }

                const metodeInput = prompt(
                    'Masukkan metode pembayaran (Transfer Bank / QRIS):',
                    'Transfer Bank'
                );

                if (
                    metodeInput === null ||
                    !metodeInput.trim()
                ) {
                    return;
                }

                const metode = metodeInput.trim();

                if (!['Transfer Bank', 'QRIS'].includes(metode)) {
                    showMessage(
                        'Metode harus Transfer Bank atau QRIS.',
                        true
                    );
                    return;
                }

                b.disabled = true;
                b.textContent = 'Mengirim...';

                try {
                    const hasil = await post(
                        'api_post_pembayaran.php',
                        {
                            no_transaksi: noTransaksi,
                            jumlah_pembayaran: jumlahPembayaran,
                            metode_pembayaran: metode
                        }
                    );

                    if (hasil.status !== 'success') {
                        throw new Error(
                            hasil.message ||
                            'Pembayaran gagal dikirim.'
                        );
                    }

                    const nomorPembayaran =
                        hasil.no_pembayaran || 'Tercatat';

                    const sisaDariServer =
                        hasil.sisa_tagihan;

                    showMessage(
                        `Pembayaran berhasil dicatat. Nomor pembayaran: ${nomorPembayaran}. ` +
                        (
                            sisaDariServer !== undefined
                                ? `Sisa tagihan: ${money(sisaDariServer)}. `
                                : ''
                        ) +
                        'Menunggu verifikasi Admin.'
                    );

                    await renderCustomerHome();

                } catch (error) {
                    showMessage(
                        error.message ||
                        'Terjadi kesalahan saat mengirim pembayaran.',
                        true
                    );

                    b.disabled = false;
                    b.textContent = 'Bayar Sisa Tagihan';
                }
            };
        });

    } catch (e) {
        content.innerHTML = `
            <div class="panel empty">
                ${escapeHtml(e.message)}<br><br>
                Pastikan akun Pelanggan sudah terhubung ke data
                di tabel pelanggan. Periksa juga konfigurasi API
                dan koneksi database.
            </div>
        `;
    }
}

async function renderCustomerCatalog() {
    const content = $('#content');
    content.innerHTML = '<div class="panel empty">Memuat katalog alat camping...</div>';

    try {
        const res = await api('api_katalog_pelanggan.php');

        if (res.status !== 'success') {
            throw new Error(res.message || 'Gagal memuat katalog.');
        }

        const items = res.data || [];

        const cards = items.length
            ? items.map((item, index) => `
                <article class="panel" style="padding:18px">
                    <p class="eyebrow">${escapeHtml(item.kategori)}</p>
                    <h3>${escapeHtml(item.nama_alat)}</h3>
                    <p class="muted small">Kode: ${escapeHtml(item.kode_alat)}</p>
                    <p>Tarif sewa <strong>${money(item.tarif_sewa)}</strong> <span class="muted small">/ hari</span></p>
                    <p class="muted small">Stok tersedia: ${Number(item.stok)}</p>
                    <label for="jumlah-alat-${index}">Jumlah yang disewa</label>
                    <input id="jumlah-alat-${index}" class="catalog-quantity" type="number"
                        min="0" max="${Number(item.stok)}" value="0" data-index="${index}"
                        style="width:100%;margin-top:8px">
                </article>
            `).join('')
            : '<div class="panel empty">Belum ada alat yang tersedia.</div>';

        content.innerHTML = `
            <div class="section-heading">
                <div>
                    <p class="eyebrow">AREA PELANGGAN</p>
                    <h2>Katalog Alat Camping</h2>
                    <p class="muted">Pilih alat dan jumlah yang ingin kamu sewa.</p>
                </div>
                <button class="btn light" id="refreshCatalog">Muat ulang</button>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px;margin:20px 0">
                ${cards}
            </div>

            <section class="panel" style="padding:20px">
                <h3>Jadwal penyewaan</h3>
                <label for="catalogTanggalSewa">Tanggal mulai sewa</label>
                <input type="date" id="catalogTanggalSewa" required style="display:block;width:100%;margin:8px 0 16px">
                <label for="catalogTanggalKembali">Tanggal pengembalian</label>
                <input type="date" id="catalogTanggalKembali" required style="display:block;width:100%;margin:8px 0 16px">
                <div class="order-total"><span>Estimasi total biaya</span><strong id="catalogTotal">${money(0)}</strong></div>
                <p class="muted small" id="catalogDuration">Pilih tanggal dan jumlah alat untuk menghitung biaya.</p>
                <button class="btn" id="submitCatalogRental">Ajukan Penyewaan</button>
                <p class="muted small" id="catalogMessage"></p>
            </section>
        `;

        const quantities = [...content.querySelectorAll('.catalog-quantity')];
        const startInput = content.querySelector('#catalogTanggalSewa');
        const endInput = content.querySelector('#catalogTanggalKembali');
        const totalElement = content.querySelector('#catalogTotal');
        const durationElement = content.querySelector('#catalogDuration');
        const messageElement = content.querySelector('#catalogMessage');

        const today = new Date();
        const localToday = [
            today.getFullYear(),
            String(today.getMonth() + 1).padStart(2, '0'),
            String(today.getDate()).padStart(2, '0')
        ].join('-');

        startInput.min = localToday;
        endInput.min = localToday;

        function calculateCatalogTotal() {
            const start = startInput.value;
            const end = endInput.value;
            let days = 0;

            if (start && end) {
                const startDate = new Date(start + 'T00:00:00');
                const endDate = new Date(end + 'T00:00:00');
                days = Math.round((endDate - startDate) / 86400000);
            }

            let total = 0;
            let selectedCount = 0;

            quantities.forEach(input => {
                const item = items[Number(input.dataset.index)];
                let quantity = Number(input.value || 0);

                quantity = Math.max(0, Math.min(Number(item.stok), Math.floor(quantity)));

                if (input.value !== '' && Number(input.value) !== quantity) {
                    input.value = quantity;
                }

                if (quantity > 0) {
                    selectedCount += quantity;
                    total += quantity * Number(item.tarif_sewa);
                }
            });

            if (days > 0) total *= days;

            totalElement.textContent = money(total);
            durationElement.textContent = days > 0
                ? `${days} hari • ${selectedCount} unit alat dipilih`
                : 'Tanggal kembali harus setelah tanggal mulai sewa.';

            return { days, total, selectedCount };
        }

        quantities.forEach(input => input.addEventListener('input', calculateCatalogTotal));

        startInput.addEventListener('change', () => {
            endInput.min = startInput.value || localToday;

            if (endInput.value && endInput.value < startInput.value) {
                endInput.value = '';
            }

            calculateCatalogTotal();
        });

        endInput.addEventListener('change', calculateCatalogTotal);
        content.querySelector('#refreshCatalog').onclick = () => renderCustomerCatalog();

        content.querySelector('#submitCatalogRental').onclick = async () => {
            const result = calculateCatalogTotal();
            const submitButton = content.querySelector('#submitCatalogRental');

            if (result.selectedCount === 0) {
                messageElement.textContent = 'Pilih minimal satu alat terlebih dahulu.';
                return;
            }

            if (!startInput.value || !endInput.value || result.days <= 0) {
                messageElement.textContent = 'Pilih tanggal kembali setelah tanggal mulai sewa.';
                return;
            }

            const selectedItems = quantities.map(input => {
                const item = items[Number(input.dataset.index)];
                return {
                    kode_alat: item.kode_alat,
                    jumlah: Number(input.value || 0)
                };
            }).filter(item => item.jumlah > 0);

            if (!confirm('Kirim pengajuan penyewaan dengan estimasi biaya ' + totalElement.textContent + '?')) {
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Mengirim pengajuan...';
            messageElement.textContent = '';

            try {
                const response = await post('api_post_sewa_pelanggan.php', {
                    tanggal_sewa: startInput.value,
                    tanggal_kembali: endInput.value,
                    items: selectedItems
                });

                if (response.status !== 'success') {
                    throw new Error(response.message || 'Pengajuan penyewaan gagal.');
                }

                const transaksi = response.data?.no_transaksi || '-';
                messageElement.textContent = 'Pengajuan berhasil dikirim! Nomor transaksi: ' + transaksi;
                showMessage('Pengajuan berhasil dikirim. Nomor transaksi: ' + transaksi);

                quantities.forEach(input => input.value = 0);
                calculateCatalogTotal();

                setTimeout(() => openPage('dashboard'), 1500);
            } catch (error) {
                messageElement.textContent = error.message;
                showMessage(error.message, true);
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Ajukan Penyewaan';
            }
        };

        calculateCatalogTotal();
    } catch (e) {
        content.innerHTML = `<div class="panel empty">${escapeHtml(e.message)}</div>`;
    }
}

function barsChart(title, subtitle, items, valueKey, labelKey, format = 'number') {
    const vals = (items || []).map(x => Number(x[valueKey] || 0));
    const max = Math.max(...vals, 1);

    return `
        <div class="panel chart-panel">
            <div class="panel-head"><div><h3>${title}</h3><p>${subtitle}</p></div></div>
            <div class="bar-chart">
                ${(items || []).map(x => `
                    <div class="bar-col" title="${escapeHtml(x[labelKey])}: ${format === 'money' ? money(x[valueKey]) : Number(x[valueKey] || 0)}">
                        <div class="bar-value">${format === 'money' ? money(x[valueKey]) : Number(x[valueKey] || 0)}</div>
                        <div class="bar-track">
                            <div class="bar-fill" style="height:${Math.max(3, Number(x[valueKey] || 0) / max * 100)}%"></div>
                        </div>
                        <div class="bar-label">${escapeHtml(x[labelKey] ?? '')}</div>
                    </div>
                `).join('')}
            </div>
        </div>`;
}

async function loadOwnerDashboard() {
    const content = document.querySelector('#content');

    const rupiah = value => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(Number(value || 0));

    content.innerHTML = `
        <div class="owner-welcome">
            <p class="eyebrow">ADVENTURE CAMP RENTAL</p>
            <h2>Dashboard Pemilik 👋</h2>
            <p>Pantau penjualan dan pendapatan bisnis kamu.</p>
        </div>
        <div class="stats owner-stats">
            <div class="stat"><div class="stat-head">Penjualan Hari Ini</div><strong id="salesToday">—</strong><small>Jumlah transaksi</small></div>
            <div class="stat"><div class="stat-head">Penjualan Bulan Ini</div><strong id="salesMonth">—</strong><small>Jumlah transaksi</small></div>
            <div class="stat"><div class="stat-head">Pendapatan Hari Ini</div><strong id="incomeToday">—</strong><small>Pembayaran tercatat</small></div>
            <div class="stat"><div class="stat-head">Pendapatan Bulan Ini</div><strong id="incomeMonth">—</strong><small>Pembayaran tercatat</small></div>
        </div>
        <div class="chart-grid">
            <div class="panel"><h3>Penjualan 7 Hari Terakhir</h3><canvas id="chartSalesWeek"></canvas></div>
            <div class="panel"><h3>Penjualan Bulan Ini</h3><canvas id="chartSalesMonth"></canvas></div>
            <div class="panel"><h3>Pendapatan Hari Ini</h3><canvas id="chartIncomeToday"></canvas></div>
            <div class="panel"><h3>Pendapatan Bulan Ini</h3><canvas id="chartIncomeMonth"></canvas></div>
        </div>
        <div class="panel"><h3>Transaksi Terbaru</h3><div id="ownerTransactions">Memuat transaksi...</div></div>
    `;

    try {
        const response = await fetch('api_owner_dashboard.php', {
            headers: { Accept: 'application/json' }
        });
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'Gagal memuat dashboard');
        }

        const d = result.data || {};

        $('#salesToday').textContent = Number(d.penjualan_hari_ini || 0);
        $('#salesMonth').textContent = Number(d.penjualan_bulan_ini || 0);
        $('#incomeToday').textContent = rupiah(d.pendapatan_hari_ini);
        $('#incomeMonth').textContent = rupiah(d.pendapatan_bulan_ini);

        if (typeof Chart === 'undefined') throw new Error('Chart.js belum dimuat.');

        const charts = [
            { id: 'chartSalesWeek', data: d.grafik_penjualan_7_hari, label: 'Transaksi', money: false },
            { id: 'chartSalesMonth', data: d.grafik_penjualan_bulan, label: 'Transaksi', money: false },
            { id: 'chartIncomeToday', data: d.grafik_pendapatan_hari_ini, label: 'Pendapatan', money: true },
            { id: 'chartIncomeMonth', data: d.grafik_pendapatan_bulan, label: 'Pendapatan', money: true }
        ];

        charts.forEach(item => {
            const rows = item.data || [];

            new Chart(document.getElementById(item.id), {
                type: 'bar',
                data: {
                    labels: rows.map(row => row.label),
                    datasets: [{
                        label: item.label,
                        data: rows.map(row => Number(row.total || 0)),
                        backgroundColor: '#37865a',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => item.money ? rupiah(ctx.raw) : `${ctx.raw} transaksi`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => item.money ? rupiah(value) : value
                            }
                        }
                    }
                }
            });
        });

        const rows = d.terbaru || [];
        const table = $('#ownerTransactions');

        if (!rows.length) {
            table.textContent = 'Belum ada transaksi.';
        } else {
            const tableEl = document.createElement('table');

            tableEl.innerHTML = `
                <thead><tr><th>No. Transaksi</th><th>Pelanggan</th><th>Tanggal Sewa</th><th>Status</th></tr></thead>
            `;

            const tbody = document.createElement('tbody');

            rows.forEach(row => {
                const tr = document.createElement('tr');

                [row.no_transaksi, row.nama_pelanggan, row.tanggal_sewa, row.status_penyewaan].forEach(value => {
                    const td = document.createElement('td');
                    td.textContent = value ?? '—';
                    tr.appendChild(td);
                });

                tbody.appendChild(tr);
            });

            tableEl.appendChild(tbody);
            table.replaceChildren(tableEl);
        }
    } catch (error) {
        content.insertAdjacentHTML('beforeend', '<div class="panel empty" id="ownerError"></div>');
        $('#ownerError').textContent = 'Dashboard gagal dimuat: ' + error.message;
    }
}

function tableHtml(rows, cols) {
    if (!rows || !rows.length) {
        return '<div class="empty">Belum ada data untuk ditampilkan.</div>';
    }

    const canApprove = state.user?.role === 'Admin';

    return `
        <table>
            <thead>
                <tr>
                    ${cols.map(c => `<th>${escapeHtml(c[1])}</th>`).join('')}
                    ${canApprove ? '<th>Aksi</th>' : ''}
                </tr>
            </thead>
            <tbody>
                ${rows.map(r => {
                    const cells = cols.map(c => {
                        let v = r[c[0]];

                        if (c[0].startsWith('status_')) v = statusPill(v);
                        else if (/total_biaya|jumlah_pembayaran|nominal_denda|tarif_sewa|pendapatan/i.test(c[0])) v = money(v);
                        else if (/tanggal|created_at|waktu/i.test(c[0])) v = date(v);
                        else v = escapeHtml(v ?? '—');

                        return `<td>${v}</td>`;
                    }).join('');

                    let action = '';

                    if (canApprove && r.status_penyewaan === 'Menunggu Konfirmasi') {
                        action = `
                            <td>
                                <button class="btn primary approve-rental" data-no-transaksi="${escapeHtml(r.no_transaksi)}">Setujui</button>
                            </td>`;
                    } else if (canApprove) {
                        action = '<td>—</td>';
                    }

                    return `<tr>${cells}${action}</tr>`;
                }).join('')}
            </tbody>
        </table>
    `;
}

const columns = {
    alat: [
        ['kode_alat', 'Kode alat'], ['nama_alat', 'Nama alat'], ['kategori', 'Kategori'],
        ['stok', 'Stok'], ['kondisi', 'Kondisi'], ['tarif_sewa', 'Tarif/hari']
    ],
    pelanggan: [
        ['id_pelanggan', 'ID'], ['nama_pelanggan', 'Nama pelanggan'],
        ['alamat', 'Alamat'], ['nomor_telepon', 'Nomor telepon']
    ],
    penyewaan: [
        ['no_transaksi', 'No. transaksi'], ['nama_pelanggan', 'Pelanggan'],
        ['nama_user', 'Petugas'], ['tanggal_sewa', 'Tanggal sewa'],
        ['tanggal_kembali', 'Tanggal kembali'], ['total_biaya', 'Total biaya'],
        ['status_penyewaan', 'Status']
    ],
    pembayaran: [
        ['no_pembayaran', 'No. pembayaran'], ['no_transaksi', 'No. transaksi'],
        ['nama_pelanggan', 'Pelanggan'], ['total_biaya', 'Total tagihan'],
        ['jumlah_pembayaran', 'Dibayar'], ['metode_pembayaran', 'Metode'],
        ['tanggal_pembayaran', 'Tanggal'], ['status_pembayaran', 'Status']
    ],
    pengembalian: [
        ['id_pengembalian', 'ID'], ['no_transaksi', 'No. transaksi'],
        ['nama_pelanggan', 'Pelanggan'], ['tanggal_pengembalian_aktual', 'Tanggal kembali'],
        ['kondisi_alat', 'Kondisi alat'], ['status_pengembalian', 'Status'],
        ['nominal_denda', 'Denda'], ['alasan_denda', 'Alasan']
    ],
    users: [
        ['id_user', 'ID'], ['nama_user', 'Nama'], ['username', 'Username'],
        ['role', 'Role'], ['status_aktif', 'Aktif'], ['created_at', 'Dibuat']
    ]
};

const dataKinds = {
    alat: 'alat',
    pelanggan: 'pelanggan',
    penyewaan: 'penyewaan',
    pembayaran: 'pembayaran',
    pengembalian: 'pengembalian',
    users: 'users'
};

async function loadTable(page) {
    const content = $('#content');
    content.innerHTML = '<div class="panel empty">Memuat data…</div>';

    try {
        const res = await api('api_get_data.php', { jenis: dataKinds[page] || page });

        if (res.status === 'error') throw new Error(res.message || 'Gagal mengambil data');

        const rows = Array.isArray(res.data) ? res.data : Array.isArray(res) ? res : [];

        state.rows = rows;
        state.cache[page] = rows;
        renderTablePage(page, rows);
    } catch (e) {
        content.innerHTML = `
            <div class="panel empty">
                Gagal mengambil data: ${escapeHtml(e.message)}<br><br>
                Periksa query pada api_get_data.php dan struktur tabel database.
            </div>`;
    }
}

function renderTablePage(page, rows) {
    const canWrite = state.user?.role === 'Admin';
    const addAllowed = canWrite && ['alat', 'pelanggan', 'penyewaan', 'pembayaran', 'pengembalian'].includes(page);
    const cols = columns[page] || [];

    $('#content').innerHTML = `
        <div class="panel">
            <div class="panel-head">
                <div><h3>${titles[page]}</h3><p>${rows.length} baris data</p></div>
                <div class="table-tools">
                    <input id="tableSearch" class="search" placeholder="Cari data…">
                    <button class="btn primary ${addAllowed ? '' : 'hidden'}" id="addBtn">＋ Tambah data</button>
                </div>
            </div>
            <div id="tableArea" class="table-wrap">${tableHtml(rows, cols)}</div>
        </div>
    `;

    $('#tableSearch').addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        const filtered = rows.filter(r =>
            Object.values(r).some(v => String(v ?? '').toLowerCase().includes(q))
        );
        $('#tableArea').innerHTML = tableHtml(filtered, cols);
    });

    $('#addBtn')?.addEventListener('click', () => openForm(page));

    $('#tableArea').addEventListener('click', async e => {
        const button = e.target.closest('.approve-rental');
        if (!button) return;

        const noTransaksi = button.dataset.noTransaksi;
        if (!confirm(`Setujui penyewaan ${noTransaksi}?`)) return;

        button.disabled = true;
        button.textContent = 'Memproses...';

        try {
            const result = await post('api_setujui_penyewaan.php', { no_transaksi: noTransaksi });

            if (result.status !== 'success') {
                throw new Error(result.message || 'Penyewaan gagal disetujui');
            }

            alert(result.message || 'Penyewaan berhasil disetujui');
            await loadTable(page);
        } catch (error) {
            alert(error.message || 'Terjadi kesalahan saat menyetujui penyewaan');
            button.disabled = false;
            button.textContent = 'Setujui';
        }
    });
}

async function renderReports() {
    const content = $('#content');

    content.innerHTML = `
        <div class="panel">
            <div class="panel-head">
                <div><h3>Laporan penyewaan</h3><p>Gunakan rentang tanggal dan kata kunci.</p></div>
                <div class="report-filter">
                    <input id="fromDate" type="date" aria-label="Dari tanggal">
                    <input id="toDate" type="date" aria-label="Sampai tanggal">
                    <input id="reportSearch" placeholder="No. transaksi / pelanggan">
                    <button id="runRentalReport" class="btn primary">Tampilkan</button>
                </div>
            </div>
            <div id="rentalReport" class="table-wrap"><div class="empty">Pilih filter lalu tekan Tampilkan.</div></div>
        </div>
        <div class="panel">
            <div class="panel-head">
                <div><h3>Laporan pendapatan</h3><p>Data berdasarkan catatan pembayaran.</p></div>
                <button id="runIncomeReport" class="btn light">Muat pendapatan</button>
            </div>
            <div id="incomeReport" class="table-wrap"><div class="empty">Tekan Muat pendapatan untuk menampilkan data.</div></div>
        </div>
    `;

    $('#runRentalReport').onclick = () => runReport('penyewaan');
    $('#runIncomeReport').onclick = () => runReport('pendapatan');
}

async function runReport(kind) {
    const target = kind === 'penyewaan' ? '#rentalReport' : '#incomeReport';
    $(target).innerHTML = '<div class="empty">Memuat laporan…</div>';

    try {
        const q = { jenis: kind };
        q.dari = $('#fromDate').value;
        q.sampai = $('#toDate').value;
        q.cari = $('#reportSearch').value.trim();

        const res = await api('api_laporan.php', q);
        if (res.status === 'error') throw new Error(res.message || 'Gagal memuat');

        const cols = kind === 'penyewaan' ? columns.penyewaan : columns.pembayaran;
        $(target).innerHTML = tableHtml(res.data || [], cols);
    } catch (e) {
        $(target).innerHTML = `<div class="empty">${escapeHtml(e.message)}</div>`;
    }
}

$('#closeModal').onclick = $('#cancelModal').onclick = closeForm;

function closeForm() {
    $('#modal').classList.add('hidden');
    $('#formFields').innerHTML = '';
    $('#formMessage').textContent = '';
}

function fieldHtml(name, label, type, required, options = [], wide = false) {
    let input = '';

    if (type === 'select') {
        input = `<select name="${name}" ${required ? 'required' : ''}>
            ${options.map(o => `<option value="${escapeHtml(o.value ?? o)}">${escapeHtml(o.label ?? o)}</option>`).join('')}
        </select>`;
    } else if (type === 'textarea') {
        input = `<textarea name="${name}" rows="3" ${required ? 'required' : ''}></textarea>`;
    } else {
        input = `<input name="${name}" type="${type}" ${required ? 'required' : ''} ${type === 'number' ? 'min="0" step="any"' : ''}>`;
    }

    return `<label class="${wide ? 'wide' : ''}">${label}${input}</label>`;
}

async function openForm(kind) {
    state.formKind = kind;
    $('#modalTitle').textContent = 'Tambah ' + kinds[kind].label;
    $('#modal').classList.remove('hidden');

    const f = $('#formFields');
    f.innerHTML = '';

    if (kind === 'alat' || kind === 'pelanggan') {
        f.innerHTML = kinds[kind].fields.map(x =>
            fieldHtml(x[0], x[1], x[2], x[3], x[4] || [], x[2] === 'textarea')
        ).join('');

        if (kind === 'alat') {
            f.querySelector('[name=stok]').value = '0';
            f.querySelector('[name=tarif_sewa]').min = '0.01';
        }
        return;
    }

    try {
        if (kind === 'penyewaan') {
            const [p, a] = await Promise.all([
                api('api_get_data.php', { jenis: 'pelanggan' }),
                api('api_get_data.php', { jenis: 'alat' })
            ]);

            state.cache.formCustomers = p.data || [];
            state.cache.formAlat = a.data || [];

            f.innerHTML =
                fieldHtml('id_pelanggan', 'Pelanggan', 'select', true,
                    state.cache.formCustomers.map(x => ({
                        value: x.id_pelanggan,
                        label: `${x.nama_pelanggan} (${x.nomor_telepon || '-'})`
                    }))
                ) +
                fieldHtml('tanggal_sewa', 'Tanggal sewa', 'date', true) +
                fieldHtml('tanggal_kembali', 'Tanggal kembali', 'date', true) +
                `<div class="wide"><b>Daftar alat</b><div id="itemRows"></div>
                <button type="button" id="addItem" class="btn light add-line">＋ Tambah alat</button></div>`;

            $('#addItem').onclick = () => addItemRow();
            addItemRow();
        } else if (kind === 'pembayaran') {
            const r = await api('api_get_data.php', { jenis: 'penyewaan' });
            const running = (r.data || []).filter(x => x.status_penyewaan === 'Berjalan');

            f.innerHTML =
                fieldHtml('no_transaksi', 'Transaksi', 'select', true,
                    running.map(x => ({ value: x.no_transaksi, label: `${x.no_transaksi} — ${x.nama_pelanggan}` }))
                ) +
                fieldHtml('jumlah_pembayaran', 'Jumlah pembayaran (Rp)', 'number', true) +
                fieldHtml('metode_pembayaran', 'Metode pembayaran', 'select', true, ['Cash', 'Transfer', 'QRIS']);
        } else if (kind === 'pengembalian') {
            const r = await api('api_get_data.php', { jenis: 'penyewaan' });
            const running = (r.data || []).filter(x => x.status_penyewaan === 'Berjalan');

            f.innerHTML =
                fieldHtml('no_transaksi', 'Transaksi', 'select', true,
                    running.map(x => ({ value: x.no_transaksi, label: `${x.no_transaksi} — ${x.nama_pelanggan}` }))
                ) +
                fieldHtml('kondisi_alat', 'Kondisi alat saat kembali', 'text', true) +
                fieldHtml('status_pengembalian', 'Status pengembalian', 'select', true, ['Selesai', 'Bermasalah']) +
                fieldHtml('nominal_denda', 'Nominal denda (Rp)', 'number', false) +
                fieldHtml('alasan_denda', 'Alasan denda', 'text', false, [], true);
        }
    } catch (e) {
        $('#formMessage').textContent = 'Gagal memuat pilihan data: ' + e.message;
    }
}

function addItemRow() {
    const root = $('#itemRows');
    if (!root) return;

    const opts = state.cache.formAlat || [];
    const div = document.createElement('div');
    div.className = 'item-row';

    div.innerHTML = `
        <label>Alat
            <select class="item-code" required>
                ${opts.map(a => `<option value="${escapeHtml(a.kode_alat)}">${escapeHtml(a.nama_alat)} — stok ${a.stok}</option>`).join('')}
            </select>
        </label>
        <label>Jumlah
            <input class="item-qty" type="number" min="1" step="1" value="1" required>
        </label>
        <button type="button" class="remove-item" title="Hapus">✕</button>
    `;

    div.querySelector('.remove-item').onclick = () => {
        if (root.children.length > 1) div.remove();
        else showMessage('Minimal pilih satu alat.', true);
    };

    root.appendChild(div);
}

$('#dataForm').addEventListener('submit', async e => {
    e.preventDefault();

    const kind = state.formKind;
    const form = new FormData(e.target);
    const d = Object.fromEntries(form.entries());

    $('#formMessage').textContent = 'Menyimpan…';

    const btn = e.target.querySelector('[type=submit]');
    btn.disabled = true;

    try {
        let payload = { ...d, id_user: Number(state.user.id_user || 0) };

        if (kind === 'alat') {
            payload.stok = Number(d.stok);
            payload.tarif_sewa = Number(d.tarif_sewa);
            payload.kode_alat = d.kode_alat.trim().toUpperCase();
        }

        if (kind === 'penyewaan') {
            payload.id_pelanggan = Number(d.id_pelanggan);
            payload.items = [...document.querySelectorAll('.item-row')].map(r => ({
                kode_alat: r.querySelector('.item-code').value,
                jumlah: Number(r.querySelector('.item-qty').value)
            }));

            if (!payload.items.length) throw new Error('Tambahkan minimal satu alat.');
        }

        if (kind === 'pembayaran') {
            payload.jumlah_pembayaran = Number(d.jumlah_pembayaran);
        }

        if (kind === 'pengembalian') {
            payload.nominal_denda = Number(d.nominal_denda || 0);
        }

        const res = await post(kinds[kind].endpoint, payload);
        if (res.status !== 'success') throw new Error(res.message || 'Gagal menyimpan data');

        closeForm();
        showMessage(res.message || 'Data berhasil disimpan.');
        openPage(kind);
    } catch (err) {
        $('#formMessage').textContent = err.message;
    } finally {
        btn.disabled = false;
    }
});

const saved = localStorage.getItem('ac_user');

if (saved) {
    try {
        const u = JSON.parse(saved);
        if (u && u.role) setUser(u);
    } catch {
        localStorage.removeItem('ac_user');
    }
}
