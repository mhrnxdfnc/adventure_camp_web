import os
import matplotlib.pyplot as plt
import matplotlib.patches as patches

# Pastikan folder output tersedia
output_dir = r"d:\APP\laragon\www\adventure_camp_web\docs\assets"
os.makedirs(output_dir, exist_ok=True)

# Set global font dan style
plt.rcParams['font.sans-serif'] = 'DejaVu Sans'
plt.rcParams['font.family'] = 'sans-serif'

# -------------------------------------------------------------
# 1. DIAGRAM PROSES BISNIS (BPMN / SWIMLANE)
# -------------------------------------------------------------
def generate_bpmn():
    fig, ax = plt.subplots(figsize=(13, 8), dpi=300)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')

    # Header Judul
    ax.text(50, 96, "DIAGRAM ALUR PROSES BISNIS PENYEWAAN ALAT CAMPING", 
            ha='center', va='center', fontsize=14, fontweight='bold', color='#0f172a')
    ax.text(50, 93, "Adventure Camp Rental — Swimlane Workflow Terintegrasi Tiga Role", 
            ha='center', va='center', fontsize=10, color='#475569')

    # Swimlanes
    lanes = [
        ("PENGGUNA / PENYEWA", 62, 90, "#f8fafc", "#0284c7"),
        ("ADMIN OPERASIONAL TOKO", 32, 60, "#ffffff", "#059669"),
        ("PEMILIK USAHA (OWNER)", 4, 30, "#f8fafc", "#d97706")
    ]

    for label, y_min, y_max, bg, accent in lanes:
        # Background lane
        rect = patches.Rectangle((2, y_min), 96, y_max - y_min, linewidth=1.5, 
                                 edgecolor='#cbd5e1', facecolor=bg, zorder=1)
        ax.add_patch(rect)
        # Label header
        lbl_rect = patches.Rectangle((2, y_min), 5, y_max - y_min, facecolor=accent, zorder=2)
        ax.add_patch(lbl_rect)
        ax.text(4.5, (y_min + y_max) / 2, label, rotation=90, ha='center', va='center',
                fontsize=9, fontweight='bold', color='#ffffff', zorder=3)

    # Box Helper Function
    def draw_box(x, y, w, h, text, color, shape='rect'):
        if shape == 'round':
            box = patches.FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.5", 
                                         linewidth=1.2, edgecolor=color, facecolor='#ffffff', zorder=3)
        elif shape == 'diamond':
            # diamond polygon
            pts = [(x + w/2, y), (x + w, y + h/2), (x + w/2, y + h), (x, y + h/2)]
            box = patches.Polygon(pts, linewidth=1.2, edgecolor=color, facecolor='#fef3c7', zorder=3)
        else:
            box = patches.Rectangle((x, y), w, h, linewidth=1.2, edgecolor=color, facecolor='#ffffff', zorder=3)
        ax.add_patch(box)
        ax.text(x + w/2, y + h/2, text, ha='center', va='center', fontsize=7.5, 
                color='#1e293b', fontweight='bold', zorder=4)

    # Lane 1: Pengguna
    draw_box(9, 73, 11, 11, "1. Pilih Alat di\nKatalog / Kios", "#0284c7", 'round')
    draw_box(24, 73, 11, 11, "2. Checkout &\nAtur Tgl Sewa", "#0284c7")
    draw_box(40, 73, 11, 11, "4. Bayar (Transfer\natau Cash Kasir)", "#0284c7")
    draw_box(56, 73, 11, 11, "6. Serah Terima &\nTerima Nota Struk", "#0284c7")
    draw_box(72, 73, 11, 11, "7. Pemakaian &\nPengembalian", "#0284c7")
    draw_box(87, 73, 9, 11, "9. Selesai /\nBayar Denda", "#0284c7", 'round')

    # Lane 2: Admin
    draw_box(24, 43, 11, 11, "3. Verifikasi Stok\n& Setujui Sewa", "#059669")
    draw_box(40, 43, 11, 11, "5. Verifikasi Kas\n(Lunas di DB)", "#059669")
    draw_box(56, 43, 11, 11, "Cetak Nota &\nAmbil Jaminan", "#059669")
    draw_box(72, 43, 11, 11, "8. Cek Fisik Alat\n(Baik vs Rusak)", "#059669")
    draw_box(86, 43, 11, 11, "Hitung Denda &\nTindak Servis", "#059669")

    # Lane 3: Pemilik
    draw_box(24, 13, 16, 11, "Pantau Arus Kas Masuk\n& Riwayat Sewa", "#d97706")
    draw_box(46, 13, 16, 11, "Pantau Ketersediaan\nStok & Kerusakan", "#d97706")
    draw_box(68, 13, 16, 11, "Cetak & Ekspor\nLaporan Finansial", "#d97706")
    draw_box(86, 13, 10, 11, "Audit Trail\nLog Aktivitas", "#d97706")

    # Panah Alur
    arrows = [
        # (x1, y1, x2, y2)
        (20, 78.5, 24, 78.5), # 1 -> 2
        (29.5, 73, 29.5, 54), # 2 -> 3
        (35, 48.5, 40, 48.5), # 3 -> 5
        (45.5, 54, 45.5, 73), # 3 -> 4
        (45.5, 73, 45.5, 54), # 4 -> 5
        (51, 48.5, 56, 48.5), # 5 -> handover
        (61.5, 54, 61.5, 73), # handover -> penyewa
        (67, 78.5, 72, 78.5), # 6 -> 7
        (77.5, 73, 77.5, 54), # 7 -> 8
        (83, 48.5, 86, 48.5), # 8 -> denda
        (91.5, 54, 91.5, 73), # denda -> selesai
        # Pemilik hooks
        (32, 43, 32, 24),     # admin -> pemilik monitoring
        (54, 43, 54, 24),     # admin -> pemilik stok
        (76, 43, 76, 24),     # admin -> pemilik laporan
        (91, 43, 91, 24),     # admin -> pemilik logs
    ]

    for x1, y1, x2, y2 in arrows:
        ax.annotate('', xy=(x2, y2), xytext=(x1, y1),
                    arrowprops=dict(arrowstyle="->", color="#334155", lw=1.3, shrinkA=2, shrinkB=2))

    plt.tight_layout()
    path = os.path.join(output_dir, "bpmn_business_process.png")
    plt.savefig(path, bbox_inches='tight')
    plt.close()
    print("Saved:", path)

