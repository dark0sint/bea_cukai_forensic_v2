"""
Unit test dasar untuk modul analisis. Jalankan dengan: pytest tests/
"""
import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from app.analysis.anomaly import AnomalyDetector
from app.analysis.timeline import TimelineBuilder
from app.analysis.graph import GraphBuilder


def test_anomaly_detects_outlier():
    records = [{"nilai_barang": 1000} for _ in range(20)]
    records.append({"nilai_barang": 500000})  # outlier jelas
    result = AnomalyDetector(contamination=0.05).analyze(records)
    assert result["anomaly_count"] >= 1


def test_timeline_orders_events():
    records = [
        {"timestamp": "2024-01-03T10:00:00Z", "event_type": "import", "entity_id": "A1"},
        {"timestamp": "2024-01-01T10:00:00Z", "event_type": "export", "entity_id": "A2"},
    ]
    result = TimelineBuilder().build(records)
    assert result["events"][0]["entity_id"] == "A2"


def test_graph_builds_edges():
    records = [
        {"importir": "PT A", "eksportir": "PT B"},
        {"importir": "PT A", "eksportir": "PT C"},
    ]
    result = GraphBuilder().build(records, source_field="importir", target_field="eksportir")
    assert result["node_count"] == 3
    assert result["edge_count"] == 2
