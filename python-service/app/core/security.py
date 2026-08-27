"""
Autentikasi service-to-service antara Laravel dan Python Forensic Service.
Laravel mengirim header: Authorization: Bearer <API_SECRET_KEY-signed-token>
Untuk kesederhanaan deployment, service ini memakai shared secret key (HMAC)
yang harus SAMA PERSIS dengan FORENSIC_SERVICE_KEY di .env Laravel.
"""
from fastapi import Header, HTTPException, status
from app.core.config import settings


def verify_service_key(x_api_key: str = Header(None, alias="X-API-Key")):
    """
    Dependency FastAPI untuk memvalidasi request yang datang dari backend Laravel.
    Setiap endpoint sensitif WAJIB memanggil dependency ini.
    """
    if not x_api_key or x_api_key != settings.API_SECRET_KEY:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or missing API key. Akses ditolak.",
        )
    return True
