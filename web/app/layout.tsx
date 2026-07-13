import type { Metadata } from "next";
import Link from "next/link";
import "./globals.css";
import ChatWidget from "./components/ChatWidget";

export const metadata: Metadata = {
  title: {
    default: "Vérifon — fact-checking multilingue au Bénin",
    template: "%s · Vérifon",
  },
  description:
    "Vérifications de faits au Bénin, en français, fon et yoruba. Sources vérifiées, verdicts clairs, balisage lisible par les moteurs et les IA.",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="fr">
      <body>
        <header className="nav">
          <div className="wrap">
            <Link href="/" className="brand"><span className="mark" />Vérifon</Link>
            <Link href="/" className="link">Vérifications</Link>
            <Link href="/personnalites" className="link">Personnalités</Link>
          </div>
        </header>
        {children}
        <footer className="site-footer">
          <div className="wrap">
            <span className="brand"><span className="mark" />Vérifon</span>
            <p className="mono mut" style={{ fontSize: ".8rem", lineHeight: 1.8 }}>
              Fact-checking multilingue par la voix · fon · yoruba · français
            </p>
          </div>
        </footer>
        <ChatWidget />
      </body>
    </html>
  );
}
