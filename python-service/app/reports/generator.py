"""
Generator laporan forensik otomatis (HTML + PDF) yang merangkum hasil
parsing, deteksi anomali, timeline, dan graf relasi menjadi satu dokumen
siap-cetak untuk keperluan penyidikan / laporan kasus.
"""
import uuid
from datetime import datetime, timezone
from pathlib import Path
from jinja2 import Environment, BaseLoader
from app.core.config import settings

TEMPLATE = """
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Forensik - {{ case_name }}</title>
<style>
  body { font-family: 'Helvetica', Arial, sans-serif; font-size: 12px; color: #1a1a1a; }
  h1 { color: #0b3d91; border-bottom: 3px solid #0b3d91; padding-bottom: 8px; }
  h2 { color: #0b3d91; margin-top: 24px; border-bottom: 1px solid #ccc; padding-bottom: 4px;}
  .meta { background: #f2f4f8; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
  .meta table td { padding: 2px 8px 2px 0; vertical-align: top; }
  table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
  table.data th, table.data td { border: 1px solid #ddd; padding: 6px; font-size: 11px; text-align: left; }
  table.data th { background: #0b3d91; color: white; }
  .anomaly-high { background: #ffe0e0; }
  .badge { display:inline-block; padding:2px 8px; border-radius:10px; background:#0b3d91; color:white; font-size:10px;}
  .footer { margin-top: 40px; font-size: 10px; color: #777; border-top: 1px solid #ccc; padding-top: 8px; }
</style>
</head>
<body>
  <h1>Laporan Analisis Forensik Digital</h1>
  <div class="meta">
    <table>
      <tr><td><b>Nama Kasus</b></td><td>: {{ case_name }}</td></tr>
      <tr><td><b>Nomor Laporan</b></td><td>: {{ report_id }}</td></tr>
      <tr><td><b>Dibuat</b></td><td>: {{ generated_at }}</td></tr>
      <tr><td><b>Dianalisis oleh</b></td><td>: {{ analyst }}</td></tr>
      <tr><td><b>Jumlah Barang Bukti</b></td><td>: {{ evidence_count }}</td></tr>
    </table>
  </div>

  {% if summary %}
  <h2>Ringkasan Eksekutif</h2>
  <p>{{ summary }}</p>
  {% endif %}

  {% if evidence_list %}
  <h2>Daftar Barang Bukti &amp; Chain of Custody</h2>
  <table class="data">
    <tr><th>Nama File</th><th>SHA-256</th><th>Ukuran</th><th>Waktu Unggah</th></tr>
    {% for ev in evidence_list %}
    <tr>
      <td>{{ ev.filename }}</td>
      <td style="font-family:monospace; font-size:9px;">{{ ev.sha256 }}</td>
      <td>{{ ev.size_bytes }} bytes</td>
      <td>{{ ev.uploaded_at }}</td>
    </tr>
    {% endfor %}
  </table>
  {% endif %}

  {% if anomaly_result %}
  <h2>Hasil Deteksi Anomali</h2>
  <p>{{ anomaly_result.summary }}</p>
  {% if anomaly_result.anomalies %}
  <table class="data">
    <tr><th>#</th><th>Skor Anomali</th><th>Alasan</th></tr>
    {% for a in anomaly_result.anomalies[:50] %}
    <tr class="{{ 'anomaly-high' if a.anomaly_score and a.anomaly_score > 0.15 else '' }}">
      <td>{{ a.row_index }}</td>
      <td>{{ a.anomaly_score }}</td>
      <td>{{ a.reasons | join(', ') }}</td>
    </tr>
    {% endfor %}
  </table>
  {% endif %}
  {% endif %}

  {% if timeline_result %}
  <h2>Rekonstruksi Timeline</h2>
  <p>{{ timeline_result.summary }}</p>
  {% if timeline_result.suspicious_gaps %}
  <p><b>Jeda waktu mencurigakan terdeteksi:</b></p>
  <table class="data">
    <tr><th>Dari</th><th>Sampai</th><th>Durasi (jam)</th></tr>
    {% for g in timeline_result.suspicious_gaps %}
    <tr><td>{{ g.from }}</td><td>{{ g.to }}</td><td>{{ g.gap_hours }}</td></tr>
    {% endfor %}
  </table>
  {% endif %}
  {% endif %}

  {% if graph_result %}
  <h2>Analisis Jaringan Relasi Entitas</h2>
  <p>{{ graph_result.summary }}</p>
  {% if graph_result.top_hubs %}
  <p><b>Entitas paling berpengaruh (hub):</b></p>
  <table class="data">
    <tr><th>Entitas</th><th>Jumlah Koneksi</th><th>Centrality</th></tr>
    {% for n in graph_result.top_hubs %}
    <tr><td>{{ n.id }}</td><td>{{ n.degree }}</td><td>{{ n.degree_centrality }}</td></tr>
    {% endfor %}
  </table>
  {% endif %}
  {% endif %}

  <div class="footer">
    Dokumen ini dihasilkan otomatis oleh Bea Cukai Forensic Analysis Service v{{ version }}.
    Untuk keperluan forensik resmi, hasil ini harus diverifikasi ulang oleh analis bersertifikat.
  </div>
</body>
</html>
"""


class ReportGenerator:

    def __init__(self):
        self.env = Environment(loader=BaseLoader())
        self.template = self.env.from_string(TEMPLATE)

    def generate_html(self, context: dict) -> str:
        context.setdefault("generated_at", datetime.now(timezone.utc).isoformat())
        context.setdefault("report_id", str(uuid.uuid4())[:8].upper())
        context.setdefault("version", "2.0.0")
        return self.template.render(**context)

    def generate_pdf(self, context: dict) -> Path:
        """Render HTML lalu convert ke PDF via WeasyPrint, simpan ke storage/reports."""
        from weasyprint import HTML  # import lokal agar service tetap jalan walau weasyprint gagal build di sistem tertentu

        html_content = self.generate_html(context)
        report_id = context.get("report_id", str(uuid.uuid4())[:8].upper())
        out_path = settings.REPORT_DIR / f"forensic_report_{report_id}.pdf"
        HTML(string=html_content).write_pdf(str(out_path))
        return out_path
