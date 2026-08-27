from pydantic import BaseModel, Field
from typing import Any, Optional


class UploadResponse(BaseModel):
    filename: str
    stored_as: str
    file_type: str
    hashes: dict
    parse_result: dict[str, Any]


class AnomalyRequest(BaseModel):
    records: list[dict[str, Any]]
    numeric_fields: Optional[list[str]] = None
    contamination: float = Field(default=0.05, ge=0.01, le=0.5)


class TimelineRequest(BaseModel):
    records: list[dict[str, Any]]
    timestamp_field: Optional[str] = None
    event_field: Optional[str] = None
    entity_field: Optional[str] = None


class GraphRequest(BaseModel):
    records: list[dict[str, Any]]
    source_field: str
    target_field: str
    weight_field: Optional[str] = None


class ReportRequest(BaseModel):
    case_name: str
    analyst: str = "System"
    summary: Optional[str] = None
    evidence_list: list[dict[str, Any]] = []
    anomaly_result: Optional[dict[str, Any]] = None
    timeline_result: Optional[dict[str, Any]] = None
    graph_result: Optional[dict[str, Any]] = None
    format: str = Field(default="pdf", pattern="^(pdf|html)$")
