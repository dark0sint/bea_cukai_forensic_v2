"""
Parser untuk file tabular / terstruktur: CSV, JSON, XML.
Digunakan untuk menarik data log transaksi, manifest, dokumen PB/PEB, dll.
"""
import json
import pandas as pd
from lxml import etree
from pathlib import Path
from typing import Any


class TabularParser:
    """Parser generik yang mengembalikan data dalam bentuk list of dict + metadata."""

    @staticmethod
    def parse_csv(filepath: Path) -> dict[str, Any]:
        df = pd.read_csv(filepath, dtype=str, keep_default_na=False, on_bad_lines="skip")
        return TabularParser._build_result(df, filepath)

    @staticmethod
    def parse_json(filepath: Path) -> dict[str, Any]:
        with open(filepath, "r", encoding="utf-8") as f:
            data = json.load(f)

        if isinstance(data, dict):
            # coba cari key list utama (mis. "records", "data", "transactions")
            list_key = next((k for k, v in data.items() if isinstance(v, list)), None)
            records = data[list_key] if list_key else [data]
        elif isinstance(data, list):
            records = data
        else:
            records = [{"value": data}]

        df = pd.json_normalize(records)
        return TabularParser._build_result(df, filepath)

    @staticmethod
    def parse_xml(filepath: Path) -> dict[str, Any]:
        tree = etree.parse(str(filepath))
        root = tree.getroot()

        records = []
        # Heuristik: ambil semua elemen anak level-2 sebagai satu "record"
        candidates = list(root)
        for node in candidates:
            record = {child.tag: (child.text or "").strip() for child in node}
            if not record and node.attrib:
                record = dict(node.attrib)
            if record:
                records.append(record)

        if not records:
            # fallback: root sendiri sebagai satu record
            records = [{child.tag: (child.text or "").strip() for child in root}]

        df = pd.DataFrame(records)
        return TabularParser._build_result(df, filepath)

    @staticmethod
    def _build_result(df: pd.DataFrame, filepath: Path) -> dict[str, Any]:
        df = df.fillna("")
        return {
            "source_file": filepath.name,
            "row_count": int(len(df)),
            "columns": list(df.columns),
            "records": df.to_dict(orient="records"),
            "dataframe": df,  # dipakai internal (dibuang sebelum dikirim sbg JSON response)
        }
