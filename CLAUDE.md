# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Context

**DVE System** — ระบบจัดการอาชีวศึกษาระบบทวิภาคี (Dual Vocational Education Management System) for สำนักงานคณะกรรมการการอาชีวศึกษา (สอศ.).

Served via XAMPP at `http://vec.dve` (local vhost). No build step — pure client-side HTML/CSS/JS.

## Stack

- `index.html` / `DVE System.dc.html` — the app entry point, using Claude Design's `.dc.html` format
- `support.js` — Claude Design runtime that loads React 18 from unpkg CDN and renders the `<x-dc>` component
- All app logic lives in the `<script type="text/x-dc" data-dc-script>` block inside the HTML as a class extending `DCLogic`

## Architecture

Single-page application with all state in one `Component` class. Key patterns:

- `this.c()` — returns the full color palette (respects dark/light/system theme)
- `this.dk()` — returns `true` if dark mode is active
- `this.h(tag, props, ...children)` — alias for `React.createElement`
- `this.data()` — returns all mock data (KPIs, requests, enterprises, etc.)
- `renderVals()` — root render method; routes between public, login, and app shell
- Chart helpers: `this.barChart()`, `this.lineChart()`, `this.donut()`
- UI helpers: `this.btn()`, `this.bdg()`, `this.sbdg()` (status badge)

## Pages / Modules

| State `page` | Render method | Description |
|---|---|---|
| `public` | `renderPublic()` | Public stats dashboard (no login required) |
| `login` | `renderLogin()` | Login with 5 role types |
| `dashboard` | `renderDashboard()` | Main dashboard with KPI cards |
| `requests` | `renderRequests()` | Internship request management + `renderInternForm()` wizard |
| `ppp` | `renderPPP()` | PPP (Public-Private Partnership) industrial estate data |
| `supervision` | `renderSupervision()` | Student supervision scheduling and records |
| `enterprise` | `renderEnterprise()` | Enterprise list + deduplication UI |
| `finance` | `renderFinance()` | Budget allocation and transfer management |
| `reports` | `renderReports()` | Charts and export for reporting |

## Color System

Primary: `#7B1D2D` (dark red/maroon). Gold accent: `#C8973A`. All colors are theme-aware via `this.c()`.

## Running

Start XAMPP Apache, then open `http://vec.dve` or `http://localhost/vec.dve/`. Requires internet access for React CDN (unpkg.com).

Demo login: click "ทดลองใช้งานในฐานะ Admin →" on the login page.