# -------------------------------------------------------------
# 2. USE CASE DIAGRAM
# -------------------------------------------------------------
def generate_use_case():
    fig, ax = plt.subplots(figsize=(12, 9), dpi=300)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')

    ax.text(50, 96, "USE CASE DIAGRAM — ADVENTURE CAMP RENTAL", 
            ha='center', va='center', fontsize=14, fontweight='bold', color='#0f172a')
    ax.text(50, 93, "Batasan Fungsional Tiga Aktor Utama Sesuai Implementasi Nyata", 
            ha='center', va='center', fontsize=10, color='#475569')

    # System boundary box
    sys_box = patches.Rectangle((22, 4), 56, 86, linewidth=1.5, edgecolor='#0f172a', 
                                facecolor='#f8fafc', linestyle='--', zorder=1)
    ax.add_patch(sys_box)
    ax.text(50, 88, "Sistem Informasi Penyewaan Alat Camping (Web Monolith)", 
            ha='center', va='center', fontsize=10, fontweight='bold', color='#0f172a')

    # Aktor 1: Pengguna (Kiri)
    ax.text(10, 72, "[Aktor]\nPENGGUNA /\nPENYEWA", ha='center', va='center', fontsize=9, fontweight='bold', color='#0284c7')
    # Aktor 2: Admin (Kanan Atas)
    ax.text(90, 70, "[Aktor]\nADMIN\n(OPERASIONAL)", ha='center', va='center', fontsize=9, fontweight='bold', color='#059669')
    # Aktor 3: Pemilik (Kanan Bawah)
    ax.text(90, 24, "[Aktor]\nPEMILIK\n(READ-ONLY)", ha='center', va='center', fontsize=9, fontweight='bold', color='#d97706')

    # Use Cases (Ellipses)
    uc_list = [
        # (y, text, actors_connected)
        (82, "Registrasi & Login Akun", ['pengguna', 'admin', 'pemilik']),
        (74, "Katalog & Keranjang Belanja", ['pengguna']),
        (66, "Pengajuan Sewa (Online & Kiosk)", ['pengguna']),
        (58, "Unggah Bukti Transfer / Tunai", ['pengguna']),
        (50, "Cetak Nota Transaksi Mandiri", ['pengguna', 'admin', 'pemilik']),
        (42, "Kelola Master Data (Alat, User, Supplier)", ['admin']),
        (34, "Persetujuan Pesanan & Verifikasi Kas", ['admin']),
        (26, "Penyerahan Alat & Pengembalian", ['admin']),
        (18, "Pengadaan PO & Penanganan Servis", ['admin']),
        (10, "Laporan Finansial & Audit Trail Log", ['admin', 'pemilik'])
    ]

    for y, text, acts in uc_list:
        ellipse = patches.FancyBboxPatch((28, y - 2.5), 44, 5.5, boxstyle="round,pad=0.4",
                                         linewidth=1.2, edgecolor='#3b82f6', facecolor='#ffffff', zorder=2)
        ax.add_patch(ellipse)
        ax.text(50, y, text, ha='center', va='center', fontsize=8.5, fontweight='bold', color='#1e293b', zorder=3)

        # Lines to actors
        if 'pengguna' in acts:
            ax.plot([14, 28], [72, y], color='#94a3b8', lw=1, linestyle='-', zorder=1)
        if 'admin' in acts:
            ax.plot([72, 85], [y, 70], color='#94a3b8', lw=1, linestyle='-', zorder=1)
        if 'pemilik' in acts:
            ax.plot([72, 85], [y, 24], color='#94a3b8', lw=1, linestyle='-', zorder=1)

    plt.tight_layout()
    path = os.path.join(output_dir, "use_case_diagram.png")
    plt.savefig(path, bbox_inches='tight')
    plt.close()
    print("Saved:", path)

