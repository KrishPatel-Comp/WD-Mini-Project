/**
 * UniBridge · API Client
 * Relative path resolves correctly regardless of whether served from root (localhost:8000)
 * or from a subdirectory (localhost/WD project/)
 */

const BASE = (function() {
  const path = window.location.pathname;
  if (path.includes('/WD project/') || path.includes('/WD%20project/')) {
    return '/WD%20project/backend/api';
  }
  if (path.includes('/frontend/pages/')) {
    return '../../backend/api';
  }
  return '/backend/api';
})();

async function apiFetch(url, options = {}) {
  try {
    const res = await fetch(url, {
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
    });
    const json = await res.json();
    return json;
  } catch (err) {
    console.warn('API Fetch error:', err);
    return { success: false, message: 'Could not connect to the server. Please try again.', data: null };
  }
}

export const api = {
  // ── Auth ──────────────────────────────────────
  me:     ()      => apiFetch(`${BASE}/auth.php?action=me`),
  login:  (body)  => apiFetch(`${BASE}/auth.php?action=login`,  { method:'POST', body: JSON.stringify(body) }),
  signup: (body)  => apiFetch(`${BASE}/auth.php?action=signup`, { method:'POST', body: JSON.stringify(body) }),
  logout: ()      => apiFetch(`${BASE}/auth.php?action=logout`, { method:'POST' }),
  forgot: (body)  => apiFetch(`${BASE}/auth.php?action=forgot`, { method:'POST', body: JSON.stringify(body) }),
  reset:  (body)  => apiFetch(`${BASE}/auth.php?action=reset`,  { method:'POST', body: JSON.stringify(body) }),

  // ── Student ───────────────────────────────────
  dashboard:    ()     => apiFetch(`${BASE}/student.php?action=dashboard`),
  profile:      ()     => apiFetch(`${BASE}/student.php?action=profile`),
  saveProfile:  (body) => apiFetch(`${BASE}/student.php?action=profile`,      { method:'POST', body: JSON.stringify(body) }),
  registration: ()     => apiFetch(`${BASE}/student.php?action=registration`),
  saveReg:      (body) => apiFetch(`${BASE}/student.php?action=registration`,  { method:'POST', body: JSON.stringify(body) }),

  // ── Documents ─────────────────────────────────
  documents: () => apiFetch(`${BASE}/documents.php?action=list`),
  upload: async (formData) => {
    try {
      const res = await fetch(`${BASE}/documents.php?action=upload`, {
        credentials: 'include',
        method: 'POST',
        body: formData,
      });
      return await res.json();
    } catch (err) {
      console.warn('Upload error:', err);
      return { success: false, message: 'Upload failed. Please check network connection.', data: null };
    }
  },

  // ── Application Tracking ───────────────────────
  application:     ()     => apiFetch(`${BASE}/application.php?action=get`),
  updateAppStatus: (body) => apiFetch(`${BASE}/application.php?action=update_status`, { method:'POST', body: JSON.stringify(body) }),

  // ── Fee Payments ───────────────────────────────
  payments: ()       => apiFetch(`${BASE}/payments.php?action=list`),
  payFee:   (body)   => apiFetch(`${BASE}/payments.php?action=pay`, { method:'POST', body: JSON.stringify(body) }),
  receipt:  (ref)    => apiFetch(`${BASE}/payments.php?action=receipt&ref=${encodeURIComponent(ref)}`),

  // ── Notifications ──────────────────────────────
  notifications:        ()     => apiFetch(`${BASE}/notifications.php?action=list`),
  markNotificationRead: (body) => apiFetch(`${BASE}/notifications.php?action=mark_read`, { method:'POST', body: JSON.stringify(body) }),
};
