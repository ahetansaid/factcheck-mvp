// Client de l'API Laravel (appelée côté serveur pour le SSR).
const API = process.env.API_URL ?? "http://127.0.0.1:8000/api";

export type ListItem = {
  title: string; slug: string; claim: string; rating: string;
  rating_label: string; summary: string; category: string | null;
  personality: string | null; published_at: string | null;
};

export type Verification = {
  title: string; slug: string; claim: string; rating: string;
  rating_label: string; rating_value: number; summary: string;
  body: string | null; category: string | null; published_at: string | null;
  personality: { name: string; slug: string; role: string | null } | null;
  sources: { title: string | null; url: string }[];
  claim_review: unknown;
};

export type PersonalitySummary = {
  name: string; slug: string; role: string | null;
  counts: Record<string, number>; total: number;
};

export type PersonalityDetail = PersonalitySummary & {
  bio: string | null;
  verifications: { title: string; slug: string; rating: string; rating_label: string; summary: string; published_at: string | null }[];
};

async function get<T>(path: string): Promise<T | null> {
  const res = await fetch(`${API}${path}`, { cache: "no-store" });
  if (res.status === 404) return null;
  if (!res.ok) throw new Error(`API ${path} → ${res.status}`);
  return res.json() as Promise<T>;
}

export async function getVerifications(
  params: { q?: string; category?: string } = {},
): Promise<ListItem[]> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);
  if (params.category) qs.set("category", params.category);
  const suffix = qs.toString() ? `?${qs}` : "";
  const r = await get<{ data: ListItem[] }>(`/verifications${suffix}`);
  return r?.data ?? [];
}

export async function getCategories(): Promise<string[]> {
  return (await get<string[]>("/categories")) ?? [];
}

export async function getVerification(slug: string): Promise<Verification | null> {
  const r = await get<{ data: Verification }>(`/verifications/${encodeURIComponent(slug)}`);
  return r?.data ?? null;
}

export async function getPersonalities(): Promise<PersonalitySummary[]> {
  return (await get<PersonalitySummary[]>("/personalities")) ?? [];
}

export async function getPersonality(slug: string): Promise<PersonalityDetail | null> {
  return await get<PersonalityDetail>(`/personalities/${encodeURIComponent(slug)}`);
}
