import Link from "next/link";
import { notFound } from "next/navigation";
import type { Metadata } from "next";
import { getVerification } from "@/lib/api";

export const dynamic = "force-dynamic";

function fmtDate(iso: string | null): string {
  if (!iso) return "";
  return new Date(iso).toLocaleDateString("fr-FR", { day: "numeric", month: "long", year: "numeric" });
}

export async function generateMetadata(
  { params }: { params: Promise<{ slug: string }> },
): Promise<Metadata> {
  const { slug } = await params;
  const v = await getVerification(slug);
  if (!v) return { title: "Vérification introuvable" };
  return { title: v.title, description: v.summary };
}

export default async function VerificationPage(
  { params }: { params: Promise<{ slug: string }> },
) {
  const { slug } = await params;
  const v = await getVerification(slug);
  if (!v) notFound();

  return (
    <main className="section">
      <div className="wrap">
        <article className="article">
          <Link href="/" className="back">← Toutes les vérifications</Link>

          <span className={`verdict ${v.rating}`}>{v.rating_label}</span>
          <h1>{v.title}</h1>
          <p className="meta">
            {v.category ?? "Vérification"}{v.published_at ? ` · ${fmtDate(v.published_at)}` : ""}
            {v.personality ? (
              <> · <Link href={`/personnalites/${v.personality.slug}`} style={{ color: "var(--link)" }}>{v.personality.name}</Link></>
            ) : null}
          </p>

          <p className="claim">« {v.claim} »</p>
          <p className="summary">{v.summary}</p>

          {v.body && (
            <div className="body">
              {v.body.split(/\n{2,}|\n/).filter(Boolean).map((para, i) => <p key={i}>{para}</p>)}
            </div>
          )}

          {v.sources.length > 0 && (
            <div className="sources">
              <p className="eyebrow">Sources</p>
              <ul>
                {v.sources.map((s, i) => (
                  <li key={i}>
                    <a href={s.url} target="_blank" rel="noopener noreferrer">{s.title ?? s.url}</a>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </article>
      </div>

      {/* Balisage AI-native : ClaimReview + Article, lisible par Google, Perplexity, ChatGPT, Claude. */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(v.claim_review) }}
      />
    </main>
  );
}
