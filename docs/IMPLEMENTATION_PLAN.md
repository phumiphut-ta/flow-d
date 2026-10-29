# Flow-D: Master Implementation Plan & Architectural Decisions

## 1. Agreed Architectural Decisions
- **Frontend Stack (Option 1A):** Laravel Blade + Tailwind CSS + Alpine.js
  - High performance, lightweight, works reliably on Windows Server IIS with FastCGI.
  - Alpine.js handles keyboard shortcuts (`Alt+N`, `Enter`, `/`), high-density data grid interactions, immediate button disabling (`disabled = true`), and spinner loading state.
- **LINE LIFF Tracking (Option 2A):** Public read-only tracking endpoint (`/liff/tracking`).
  - Search by `doc_number` (e.g. `123/69`).
  - Multi-round tab switcher (Round 1 Completed, Round 2 Active).
  - Visual parcel-style timeline with checkpoints, Thai Buddhist Era timestamps (`d/m/Y+543 H:i`), and updater names.
- **Initial Authentication & Credentials:**
  - Admin: `username: admin`, `password: admin123`
  - Self-service password change menu for users.
  - Admin User Management password reset.
  - No email field required.
- **Initial Seeded Route:**
  - Route Name: `ลงเลขรับ-หอบ. ลงนาม-สารบรรณ-เสร็จสิ้น`
  - Steps: `["ลงเลขรับ", "หอบ. ลงนาม", "สารบรรณ", "เสร็จสิ้น"]`
  - Also support Point-to-Point routes (e.g. `ผบป.อบ.-หอบ.` where step is 1-click check-out).

---

## 2. Core Functional Specifications
1. **PC Desktop-First High Density Layout:**
   - Dual-Pane Layout:
     - Left (30%): Quick Intake Form (`doc_number`, `doc_date`, `route_id`). Instant submit on `Enter`.
     - Right (70%): Active Worklist with filters, sticky table header, and row actions.
   - Batch Action Toolbar: Bulk check-out and bulk advance step.
2. **Anti-Double Click & Server Idempotency:**
   - Frontend: Immediately disable button on click, set loading text/spinner, debounce form submits.
   - Backend: Database transaction with pessimistic lock (`lockForUpdate()`), verify `is_returned`, return idempotent status if already completed.
3. **Admin Recovery (Same-day Single Undo):**
   - Single step undo only.
   - Same-day constraint: `DATE(action_datetime) == today()`.
   - Admin authorization check (`role === 'admin'`).
   - Audit logging with mandatory reason prompt in `activity_logs`.
4. **Device Fingerprint Middleware:**
   - Persistent UUID cookie `flow_device_token`.
   - Auto-registers in `registered_devices` table, logs IP address and User Agent.
5. **Thai Buddhist Era (พ.ศ.):**
   - Carbon / Eloquent date accessors converting Christian year + 543.
6. **Dual Database & Windows Server IIS:**
   - SQLite for local development.
   - MariaDB support with fallback to `config/database.production.php`.
   - `public/web.config` configured for IIS URL Rewrite.

