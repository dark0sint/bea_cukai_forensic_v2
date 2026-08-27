# Bea Cukai Forensic Suite v2

Proyek lanjutan dari [`dark0sint/bea_cukai_forensic`](https://github.com/dark0sint/bea_cukai_forensic) —
toolkit forensik digital untuk data kepabeanan (Bea Cukai). Versi ini memisahkan sistem
menjadi dua layanan yang saling terhubung:

- **`python-service/`** — Microservice analisis forensik (FastAPI). Bertugas: parsing file
  bukti (CSV/JSON/XML/PDF), hashing chain-of-custody, deteksi anomali, rekonstruksi timeline,
  analisis graf relasi entitas, dan pembuatan laporan PDF otomatis.
- **`laravel-app/`** — Dashboard web case-management (Laravel 11). Bertugas: autentikasi
  investigator, manajemen kasus, upload barang bukti, memicu analisis di Python service,
  menampilkan hasil, audit log, dan mengunduh laporan akhir.

Kedua layanan berkomunikasi lewat REST API dengan header `X-API-Key` (shared secret).

> **Disclaimer**: Tool ini ditujukan untuk keperluan forensik digital yang sah, edukasi, dan
> penelitian. Jangan gunakan untuk akses ilegal atau melanggar hukum. Selalu patuhi
> peraturan yang berlaku (UU ITE, UU Kepabeanan, dsb).

---

## Arsitektur

```
┌─────────────────┐        HTTPS/REST + X-API-Key        ┌───────────────────────┐
│   Laravel App    │ ─────────────────────────────────▶  │  Python FastAPI       │
│  (Dashboard Web)  │ ◀─────────────────────────────────  │  Forensic Service     │
│  MySQL + Redis    │        JSON / file PDF               │  parsing & analysis   │
└─────────────────┘                                       └───────────────────────┘
```

- Laravel menyimpan metadata kasus, evidence, hasil analisis (JSON), dan audit trail di MySQL.
- Python service tidak menyimpan data kasus — dia stateless per-request (kecuali file upload
  sementara & cache report), sehingga bisa di-scale horizontal dengan mudah.

---

## Struktur Folder

```
bea_cukai_forensic_v2/
├── docker-compose.yml          # orkestrasi semua service
├── .env.example                 # env root (dipakai docker-compose)
├── python-service/
│   ├── app/
│   │   ├── main.py               # entrypoint FastAPI
│   │   ├── api/routes.py         # semua endpoint REST
│   │   ├── core/                 # config, security (API key), hashing
│   │   ├── parsers/               # CSV, JSON, XML, PDF parser
│   │   ├── analysis/             # anomaly, timeline, graph
│   │   ├── reports/               # generator laporan HTML/PDF
│   │   └── models/schemas.py     # Pydantic request/response schema
│   ├── requirements.txt
│   ├── Dockerfile
│   └── tests/
└── laravel-app/
    ├── app/
    │   ├── Http/Controllers/     # Dashboard, Case, Evidence, Analysis, Report, Auth
    │   ├── Models/                # ForensicCase, EvidenceItem, AnalysisResult, AuditLog, User
    │   └── Services/PythonForensicService.php  # HTTP client ke python-service
    ├── database/migrations/
    ├── resources/views/           # Blade + TailwindCSS (CDN, tanpa build step)
    ├── routes/web.php
    ├── docker/                    # nginx.conf, supervisord.conf, entrypoint.sh
    └── Dockerfile
```

---

## Fitur

| Fitur | Keterangan |
|---|---|
| Manajemen Kasus | Buat kasus, atur prioritas & status, assign investigator |
| Upload Barang Bukti | CSV, JSON, XML, PDF — otomatis di-hash (SHA-256/MD5) untuk chain-of-custody |
| Deteksi Anomali | Isolation Forest + fallback statistik IQR, mendeteksi nilai transaksi tidak wajar |
| Rekonstruksi Timeline | Urutan kejadian otomatis + deteksi jeda waktu mencurigakan |
| Analisis Graf Relasi | Peta jaringan entitas (importir-eksportir dll), deteksi hub & komunitas/klaster |
| Laporan Otomatis | Generate PDF laporan investigasi lengkap dengan chain-of-custody |
| Audit Log | Semua aksi (upload, analisis, generate report) tercatat dengan IP & waktu |
| Autentikasi | Login/register bawaan, role admin/investigator |

---

## Menjalankan di Server (Docker — direkomendasikan)

### Prasyarat
- Docker Engine 24+ dan Docker Compose plugin
- Server dengan minimal 2 vCPU / 4GB RAM
- Port yang akan dipakai (default `8080`) tidak dipakai service lain

### Langkah-langkah

```bash
# 1. Clone / salin folder project ke server
cd bea_cukai_forensic_v2

# 2. Siapkan environment
cp .env.example .env
nano .env   # WAJIB ubah DB_PASSWORD, DB_ROOT_PASSWORD, FORENSIC_SERVICE_KEY

cp laravel-app/.env.example laravel-app/.env
nano laravel-app/.env   # samakan FORENSIC_SERVICE_KEY dengan .env root

cp python-service/.env.example python-service/.env
nano python-service/.env   # samakan API_SECRET_KEY dengan FORENSIC_SERVICE_KEY di atas

# 3. Build & jalankan semua service
docker compose up -d --build

# 4. Cek status
docker compose ps
docker compose logs -f laravel-app
```

Saat container `laravel-app` pertama kali start, `entrypoint.sh` otomatis akan:
- menunggu MySQL siap,
- generate `APP_KEY`,
- menjalankan migrasi database,
- seed user admin default,
- cache config/route/view.

### Akun default setelah instalasi

```
Email    : admin@beacukai-forensic.local
Password : ChangeMe123!
```

**Wajib segera login dan ganti password**, atau langsung `docker compose exec laravel-app php artisan tinker`
untuk update manual di database.

### Mengakses aplikasi

- Dashboard: `http://<ip-server-anda>:8080`
- Dokumentasi API Python (Swagger): `http://<ip-server-anda>:8000/docs` (disarankan tidak
  diexpose ke publik di production — lihat bagian Keamanan di bawah)

---

## Menjalankan Tanpa Docker (Manual)

### Python service

```bash
cd python-service
python3 -m venv venv && source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env   # edit API_SECRET_KEY
uvicorn app.main:app --host 0.0.0.0 --port 8000
```

### Laravel app

```bash
cd laravel-app
composer install --no-dev
cp .env.example .env
php artisan key:generate
# edit .env: DB_*, FORENSIC_SERVICE_URL=http://127.0.0.1:8000, FORENSIC_SERVICE_KEY
php artisan migrate --seed
php artisan storage:link
php artisan serve --host 0.0.0.0 --port 8080
# jalankan queue worker di terminal terpisah bila diperlukan:
php artisan queue:work
```

---

## Keamanan untuk Produksi

1. **Jangan expose port 8000 (python-service) langsung ke internet.** Biarkan hanya
   `laravel-app` yang bisa menjangkaunya (sudah didesain begitu lewat Docker network internal
   `forensic_net`). Jangan tambahkan `ports:` untuk `python-service` di `docker-compose.yml`
   kecuali untuk debugging lokal.
2. Ganti semua nilai default: `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `FORENSIC_SERVICE_KEY`,
   password admin default.
3. Pasang reverse proxy (Nginx/Traefik) + SSL (Let's Encrypt) di depan `laravel-app` untuk HTTPS.
4. Aktifkan backup rutin volume `mysql_data` dan `forensic_uploads` (barang bukti asli).
5. Batasi ukuran & tipe file upload sesuai kebutuhan (`MAX_UPLOAD_SIZE` di python-service,
   validasi `mimes:` di `EvidenceController`).
6. Pertimbangkan menambahkan 2FA dan rate-limiting login untuk akun investigator.

---

## Menambah Analisis Baru

Untuk menambah jenis analisis baru (mis. deteksi split-shipment, analisis pola HS Code):

1. Tambahkan modul baru di `python-service/app/analysis/nama_modul.py`.
2. Tambahkan endpoint baru di `python-service/app/api/routes.py`.
3. Tambahkan method baru di `laravel-app/app/Services/PythonForensicService.php`.
4. Tambahkan action di `AnalysisController.php` + tombol di `cases/show.blade.php`.

---

## Lisensi

MIT — mengikuti lisensi proyek asli. Gunakan secara bertanggung jawab dan sesuai hukum yang berlaku.
