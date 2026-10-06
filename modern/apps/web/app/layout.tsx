import "./globals.css";
import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "FALCONS Smart Theatre ERP",
  description: "Modern cinema booking and enterprise management platform"
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
