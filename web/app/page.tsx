import Link from "next/link";
import { getVerifications } from "@/lib/api";

export const dynamic = "force-dynamic";

function fmtDate(iso: string | null): string {
  if (!iso) return "";
  return new Date(iso).toLocaleDateString("fr-FR", { day: "numeric", month: "long", year: "numeric" });
}

export default async function Home() {
  let items = [] as Awaited<ReturnType<typeof getVerifications>>;
  let error = false;
  try {
    items = await getVerifications();
  } catch {
    error = true;
  }

  return (
    <main>
      <section className="hero-h">
        <div className="wrap">
          <p className="eyebrow">Fact-checking · Bénin</p>
          <h1>Vérifier l&apos;information, dans les langues du Bénin.</h1>
          <p className="lede">
            Nous vérifions les affirmations qui circulent — en français, en fon et en yoruba —
            avec des sources et un verdict clair. Chaque vérification est publiée pour être citée.
          </p>
        </div>
      </section>

      <section className="section">
        <div className="wrap">
          <p className="eyebrow" style={{ marginBottom: "1.2rem" }}>Dernières vérifications</p>

          {error && (
            <p className="mut">Le service de vérifications est momentanément indisponible. Réessayez dans un instant.</p>
          )}
          {!error && items.length === 0 && (
            <p className="mut">Aucune vérification publiée pour le moment.</p>
          )}

          <div className="grid">
            {items.map((v) => (
              <Link key={v.slug} href={`/verifications/${v.slug}`} className="card">
                <span className={`verdict ${v.rating}`}>{v.rating_label}</span>
                <h3>{v.title}</h3>
                <p>{v.summary}</p>
                <span className="cat">
                  {v.category ?? "Vérification"}{v.published_at ? ` · ${fmtDate(v.published_at)}` : ""}
                </span>
              </Link>
            ))}
          </div>
        </div>
      </section>
    </main>
  );
}
