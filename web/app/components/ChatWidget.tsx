"use client";

import { useState, useRef, useEffect } from "react";

type Verif = {
  title: string; slug: string; rating: string; rating_label: string;
  summary: string; sources: { title: string | null; url: string }[];
};
type Msg = { role: "user" | "bot"; text: string; verif?: Verif };

const PRESETS = [
  "Une tisane guérit-elle le paludisme ?",
  "Peut-on voter par téléphone ?",
];

export default function ChatWidget() {
  const [open, setOpen] = useState(false);
  const [msgs, setMsgs] = useState<Msg[]>([
    { role: "bot", text: "Bonjour 👋 Posez-moi une question : « C'est vrai que… ? ». Je réponds uniquement à partir de nos vérifications publiées." },
  ]);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const logRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    logRef.current?.scrollTo({ top: logRef.current.scrollHeight, behavior: "smooth" });
  }, [msgs, loading, open]);

  async function ask(question: string) {
    const q = question.trim();
    if (!q || loading) return;
    setInput("");
    setMsgs((m) => [...m, { role: "user", text: q }]);
    setLoading(true);
    try {
      const r = await fetch("/api/ask", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ question: q }),
      });
      const d = await r.json();
      setMsgs((m) => [...m, { role: "bot", text: d.message, verif: d.matched ? d.verification : undefined }]);
    } catch {
      setMsgs((m) => [...m, { role: "bot", text: "Service indisponible, réessayez." }]);
    } finally {
      setLoading(false);
    }
  }

  return (
    <>
      <button className="chat-fab" onClick={() => setOpen((o) => !o)} aria-label="Assistant de vérification">
        {open ? "✕" : "Vérifier une info"}
      </button>

      {open && (
        <div className="chat-panel" role="dialog" aria-label="Assistant de vérification">
          <div className="chat-head">
            <span className="brand" style={{ fontSize: "1rem" }}><span className="mark" />Assistant Vérifon</span>
          </div>

          <div className="chat-log" ref={logRef}>
            {msgs.map((m, i) => (
              <div key={i} className={`c-bubble ${m.role}`}>
                {m.role === "bot" && m.verif && (
                  <span className={`verdict ${m.verif.rating}`} style={{ marginBottom: ".5rem" }}>{m.verif.rating_label}</span>
                )}
                <div>{m.text}</div>
                {m.verif && (
                  <div className="c-cite">
                    <a href={`/verifications/${m.verif.slug}`}>Lire la vérification →</a>
                    {m.verif.sources[0] && (
                      <> · <a href={m.verif.sources[0].url} target="_blank" rel="noopener noreferrer">
                        {m.verif.sources[0].title ?? "source"}
                      </a></>
                    )}
                  </div>
                )}
              </div>
            ))}
            {loading && <div className="c-bubble bot c-typing">…</div>}
          </div>

          <div className="chat-presets">
            {PRESETS.map((p) => (
              <button key={p} onClick={() => ask(p)}>{p}</button>
            ))}
          </div>

          <form
            className="chat-input"
            onSubmit={(e) => { e.preventDefault(); ask(input); }}
          >
            <input
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder="C'est vrai que… ?"
              aria-label="Votre question"
            />
            <button type="submit" disabled={loading || !input.trim()}>→</button>
          </form>
        </div>
      )}
    </>
  );
}
