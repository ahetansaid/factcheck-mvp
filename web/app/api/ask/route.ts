// Proxy vers l'API Laravel : le widget appelle /api/ask (même origine),
// on relaie côté serveur pour éviter tout souci CORS.
const API = process.env.API_URL ?? "http://127.0.0.1:8000/api";

export async function POST(req: Request) {
  let body: unknown = {};
  try {
    body = await req.json();
  } catch {
    /* corps vide */
  }

  try {
    const r = await fetch(`${API}/ask`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
      cache: "no-store",
    });
    const data = await r.json();
    return Response.json(data);
  } catch {
    return Response.json(
      { matched: false, message: "Le service est momentanément indisponible. Réessayez dans un instant." },
      { status: 200 },
    );
  }
}
