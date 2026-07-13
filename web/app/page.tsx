import Link from "next/link";
import { getVerifications, type ListItem } from "@/lib/api";

export const dynamic = "force-dynamic";

function fmtDate(iso: string | null): string {
  if (!iso) return "";
  return new Date(iso).toLocaleDateString("fr-FR", { day: "numeric", month: "long", year: "numeric" });
}

function Card({ v }: { v: ListItem }) {
  return (
    <Link href={`/verifications/${v.slug}`} className={`card v-${v.rating}`}>
      <span className={`verdict ${v.rating}`}>{v.rating_label}</span>
      <h3>{v.title}</h3>
      <p>{v.summary}</p>
      <span className="cat">
        {v.category ?? "Vérification"}{v.published_at ? ` · ${fmtDate(v.published_at)}` : ""}
      </span>
    </Link>
  );
}

export default async function Home() {
  let items: ListItem[] = [];
  let error = false;
  try {
    items = await getVerifications();
  } catch {
    error = true;
  }

  const [featured, ...rest] = items;

  return (
    <main>
      <section className="hero-h">
        <div className="wrap">
          <p className="eyebrow">Fact-checking · Bénin</p>
          <h1>Vérifier l&apos;information, dans <em>les langues du Bénin</em>.</h1>
          <p className="lede">
            Nous vérifions les affirmations qui circulent — en français, en fon et en yoruba —
            avec des sources et un verdict clair. Chaque vérification est publiée pour être citée.
          </p>
        </div>
      </section>

      <section className="section">
        <div className="wrap">
          <div className="section-head">
            <p className="eyebrow">Dernières vérifications</p>
            <Link href="/personnalites" className="mono mut" style={{ fontSize: ".85rem" }}>
              Voir par personnalité →
            </Link>
          </div>

          {error && <p className="empty">Le service de vérifications est momentanément indisponible. Réessayez dans un instant.</p>}
          {!error && items.length === 0 && <p className="empty">Aucune vérification publiée pour le moment.</p>}

          {featured && (
            <Link href={`/verifications/${featured.slug}`} className="featured">
              <div className="body">
                <span className={`verdict ${featured.rating}`}>{featured.rating_label}</span>
                <h2>{featured.title}</h2>
                <p className="mut">{featured.summary}</p>
                <span className="cat mono" style={{ fontSize: ".72rem", color: "var(--faint)" }}>
                  {featured.category ?? "Vérification"}{featured.published_at ? ` · ${fmtDate(featured.published_at)}` : ""}
                </span>
              </div>
              <div className="side">
                <p className="eyebrow" style={{ fontSize: ".68rem" }}>L&apos;affirmation</p>
                <p className="claim">« {featured.claim} »</p>
              </div>
            </Link>
          )}

          {rest.length > 0 && (
            <div className="grid">
              {rest.map((v) => <Card key={v.slug} v={v} />)}
            </div>
          )}
        </div>
      </section>
    </main>
  );
}
