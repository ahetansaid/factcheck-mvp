import { getVerifications } from "@/lib/api";

const SITE = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

export const dynamic = "force-dynamic";

function esc(s: string): string {
  return s.replace(/[<>&'"]/g, (c) =>
    ({ "<": "&lt;", ">": "&gt;", "&": "&amp;", "'": "&apos;", '"': "&quot;" }[c] as string),
  );
}

export async function GET() {
  const items = await getVerifications().catch(() => []);

  const entries = items
    .map((v) => {
      const link = `${SITE}/verifications/${v.slug}`;
      const date = v.published_at ? new Date(v.published_at).toUTCString() : "";
      return `    <item>
      <title>${esc(v.rating_label)} — ${esc(v.title)}</title>
      <link>${link}</link>
      <guid>${link}</guid>
      ${date ? `<pubDate>${date}</pubDate>` : ""}
      <description>${esc(v.summary)}</description>
    </item>`;
    })
    .join("\n");

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Vérifon — Vérifications</title>
    <link>${SITE}</link>
    <description>Fact-checking multilingue au Bénin (français, fon, yoruba).</description>
    <language>fr</language>
${entries}
  </channel>
</rss>`;

  return new Response(xml, {
    headers: { "Content-Type": "application/xml; charset=utf-8" },
  });
}
