import Link from "next/link";
import { getVerifications, getCategories, type ListItem } from "@/lib/api";

export const dynamic = "force-dynamic";
export const metadata = { title: "Recherche" };

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
      <span className="cat">{v.category ?? "Vérification"}{v.published_at ? ` · ${fmtDate(v.published_at)}` : ""}</span>
    </Link>
  );
}

export default async function SearchPage(
  { searchParams }: { searchParams: Promise<{ q?: string; category?: string }> },
) {
  const { q = "", category = "" } = await searchParams;
  const [items, categories] = await Promise.all([
    (q || category) ? getVerifications({ q, category }).catch(() => []) : Promise.resolve<ListItem[]>([]),
    getCategories().catch(() => []),
  ]);
  const active = q || category;

  return (
    <main>
      <section className="hero-h">
        <div className="wrap">
          <p className="eyebrow">Recherche</p>
          <h1>Chercher une vérification</h1>
          <form action="/recherche" method="get" className="search-bar">
            <input
              type="search"
              name="q"
              defaultValue={q}
              placeholder="Ex. : paludisme, élection, tisane…"
              aria-label="Rechercher une vérification"
              autoFocus
            />
            <button type="submit">Rechercher</button>
          </form>

          {categories.length > 0 && (
            <div className="chips" style={{ marginTop: "1.2rem" }}>
              <Link href="/recherche" className="chip" style={!category ? { borderColor: "var(--accent)", color: "var(--accent)" } : undefined}>Toutes</Link>
              {categories.map((c) => (
                <Link
                  key={c}
                  href={`/recherche?category=${encodeURIComponent(c)}`}
                  className="chip"
                  style={category === c ? { borderColor: "var(--accent)", color: "var(--accent)" } : undefined}
                >
                  {c}
                </Link>
              ))}
            </div>
          )}
        </div>
      </section>

      <section className="section">
        <div className="wrap">
          {!active && <p className="empty">Saisissez un mot-clé ou choisissez une catégorie.</p>}
          {active && (
            <>
              <p className="eyebrow" style={{ marginBottom: "1.2rem" }}>
                {items.length} résultat{items.length > 1 ? "s" : ""}
                {q ? ` pour « ${q} »` : ""}{category ? ` · ${category}` : ""}
              </p>
              {items.length === 0 ? (
                <p className="empty">Aucune vérification ne correspond. Vous pouvez <Link href="/signaler" style={{ color: "var(--link)" }}>la signaler</Link> à la rédaction.</p>
              ) : (
                <div className="grid">{items.map((v) => <Card key={v.slug} v={v} />)}</div>
              )}
            </>
          )}
        </div>
      </section>
    </main>
  );
}
