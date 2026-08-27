"""
Membangun timeline kejadian dari data forensik (log transaksi, pergerakan dokumen,
event sistem) - filename asli proyek referensi memakai skema
"timestamp-event_type-entity_id", modul ini menggeneralisasi ide tersebut.
"""
import pandas as pd
from typing import Any


class TimelineBuilder:

    def build(
        self,
        records: list[dict],
        timestamp_field: str | None = None,
        event_field: str | None = None,
        entity_field: str | None = None,
    ) -> dict[str, Any]:
        df = pd.DataFrame(records)
        if df.empty:
            return {"events": [], "summary": "Tidak ada data untuk membangun timeline."}

        timestamp_field = timestamp_field or self._guess_field(df, ["timestamp", "date", "tanggal", "waktu", "time"])
        event_field = event_field or self._guess_field(df, ["event", "event_type", "jenis", "activity", "status"])
        entity_field = entity_field or self._guess_field(df, ["entity_id", "entity", "id", "nomor", "no_dokumen", "reference"])

        if not timestamp_field:
            return {"events": [], "summary": "Tidak ditemukan kolom timestamp/tanggal pada data."}

        df["_parsed_ts"] = pd.to_datetime(df[timestamp_field], errors="coerce", utc=True)
        df = df.dropna(subset=["_parsed_ts"]).sort_values("_parsed_ts")

        events = []
        for _, row in df.iterrows():
            events.append({
                "timestamp": row["_parsed_ts"].isoformat(),
                "event_type": str(row[event_field]) if event_field else "unknown_event",
                "entity_id": str(row[entity_field]) if entity_field else None,
                "raw": row.drop(labels=["_parsed_ts"]).to_dict(),
            })

        gaps = self._detect_time_gaps(df["_parsed_ts"].tolist())

        return {
            "fields_used": {
                "timestamp_field": timestamp_field,
                "event_field": event_field,
                "entity_field": entity_field,
            },
            "event_count": len(events),
            "time_range": {
                "start": events[0]["timestamp"] if events else None,
                "end": events[-1]["timestamp"] if events else None,
            },
            "suspicious_gaps": gaps,
            "events": events,
            "summary": f"Timeline berisi {len(events)} kejadian dari {events[0]['timestamp'] if events else '-'} sampai {events[-1]['timestamp'] if events else '-'}.",
        }

    @staticmethod
    def _guess_field(df: pd.DataFrame, keywords: list[str]) -> str | None:
        for col in df.columns:
            if any(k in col.lower() for k in keywords):
                return col
        return None

    @staticmethod
    def _detect_time_gaps(timestamps: list, threshold_hours: int = 72) -> list[dict]:
        """Menandai jeda waktu tidak wajar antar-kejadian berurutan (>72 jam default),
        yang di kasus forensik sering menandakan aktivitas disembunyikan / batch manipulasi."""
        gaps = []
        for i in range(1, len(timestamps)):
            delta = timestamps[i] - timestamps[i - 1]
            hours = delta.total_seconds() / 3600
            if hours >= threshold_hours:
                gaps.append({
                    "from": timestamps[i - 1].isoformat(),
                    "to": timestamps[i].isoformat(),
                    "gap_hours": round(hours, 1),
                })
        return gaps
