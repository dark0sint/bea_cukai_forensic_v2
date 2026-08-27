"""
Konfigurasi global untuk Forensic Analysis Service.
Semua nilai bisa dioverride lewat environment variable / file .env
"""
from pydantic_settings import BaseSettings
from pathlib import Path


class Settings(BaseSettings):
    APP_NAME: str = "Bea Cukai Forensic Analysis Service"
    APP_VERSION: str = "2.0.0"
    ENV: str = "production"

    # Keamanan - API antara Laravel <-> Python harus pakai shared secret / JWT
    API_SECRET_KEY: str = "CHANGE_ME_SUPER_SECRET"
    ALGORITHM: str = "HS256"
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 60

    # Batas ukuran file upload (bytes) - default 50MB
    MAX_UPLOAD_SIZE: int = 50 * 1024 * 1024

    # Direktori penyimpanan
    BASE_DIR: Path = Path(__file__).resolve().parent.parent
    UPLOAD_DIR: Path = BASE_DIR / "storage" / "uploads"
    REPORT_DIR: Path = BASE_DIR / "storage" / "reports"

    # CORS - domain Laravel yang diizinkan mengakses service ini
    ALLOWED_ORIGINS: list[str] = ["*"]

    class Config:
        env_file = ".env"
        extra = "ignore"


settings = Settings()
settings.UPLOAD_DIR.mkdir(parents=True, exist_ok=True)
settings.REPORT_DIR.mkdir(parents=True, exist_ok=True)
