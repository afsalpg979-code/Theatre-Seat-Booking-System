const modules = [
  ["🎬", "Movies & Shows", "Manage movies, screens, seats and schedules."],
  ["🎟️", "Bookings", "Live seat availability, reservations and e-tickets."],
  ["🍿", "POS & Inventory", "Food sales, stock, suppliers and procurement."],
  ["👥", "People", "Employees, roles, shifts and attendance."],
  ["💰", "Finance", "Revenue, expenses, payments and reconciliation."],
  ["🤖", "AI Insights", "Occupancy, revenue and inventory predictions."]
];

export default function Home() {
  return (
    <main className="shell">
      <section className="hero">
        <div>
          <span className="badge">FALCONS • SMART THEATRE ERP</span>
          <h1>One platform for the entire theatre.</h1>
          <p>Modern booking, operations, finance and AI intelligence — built with React, TypeScript and Node.js.</p>
          <div className="actions">
            <a href="#modules" className="primary">Explore ERP</a>
            <a href="http://localhost:4000/api/health" className="secondary">API Health</a>
          </div>
        </div>
        <div className="status-card">
          <span>System foundation</span>
          <strong>READY</strong>
          <small>Web + API + Database architecture</small>
        </div>
      </section>

      <section id="modules" className="grid">
        {modules.map(([icon, title, description]) => (
          <article className="card" key={title}>
            <div className="icon">{icon}</div>
            <h2>{title}</h2>
            <p>{description}</p>
          </article>
        ))}
      </section>
    </main>
  );
}
