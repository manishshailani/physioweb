# PhysioWeb — static preview (GitHub Pages)

This branch holds a **static snapshot** of PhysioWeb's public-facing pages, published to GitHub Pages so the team can review the look and content without setting up a server.

**This is a preview build, not the real app.** The real app is PHP 8.3+/MySQL 8+ with a database-backed admin CMS and patient portal — none of that can run on GitHub Pages (static hosting only, no PHP/MySQL). Specifically, on this preview:

- Booking, contact, login, and registration forms are disabled (they show a notice instead of submitting — there's no backend to receive them).
- Content (services, team, blog posts, testimonials) is a frozen snapshot from demo/seed data, not live data.
- The admin CMS and patient portal dashboards aren't included — only their login screens, to preview the UI.

For the real, fully-functional application, see the source on the `main` branch and [`docs/INSTALL.md`](https://github.com/manishshailani/physioweb/blob/main/docs/INSTALL.md) for how to run it with a real PHP + MySQL server.

This branch is regenerated from `main` by `database/demo_data/build_static_preview.js` — it is not meant to be edited by hand.
