"""
Deteksi anomali pada data kepabeanan (transaksi impor/ekspor).
Menggabungkan pendekatan statistik klasik (Z-score, IQR) dan Isolation Forest
untuk menandai transaksi yang polanya menyimpang signifikan - berpotensi indikasi
under-invoicing, over-invoicing, split-shipment, atau volume tidak wajar.
"""
import pandas as pd
import numpy as np
from sklearn.ensemble import IsolationForest
from typing import Any


class AnomalyDetector:

    def __init__(self, contamination: float = 0.05):
        # contamination = perkiraan proporsi data anomali (default 5%)
        self.contamination = contamination

    def analyze(self, records: list[dict], numeric_fields: list[str] | None = None) -> dict[str, Any]:
        df = pd.DataFrame(records)
        if df.empty:
            return {"anomalies": [], "summary": "Tidak ada data untuk dianalisis.", "numeric_fields_used": []}

        numeric_fields = numeric_fields or self._auto_detect_numeric_fields(df)
        if not numeric_fields:
            return {
                "anomalies": [],
                "summary": "Tidak ditemukan kolom numerik (mis. nilai/berat/kuantitas) untuk dianalisis.",
                "numeric_fields_used": [],
            }

        work_df = df.copy()
        for col in numeric_fields:
            work_df[col] = pd.to_numeric(work_df[col], errors="coerce")
        work_df = work_df.dropna(subset=numeric_fields, how="all")
        work_df[numeric_fields] = work_df[numeric_fields].fillna(0)

        if len(work_df) < 5:
            return self._simple_statistical(df, work_df, numeric_fields)

        # --- Isolation Forest ---
        model = IsolationForest(contamination=self.contamination, random_state=42)
        preds = model.fit_predict(work_df[numeric_fields])
        scores = model.decision_function(work_df[numeric_fields])

        work_df["_anomaly_flag"] = preds == -1
        work_df["_anomaly_score"] = -scores  # makin besar = makin anomali

        # --- Z-score per kolom, sebagai penjelas kenapa dia anomali ---
        zscores = (work_df[numeric_fields] - work_df[numeric_fields].mean()) / work_df[numeric_fields].std(ddof=0).replace(0, 1)

        anomalies = []
        for idx in work_df[work_df["_anomaly_flag"]].index:
            reasons = []
            for col in numeric_fields:
                z = zscores.loc[idx, col]
                if abs(z) >= 2:
                    arah = "jauh di atas rata-rata" if z > 0 else "jauh di bawah rata-rata"
                    reasons.append(f"{col} {arah} (z-score={round(float(z), 2)})")

            original_row = df.loc[idx].to_dict()
            anomalies.append({
                "row_index": int(idx),
                "anomaly_score": round(float(work_df.loc[idx, "_anomaly_score"]), 4),
                "reasons": reasons or ["Kombinasi pola nilai tidak biasa (multivariat)"],
                "record": original_row,
            })

        anomalies.sort(key=lambda a: a["anomaly_score"], reverse=True)

        return {
            "total_records": int(len(df)),
            "numeric_fields_used": numeric_fields,
            "anomaly_count": len(anomalies),
            "anomaly_percentage": round(len(anomalies) / len(df) * 100, 2),
            "anomalies": anomalies,
            "summary": f"Ditemukan {len(anomalies)} dari {len(df)} record ({round(len(anomalies) / len(df) * 100, 2)}%) yang menyimpang dari pola normal.",
        }

    def _simple_statistical(self, df, work_df, numeric_fields) -> dict[str, Any]:
        """Fallback IQR method untuk dataset kecil (< 5 baris, ML kurang reliabel)."""
        anomalies = []
        for col in numeric_fields:
            q1, q3 = work_df[col].quantile([0.25, 0.75])
            iqr = q3 - q1
            lower, upper = q1 - 1.5 * iqr, q3 + 1.5 * iqr
            outlier_idx = work_df[(work_df[col] < lower) | (work_df[col] > upper)].index
            for idx in outlier_idx:
                anomalies.append({
                    "row_index": int(idx),
                    "anomaly_score": None,
                    "reasons": [f"{col} di luar rentang wajar (IQR): {work_df.loc[idx, col]}"],
                    "record": df.loc[idx].to_dict(),
                })
        return {
            "total_records": int(len(df)),
            "numeric_fields_used": numeric_fields,
            "anomaly_count": len(anomalies),
            "anomaly_percentage": round(len(anomalies) / max(len(df), 1) * 100, 2),
            "anomalies": anomalies,
            "summary": f"(Metode IQR - dataset kecil) Ditemukan {len(anomalies)} nilai di luar rentang wajar.",
        }

    @staticmethod
    def _auto_detect_numeric_fields(df: pd.DataFrame) -> list[str]:
        candidates = []
        keywords = ["nilai", "harga", "value", "amount", "qty", "quantity", "berat", "weight", "jumlah", "total", "price"]
        for col in df.columns:
            col_lower = col.lower()
            converted = pd.to_numeric(df[col], errors="coerce")
            ratio_numeric = converted.notna().mean()
            if ratio_numeric > 0.7 and (any(k in col_lower for k in keywords) or ratio_numeric > 0.9):
                candidates.append(col)
        return candidates
