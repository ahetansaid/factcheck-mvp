import type { MetadataRoute } from "next";
import { getVerifications, getPersonalities } from "@/lib/api";

const SITE = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

export const dynamic = "force-dynamic";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [verifs, people] = await Promise.all([
    getVerifications().catch(() => []),
    getPersonalities().catch(() => []),
  ]);

  const staticUrls: MetadataRoute.Sitemap = [
    { url: `${SITE}/`, changeFrequency: "daily", priority: 1 },
    { url: `${SITE}/personnalites`, changeFrequency: "weekly", priority: 0.6 },
  ];

  const verifUrls: MetadataRoute.Sitemap = verifs.map((v) => ({
    url: `${SITE}/verifications/${v.slug}`,
    lastModified: v.published_at ?? undefined,
    changeFrequency: "monthly",
    priority: 0.8,
  }));

  const peopleUrls: MetadataRoute.Sitemap = people.map((p) => ({
    url: `${SITE}/personnalites/${p.slug}`,
    changeFrequency: "weekly",
    priority: 0.5,
  }));

  return [...staticUrls, ...verifUrls, ...peopleUrls];
}
