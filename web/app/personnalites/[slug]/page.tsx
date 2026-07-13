import Link from "next/link";
import { notFound } from "next/navigation";
import type { Metadata } from "next";
import { getPersonality } from "@/lib/api";

export const dynamic = "force-dynamic";

const LABELS: Record<string, string> = {
  true: "Vrai", false: "Faux", misleading: "Trompeur", unproven: "Non vérifié",
};

export async function generateMetadata(
  { params }: { params: Promise<{ slug: string }> },
): Promise<Metadata> {
  const { slug } = await params;
  const p = await getPersonality(slug);
  return { title: p ? p.name : "Personnalité introuvable" };
}

export default async function PersonalityPage(
  { params }: { params: Promise<{ slug: string }> },
) {
  const { slug } = await params;
  const p = await getPersonality(slug);
  if (!p) notFound();

  return (
    <main className="section">
      <div className="wrap article">
        <Link href="/personnalites" className="back">← Annuaire des personnalités</Link>
        <h1>{p.name}</h1>
        {p.role && <p className="meta">{p.role}</p>}

        <div className="stats" style={{ marginTop: "1.1rem" }}>
          {Object.entries(p.counts).filter(([, n]) => n > 0).map(([r, n]) => (
            <span key={r} className={`stat ${r}`}>{LABELS[r] ?? r} <b>{n}</b></span>
          ))}
        </div>

        {p.bio && <p style={{ marginTop: "1.4rem", color: "var(--mut)" }}>{p.bio}</p>}

        <p className="eyebrow" style={{ margin: "2.6rem 0 1.2rem" }}>Ses vérifications</p>
        <div className="grid">
          {p.verifications.length === 0 && <p className="empty">Aucune vérification publiée.</p>}
          {p.verifications.map((v) => (
            <Link key={v.slug} href={`/verifications/${v.slug}`} className={`card v-${v.rating}`}>
              <span className={`verdict ${v.rating}`}>{v.rating_label}</span>
              <h3>{v.title}</h3>
              <p>{v.summary}</p>
            </Link>
          ))}
        </div>
      </div>
    </main>
  );
}
