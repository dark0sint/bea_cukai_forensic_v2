"""
Utility hashing untuk chain-of-custody barang bukti digital.
Setiap file yang diunggah dihitung hash-nya (SHA256 + MD5) sesaat setelah diterima,
supaya integritas barang bukti dapat diverifikasi kapan saja (standar forensik digital).
"""
import hashlib
from pathlib import Path


def compute_file_hashes(filepath: Path) -> dict[str, str]:
    sha256 = hashlib.sha256()
    md5 = hashlib.md5()

    with open(filepath, "rb") as f:
        for chunk in iter(lambda: f.read(8192), b""):
            sha256.update(chunk)
            md5.update(chunk)

    return {
        "sha256": sha256.hexdigest(),
        "md5": md5.hexdigest(),
        "size_bytes": filepath.stat().st_size,
    }
