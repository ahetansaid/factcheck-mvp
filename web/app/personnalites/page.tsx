import Link from "next/link";
import { getPersonalities } from "@/lib/api";

export const dynamic = "force-dynamic";
export const metadata = { title: "Personnalités" };

const LABELS: Record<string, string> = {
  true: "Vrai", false: "Faux", misleading: "Trompeur", unproven: "Non vérifié",
};

export default async function PersonalitiesPage() {
  let people = [] as Awaited<ReturnType<typeof getPersonalities>>;
  try {
    people = await getPersonalities();
  } catch {
    /* service indisponible */
  }

  return (
    <main>
      <section className="hero-h">
        <div className="wrap">
          <p className="eyebrow">Annuaire</p>
          <h1>Personnalités &amp; sources de rumeurs</h1>
          <p className="lede">Le bilan des vérifications par acteur : combien de leurs affirmations se sont révélées vraies, fausses ou trompeuses.</p>
        </div>
      </section>

      <section className="section">
        <div className="wrap">
          <div className="grid">
            {people.length === 0 && <p className="empty">Aucune personnalité pour le moment.</p>}
            {people.map((p) => (
              <Link key={p.slug} href={`/personnalites/${p.slug}`} className="card">
                <h3>{p.name}</h3>
                {p.role && <p>{p.role}</p>}
                <div className="stats">
                  {Object.entries(p.counts).filter(([, n]) => n > 0).map(([r, n]) => (
                    <span key={r} className={`stat ${r}`}>{LABELS[r] ?? r} <b>{n}</b></span>
                  ))}
                  {p.total === 0 && <span className="stat">aucune vérif publiée</span>}
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>
    </main>
  );
}