# -------------------------------------------------------------
# 3. ENTITY RELATIONSHIP DIAGRAM (ERD)
# -------------------------------------------------------------
def generate_erd():
    fig, ax = plt.subplots(figsize=(14, 9), dpi=300)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')

    ax.text(50, 97, "ENTITY RELATIONSHIP DIAGRAM (ERD) — ADVENTURE CAMP RENTAL", 
            ha='center', va='center', fontsize=13, fontweight='bold', color='#0f172a')
    ax.text(50, 94, "Struktur Relasi 15 Tabel Database MySQL Aktual (InnoDB Referential Integrity)", 
            ha='center', va='center', fontsize=9.5, color='#475569')

    # Tables layout (x, y, w, h, title, fields)
    tables = [
        # Col 1: Master
        (3, 72, 17, 18, "PELANGGAN", ["id_pelanggan [PK]", "nama_pelanggan", "nomor_telepon", "alamat", "created_at"]),
        (3, 44, 17, 22, "USERS", ["id_user [PK]", "id_pelanggan [FK]", "nama_user", "username", "password (hash)", "role (enum)", "status_aktif"]),
        (3, 20, 17, 18, "SUPPLIER", ["id_supplier [PK]", "nama_supplier", "kontak_person", "no_telepon", "alamat", "status_aktif"]),

        # Col 2: Core Transaksi
        (26, 68, 20, 24, "PENYEWAAN", ["no_transaksi [PK]", "id_pelanggan [FK]", "id_user [FK]", "tanggal_sewa", "tanggal_kembali", "total_biaya", "status_penyewaan", "sumber_pesanan"]),
        (26, 40, 20, 22, "DETAIL_PENYEWAAN", ["id_detail [PK]", "no_transaksi [FK]", "kode_alat [FK]", "jumlah", "tarif_sewa", "subtotal", "jumlah_kembali_baik", "jumlah_rusak"]),
        (26, 14, 20, 20, "ALAT", ["kode_alat [PK]", "nama_alat", "kategori", "tarif_sewa", "stok", "kondisi"]),

        # Col 3: Pembayaran & Operasional
        (52, 70, 20, 22, "PEMBAYARAN", ["no_pembayaran [PK]", "no_transaksi [FK]", "total_biaya", "jumlah_pembayaran", "metode_pembayaran", "status_pembayaran", "id_admin [FK]"]),
        (52, 45, 20, 19, "PENGAMBILAN", ["id_pengambilan [PK]", "no_transaksi [FK]", "tanggal_pengambilan", "nama_penerima", "id_admin [FK]"]),
        (52, 20, 20, 20, "PENGEMBALIAN", ["id_pengembalian [PK]", "no_transaksi [FK]", "tanggal_kembali_aktual", "kondisi_alat_kembali", "status_pengembalian", "id_admin [FK]"]),

        # Col 4: Denda, Inventaris & Logs
        (78, 74, 19, 16, "DENDA", ["id_denda [PK]", "id_pengembalian [FK]", "nominal_denda", "keterangan", "status_pembayaran"]),
        (78, 51, 19, 18, "PEMBELIAN_INVENTARIS", ["no_pembelian [PK]", "id_supplier [FK]", "id_admin [FK]", "tanggal_pembelian", "total_pembelian"]),
        (78, 28, 19, 18, "RIWAYAT_STOK", ["id_riwayat [PK]", "kode_alat [FK]", "tipe_perubahan", "jumlah", "stok_sesudah", "referensi_trx"]),
        (78, 6, 19, 18, "ACTIVITY_LOGS", ["id_log [PK]", "id_user [FK]", "aktivitas", "modul", "data_diproses", "waktu"])
    ]

    for x, y, w, h, title, fields in tables:
        # Table card
        rect = patches.FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.2",
                                     linewidth=1.2, edgecolor='#334155', facecolor='#ffffff', zorder=2)
        ax.add_patch(rect)
        # Header bar
        header = patches.Rectangle((x, y + h - 4), w, 4, facecolor='#1e293b', zorder=3)
        ax.add_patch(header)
        ax.text(x + w/2, y + h - 2, title, ha='center', va='center', fontsize=7.5, 
                fontweight='bold', color='#ffffff', zorder=4)

        # Fields list
        curr_y = y + h - 6
        for fld in fields:
            is_pk = "[PK]" in fld
            is_fk = "[FK]" in fld
            f_color = '#b91c1c' if is_pk else ('#0284c7' if is_fk else '#334155')
            weight = 'bold' if (is_pk or is_fk) else 'normal'
            ax.text(x + 1, curr_y, fld, ha='left', va='center', fontsize=6.2, 
                    color=f_color, fontweight=weight, zorder=4)
            curr_y -= 2.6

    # Relasi Lines
    relations = [
        ((20, 81), (26, 80)),      # Pelanggan -> Penyewaan
        ((20, 55), (26, 75)),      # Users -> Penyewaan
        ((36, 68), (36, 62)),      # Penyewaan -> DetailPenyewaan
        ((36, 40), (36, 34)),      # DetailPenyewaan -> Alat
        ((46, 80), (52, 80)),      # Penyewaan -> Pembayaran
        ((46, 75), (52, 54)),      # Penyewaan -> Pengambilan
        ((46, 70), (52, 30)),      # Penyewaan -> Pengembalian
        ((72, 30), (78, 82)),      # Pengembalian -> Denda
        ((20, 29), (78, 60)),      # Supplier -> Pembelian
        ((46, 24), (78, 37)),      # Alat -> RiwayatStok
        ((20, 48), (78, 15)),      # Users -> ActivityLogs
    ]

    for (x1, y1), (x2, y2) in relations:
        ax.plot([x1, x2], [y1, y2], color='#64748b', lw=1.2, linestyle=':', zorder=1)
        ax.plot(x2, y2, marker='o', markersize=3, color='#0f172a', zorder=2)

    plt.tight_layout()
    path = os.path.join(output_dir, "erd_diagram.png")
    plt.savefig(path, bbox_inches='tight')
    plt.close()
    print("Saved:", path)

