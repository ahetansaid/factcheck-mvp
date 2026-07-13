"use client";

import { useState } from "react";

export default function SignalerPage() {
  const [content, setContent] = useState("");
  const [contact, setContact] = useState("");
  const [state, setState] = useState<"idle" | "sending" | "done" | "error">("idle");
  const [message, setMessage] = useState("");

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (content.trim().length < 5 || state === "sending") return;
    setState("sending");
    try {
      const r = await fetch("/api/submissions", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ content, contact }),
      });
      const d = await r.json();
      if (r.ok && d.ok) {
        setState("done");
        setMessage(d.message ?? "Merci, votre signalement a été transmis.");
      } else {
        setState("error");
        setMessage(d.message ?? "Le signalement n'a pas pu être envoyé.");
      }
    } catch {
      setState("error");
      setMessage("Service momentanément indisponible.");
    }
  }

  return (
    <main className="section">
      <div className="wrap article">
        <p className="eyebrow">Participer</p>
        <h1>Signaler une rumeur</h1>
        <p className="lede" style={{ marginTop: "1rem" }}>
          Une information vous semble douteuse ? Transmettez-la. Notre rédaction l&apos;examine
          et publie une vérification si elle est vérifiable.
        </p>

        {state === "done" ? (
          <div className="card" style={{ marginTop: "2rem", cursor: "default" }}>
            <span className="verdict true">Reçu</span>
            <p style={{ color: "var(--ink)" }}>{message}</p>
          </div>
        ) : (
          <form onSubmit={submit} className="signal-form" style={{ marginTop: "2rem", display: "flex", flexDirection: "column", gap: "1rem", maxWidth: "38rem" }}>
            <label style={{ display: "flex", flexDirection: "column", gap: ".4rem" }}>
              <span className="eyebrow" style={{ fontSize: ".68rem" }}>L&apos;affirmation à vérifier</span>
              <textarea
                value={content}
                onChange={(e) => setContent(e.target.value)}
                rows={4}
                required
                minLength={5}
                placeholder="Ex. : « On dit que… ». Copiez le message ou décrivez la rumeur."
                style={taStyle}
              />
            </label>
            <label style={{ display: "flex", flexDirection: "column", gap: ".4rem" }}>
              <span className="eyebrow" style={{ fontSize: ".68rem" }}>Votre contact (facultatif)</span>
              <input
                value={contact}
                onChange={(e) => setContact(e.target.value)}
                placeholder="email ou téléphone, si vous voulez un retour"
                style={inStyle}
              />
            </label>
            {state === "error" && <p style={{ color: "var(--false)" }}>{message}</p>}
            <button type="submit" className="chat-fab" style={{ position: "static", alignSelf: "flex-start" }} disabled={state === "sending"}>
              {state === "sending" ? "Envoi…" : "Transmettre à la rédaction"}
            </button>
          </form>
        )}
      </div>
    </main>
  );
}

const taStyle: React.CSSProperties = {
  fontFamily: "inherit", fontSize: "1rem", padding: ".7rem .9rem",
  border: "1px solid var(--line)", borderRadius: "12px", background: "var(--panel)", color: "var(--ink)", resize: "vertical",
};
const inStyle: React.CSSProperties = { ...taStyle, borderRadius: "99px" };
