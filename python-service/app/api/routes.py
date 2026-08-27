import shutil
import uuid
from pathlib import Path
from fastapi import APIRouter, UploadFile, File, Depends, HTTPException
from fastapi.responses import FileResponse

from app.core.config import settings
from app.core.security import verify_service_key
from app.core.hashing import compute_file_hashes
from app.parsers.tabular_parser import TabularParser
from app.parsers.pdf_parser import PDFParser
from app.analysis.anomaly import AnomalyDetector
from app.analysis.timeline import TimelineBuilder
from app.analysis.graph import GraphBuilder
from app.reports.generator import ReportGenerator
from app.models.schemas import (
    AnomalyRequest, TimelineRequest, GraphRequest, ReportRequest,
)

router = APIRouter(prefix="/api/v1", dependencies=[Depends(verify_service_key)])
public_router = APIRouter(prefix="/api/v1")

SUPPORTED_EXT = {".csv", ".json", ".xml", ".pdf"}


@router.post("/evidence/upload")
async def upload_evidence(file: UploadFile = File(...)):
    """
    Menerima file barang bukti dari Laravel, menghitung hash chain-of-custody,
    menyimpannya secara aman, lalu langsung mem-parsing isinya sesuai tipe file.
    """
    ext = Path(file.filename).suffix.lower()
    if ext not in SUPPORTED_EXT:
        raise HTTPException(400, f"Tipe file '{ext}' belum didukung. Format didukung: {SUPPORTED_EXT}")

    stored_name = f"{uuid.uuid4().hex}{ext}"
    dest = settings.UPLOAD_DIR / stored_name

    with dest.open("wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    if dest.stat().st_size > settings.MAX_UPLOAD_SIZE:
        dest.unlink(missing_ok=True)
        raise HTTPException(413, "Ukuran file melebihi batas maksimum.")

    hashes = compute_file_hashes(dest)

    try:
        if ext == ".csv":
            parsed = TabularParser.parse_csv(dest)
        elif ext == ".json":
            parsed = TabularParser.parse_json(dest)
        elif ext == ".xml":
            parsed = TabularParser.parse_xml(dest)
        elif ext == ".pdf":
            parsed = PDFParser.parse(dest)
        else:
            parsed = {}
    except Exception as e:
        raise HTTPException(422, f"Gagal mem-parsing file: {e}")

    parsed.pop("dataframe", None)  # tidak bisa diserialisasi JSON

    return {
        "filename": file.filename,
        "stored_as": stored_name,
        "file_type": ext.lstrip("."),
        "hashes": hashes,
        "parse_result": parsed,
    }


@router.post("/analysis/anomaly")
async def analyze_anomaly(payload: AnomalyRequest):
    detector = AnomalyDetector(contamination=payload.contamination)
    result = detector.analyze(payload.records, payload.numeric_fields)
    return result


@router.post("/analysis/timeline")
async def analyze_timeline(payload: TimelineRequest):
    builder = TimelineBuilder()
    result = builder.build(
        payload.records,
        timestamp_field=payload.timestamp_field,
        event_field=payload.event_field,
        entity_field=payload.entity_field,
    )
    return result


@router.post("/analysis/graph")
async def analyze_graph(payload: GraphRequest):
    builder = GraphBuilder()
    result = builder.build(
        payload.records,
        source_field=payload.source_field,
        target_field=payload.target_field,
        weight_field=payload.weight_field,
    )
    return result


@router.post("/reports/generate")
async def generate_report(payload: ReportRequest):
    generator = ReportGenerator()
    context = payload.model_dump(exclude={"format"})

    if payload.format == "html":
        html = generator.generate_html(context)
        return {"format": "html", "content": html}

    try:
        pdf_path = generator.generate_pdf(context)
    except Exception as e:
        raise HTTPException(500, f"Gagal membuat PDF (pastikan WeasyPrint terpasang dengan benar di server): {e}")

    return FileResponse(
        path=pdf_path,
        media_type="application/pdf",
        filename=pdf_path.name,
    )


@public_router.get("/health")
async def health():
    """Endpoint publik (tanpa API key) - dipakai Docker healthcheck / load balancer."""
    return {"status": "ok", "service": settings.APP_NAME, "version": settings.APP_VERSION}
