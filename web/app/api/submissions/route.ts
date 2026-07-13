// Proxy vers l'API Laravel pour le formulaire public de signalement.
const API = process.env.API_URL ?? "http://127.0.0.1:8000/api";

export async function POST(req: Request) {
  let body: unknown = {};
  try {
    body = await req.json();
  } catch {
    /* corps vide */
  }

  try {
    const r = await fetch(`${API}/submissions`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify(body),
      cache: "no-store",
    });
    const data = await r.json().catch(() => ({}));
    return Response.json(data, { status: r.status });
  } catch {
    return Response.json(
      { ok: false, message: "Service momentanément indisponible. Réessayez." },
      { status: 503 },
    );
  }
}
