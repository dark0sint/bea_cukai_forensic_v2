from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.core.config import settings
from app.api.routes import router, public_router

app = FastAPI(
    title=settings.APP_NAME,
    version=settings.APP_VERSION,
    description=(
        "Microservice analisis forensik digital untuk data kepabeanan (Bea Cukai). "
        "Menyediakan parsing dokumen, deteksi anomali transaksi, rekonstruksi timeline, "
        "analisis jaringan relasi entitas, dan pembuatan laporan otomatis. "
        "Dikonsumsi oleh aplikasi Laravel (case management & dashboard)."
    ),
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(public_router)
app.include_router(router)


@app.get("/")
async def root():
    return {
        "service": settings.APP_NAME,
        "version": settings.APP_VERSION,
        "docs": "/docs",
        "status": "running",
    }
