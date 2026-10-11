/**
 * UniBridge · UI Utilities
 */

// ── Toast notifications ───────────────────────────────────────────────────
export function showToast(message, type = 'success', duration = 4000) {
  const existing = document.querySelectorAll('.toast-floating');
  existing.forEach(t => t.remove());

  const el = document.createElement('div');
  el.className = `toast toast-floating ${type}`;
  el.textContent = message;
  el.style.cssText = `
    position:fixed; top:20px; right:20px; z-index:9999;
    min-width:280px; max-width:400px;
    animation: slideIn .25s ease;
  `;
  document.body.appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, duration);
}

// ── Page loader ────────────────────────────────────────────────────────────
export function showLoader() {
  document.getElementById('page-loader')?.classList.remove('hidden');
}
export function hideLoader() {
  const l = document.getElementById('page-loader');
  if (l) { l.classList.add('hidden'); setTimeout(() => l.remove(), 300); }
}

// ── Require auth guard ─────────────────────────────────────────────────────
export async function requireAuth(apiClient) {
  const res = await apiClient.me();
  if (!res.success || !res.data?.loggedIn) {
    window.location.href = 'login.html';
    return null;
  }
  return res.data;
}

// ── Redirect if already logged in ─────────────────────────────────────────
export async function redirectIfLoggedIn(apiClient) {
  const res = await apiClient.me();
  if (res.success && res.data?.loggedIn) {
    window.location.href = 'dashboard.html';
  }
}

// ── Set loading state on button ────────────────────────────────────────────
export function setLoading(btn, loading) {
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner"></span> Please wait…';
    btn.disabled = true;
  } else {
    btn.innerHTML = btn.dataset.originalText || 'Submit';
    btn.disabled = false;
  }
}

// ── Fill form fields from object ───────────────────────────────────────────
export function fillForm(container, data) {
  Object.entries(data).forEach(([key, value]) => {
    const el = container.querySelector(`[name="${key}"]`);
    if (!el || value == null) return;
    if (el.tagName === 'SELECT') {
      [...el.options].forEach(o => { o.selected = o.value === String(value) || o.text === String(value); });
    } else if (el.tagName === 'TEXTAREA') {
      el.value = value;
    } else {
      el.value = value;
    }
  });
}

// ── Serialize form to plain object ─────────────────────────────────────────
export function formData(form) {
  const data = {};
  new FormData(form).forEach((val, key) => { data[key] = val; });
  return data;
}

// ── Format date ────────────────────────────────────────────────────────────
export function fmtDate(str) {
  if (!str) return '—';
  return new Date(str).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

// ── Sidebar toggle for mobile ──────────────────────────────────────────────
export function initSidebarToggle() {
  const toggle = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  if (!toggle || !sidebar) return;
  toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && !toggle.contains(e.target)) sidebar.classList.remove('open');
  });
}

// ── Readiness ring ─────────────────────────────────────────────────────────
export function setRingProgress(ringEl, percent) {
  if (!ringEl) return;
  ringEl.style.setProperty('--p', percent);
}

// ── Status pill HTML ───────────────────────────────────────────────────────
export function pillHTML(status) {
  const map = { Approved: 'approved', Pending: 'pending', Rejected: 'rejected', 'Not uploaded': '' };
  const cls = map[status] ?? '';
  return `<span class="pill ${cls}">${status ?? 'Not uploaded'}</span>`;
}