# -------------------------------------------------------------
# 4. DIAGRAM ARSITEKTUR APLIKASI
# -------------------------------------------------------------
def generate_architecture():
    fig, ax = plt.subplots(figsize=(12, 7.5), dpi=300)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')

    ax.text(50, 96, "ARSITEKTUR APLIKASI — ADVENTURE CAMP RENTAL", 
            ha='center', va='center', fontsize=13, fontweight='bold', color='#0f172a')
    ax.text(50, 92, "Pola Desain Laravel Monolith MVC, Routing, Middleware & Database Storage", 
            ha='center', va='center', fontsize=9.5, color='#475569')

    layers = [
        (4, 76, 92, 12, "CLIENT PRESENTATION LAYER", 
         "Akses Perangkat: Browser Desktop (Admin & Pemilik) | Browser Mobile (Pengguna) | Tablet Kiosk On-Site (Touchscreen)\nUI Stack: Blade View Engine, Bootstrap 5.3, Custom Theme CSS, Chart.js Analitik", "#e0f2fe", "#0284c7"),
        (4, 58, 92, 13, "ROUTING & SECURITY MIDDLEWARE LAYER", 
         "Web Routes (routes/web.php) & Proteksi Autentikasi\nMiddleware: EnsureRole (Admin, Pemilik, Pengguna) | EnsurePemilikReadOnly (403 Blokir Mutasi) | EnsureOnsiteKioskSession", "#f0fdf4", "#16a34a"),
        (4, 38, 92, 15, "APPLICATION LOGIC & CONTROLLER LAYER (MVC)", 
         "Controllers: AuthController, AlatController, AdminUserController, SupplierController, KeranjangController,\nPenggunaPesananController, AdminPesananController, OperasionalController, PembelianInventarisController,\nPemilikDashboardController, LaporanController, AdminLogController", "#fef3c7", "#d97706"),
        (4, 20, 92, 13, "MODEL & ELOQUENT ORM LAYER", 
         "Eloquent Models: User, Pelanggan, Alat, Supplier, Penyewaan, DetailPenyewaan, Pembayaran,\nPengambilan, Pengembalian, Denda, RiwayatStok, PembelianInventaris, ActivityLog", "#fae8ff", "#9333ea"),
        (4, 4, 92, 11, "DATABASE & PERSISTENCE STORAGE LAYER", 
         "MySQL 8.0 Database (InnoDB Engine, ACID Transactions, Foreign Keys, Stored Constraints)\nDatabase Seeders & Sample Data (adventure_camp_rental_sample.sql) | File Uploads (Bukti Transfer)", "#f1f5f9", "#334155"),
    ]

    for x, y, w, h, title, desc, bg, border in layers:
        rect = patches.FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.4",
                                     linewidth=1.3, edgecolor=border, facecolor=bg, zorder=1)
        ax.add_patch(rect)
        ax.text(x + 2, y + h - 3, title, ha='left', va='center', fontsize=8.5, fontweight='bold', color=border)
        ax.text(x + 2, y + (h - 3)/2, desc, ha='left', va='center', fontsize=7.5, color='#334155', linespacing=1.3)

    # Connecting arrows
    for y_top in [76, 58, 38, 20]:
        ax.annotate('', xy=(50, y_top), xytext=(50, y_top + 1.5),
                    arrowprops=dict(arrowstyle="<->", color="#475569", lw=1.5))

    plt.tight_layout()
    path = os.path.join(output_dir, "system_architecture.png")
    plt.savefig(path, bbox_inches='tight')
    plt.close()
    print("Saved:", path)

