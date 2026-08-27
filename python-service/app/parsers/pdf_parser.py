"""
Parser untuk dokumen PDF (mis. dokumen PIB/PEB, invoice, manifest kepabeanan hasil scan/cetak).
Mengekstrak teks per halaman + tabel bila terdeteksi, plus metadata dokumen (untuk forensik:
Author, CreationDate, Producer, ModDate - berguna melihat indikasi manipulasi dokumen).
"""
import pdfplumber
from PyPDF2 import PdfReader
from pathlib import Path
from typing import Any


class PDFParser:

    @staticmethod
    def parse(filepath: Path) -> dict[str, Any]:
        metadata = PDFParser._extract_metadata(filepath)
        pages_text = []
        tables = []

        with pdfplumber.open(filepath) as pdf:
            for i, page in enumerate(pdf.pages):
                text = page.extract_text() or ""
                pages_text.append({"page": i + 1, "text": text})

                page_tables = page.extract_tables()
                for t_idx, table in enumerate(page_tables):
                    tables.append({
                        "page": i + 1,
                        "table_index": t_idx,
                        "rows": table,
                    })

        return {
            "source_file": filepath.name,
            "page_count": len(pages_text),
            "metadata": metadata,
            "pages": pages_text,
            "tables": tables,
            "full_text": "\n".join(p["text"] for p in pages_text),
        }

    @staticmethod
    def _extract_metadata(filepath: Path) -> dict[str, Any]:
        """Metadata PDF penting untuk analisis forensik dokumen (indikasi edit/pemalsuan)."""
        try:
            reader = PdfReader(str(filepath))
            info = reader.metadata or {}
            return {
                "author": str(info.get("/Author", "")),
                "creator": str(info.get("/Creator", "")),
                "producer": str(info.get("/Producer", "")),
                "creation_date": str(info.get("/CreationDate", "")),
                "modification_date": str(info.get("/ModDate", "")),
                "is_encrypted": reader.is_encrypted,
                "num_pages": len(reader.pages),
            }
        except Exception as e:
            return {"error": f"Gagal membaca metadata PDF: {e}"}
