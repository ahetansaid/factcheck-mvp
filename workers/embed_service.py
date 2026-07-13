"""Worker d'embeddings — précurseur du service IA (FastAPI).

Sert le modèle multilingue e5-small pour vectoriser textes et requêtes.
Appelé par Laravel pour la recherche sémantique (L2 v3). Tourne sur CPU.

Lancer :
    cd workers
    ../poc-l0/.venv/Scripts/python.exe -m uvicorn embed_service:app --port 8100

Les vecteurs sont normalisés (norme 1) : la similarité cosinus = produit scalaire.
"""
from fastapi import FastAPI
from pydantic import BaseModel
import torch
from transformers import AutoModel, AutoTokenizer

MODEL = "intfloat/multilingual-e5-small"

app = FastAPI(title="Vérifon — worker embeddings")

print(f"chargement {MODEL}...", flush=True)
_tok = AutoTokenizer.from_pretrained(MODEL)
_model = AutoModel.from_pretrained(MODEL)
_model.eval()
print("modèle prêt.", flush=True)


def _mean_pool(last_hidden, mask):
    m = mask.unsqueeze(-1).float()
    return (last_hidden * m).sum(1) / m.sum(1).clamp(min=1e-9)


class EmbedRequest(BaseModel):
    texts: list[str]
    kind: str = "query"  # "query" (requête) ou "passage" (document)


@app.get("/health")
def health():
    return {"ok": True, "model": MODEL}


@app.post("/embed")
def embed(req: EmbedRequest):
    # e5 attend un préfixe distinct pour requêtes et documents.
    prefix = "query: " if req.kind == "query" else "passage: "
    inputs = [prefix + (t or "") for t in req.texts]

    enc = _tok(inputs, padding=True, truncation=True, max_length=256, return_tensors="pt")
    with torch.no_grad():
        out = _model(**enc)
    emb = _mean_pool(out.last_hidden_state, enc["attention_mask"])
    emb = torch.nn.functional.normalize(emb, p=2, dim=1)

    return {"vectors": emb.tolist(), "dim": emb.shape[1]}