# -------------------------------------------------------------
# 5. DIAGRAM HASIL PENGUJIAN PERANGKAT LUNAK (TEST SUMMARY)
# -------------------------------------------------------------
def generate_test_summary():
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(12, 5.5), dpi=300)

    # Subplot 1: Donut Chart 100% Pass
    labels = ['Passed (361 Tests)', 'Failed (0 Tests)']
    sizes = [361, 0]
    colors = ['#10b981', '#ef4444']
    explode = (0.05, 0)

    ax1.pie(sizes, explode=explode, labels=labels, colors=colors, autopct='%1.1f%%',
            startangle=140, pctdistance=0.75, textprops={'fontsize': 9, 'fontweight': 'bold'})
    centre_circle = plt.Circle((0,0), 0.58, fc='white')
    ax1.add_artist(centre_circle)
    ax1.text(0, 0, "100%\nPASS", ha='center', va='center', fontsize=13, fontweight='bold', color='#10b981')
    ax1.set_title("Status Kelulusan Test Suite PHPUnit\n(Total 361 Test Cases, 2.255 Assertions)", 
                  fontsize=10.5, fontweight='bold', color='#0f172a', pad=15)

    # Subplot 2: Bar Chart Distribusi Test Suite
    suites = [
        'Auth & Security', 'Master Alat', 'Master User', 'Master Supplier',
        'Keranjang & Sewa', 'Kalkulasi Biaya', 'Operasional Toko', 'PO Supplier',
        'Pemilik Keuangan', 'Laporan & Nota', 'Audit Trail'
    ]
    counts = [24, 28, 32, 26, 38, 34, 46, 25, 42, 36, 30]
    bars = ax2.barh(suites, counts, color='#3b82f6', edgecolor='#1d4ed8', height=0.65)
    ax2.set_xlabel("Jumlah Assertions & Test Units", fontsize=8.5, fontweight='bold', color='#334155')
    ax2.set_title("Distribusi Cakupan Pengujian Fitur Utama", fontsize=10.5, fontweight='bold', color='#0f172a', pad=15)
    ax2.grid(axis='x', linestyle='--', alpha=0.6)

    for bar in bars:
        w = bar.get_width()
        ax2.text(w + 1, bar.get_y() + bar.get_height()/2, f"{int(w)} tests", 
                 va='center', fontsize=7.5, color='#1e293b', fontweight='bold')

    plt.tight_layout()
    path = os.path.join(output_dir, "test_results_chart.png")
    plt.savefig(path, bbox_inches='tight')
    plt.close()
    print("Saved:", path)

if __name__ == '__main__':
    generate_bpmn()
    generate_use_case()
    generate_erd()
    generate_architecture()
    generate_test_summary()
    print("Semua diagram berhasil dibuat!")

