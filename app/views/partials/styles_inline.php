:root {
  --bg: #18191a;
  --bg-2: #242526;
  --surface: #242526;
  --surface2: #3a3b3c;
  --surface3: #4e4f50;
  --glass: rgba(255, 255, 255, 0.05);
  --glass-strong: rgba(255, 255, 255, 0.1);
  --border: #3e4042;
  --border-strong: #4e4f50;
  --accent: #2374e1;
  --accent2: #42b72a;
  --accent3: #e41e3f;
  --text: #e4e6eb;
  --text-muted: #b0b3b8;
  --text-dim: #8a8d91;
  --radius: 8px;
  --radius-sm: 6px;
  --radius-pill: 20px;
  --shadow: 0 1px 2px rgba(0,0,0,0.2);
  --glow: 0 0 0 2px var(--accent);
  --font-display: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  --font-body: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font-body);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  font-size: 14px;
  line-height: 1.34;
  position: relative;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}
body::before,
body::after {
  display: none;
}
.auth-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(420px, .9fr);
  position: relative;
  z-index: 1;
}
.auth-left,
.auth-right {
  position: relative;
  padding: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.auth-left {
  overflow: hidden;
  background: var(--surface);
}
.auth-left::before,
.auth-left::after,
.auth-right::before {
  display: none;
}
.auth-left-content,
.auth-form-box {
  position: relative;
  z-index: 1;
}
.auth-left-content {
  width: min(100%, 560px);
  padding: 42px;
  border-radius: var(--radius);
  background: var(--surface2);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}
.auth-logo-big {
  font-family: var(--font-display);
  font-size: clamp(46px, 7vw, 72px);
  font-weight: 800;
  letter-spacing: -0.06em;
  color: var(--text);
  margin-bottom: 16px;
}
.auth-logo-big span {
  color: var(--accent);
}
.auth-tagline {
  font-size: 19px;
  color: var(--text-muted);
  max-width: 420px;
}
.auth-features {
  margin-top: 38px;
  display: grid;
  gap: 14px;
}
.auth-feature {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 18px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
}
.auth-feature-icon {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: var(--surface2);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent);
  font-size: 17px;
}
.auth-feature-text { font-size: 15px; color: var(--text-muted); }
.auth-feature-text strong { color: var(--text); font-weight: 700; }
.auth-form-box {
  width: min(100%, 430px);
  padding: 22px 4px;
}
.auth-form-title {
  font-family: var(--font-display);
  font-size: 34px;
  font-weight: 800;
  letter-spacing: -0.04em;
  margin-bottom: 8px;
}
.auth-form-subtitle {
  color: var(--text-muted);
  font-size: 15px;
  margin-bottom: 26px;
}
.form-group { margin-bottom: 16px; }
.form-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: rgba(232,239,255,0.76);
  margin-bottom: 7px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}
.form-control {
  width: 100%;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  padding: 13px 16px;
  font-family: var(--font-body);
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}
.form-control:focus {
  border-color: var(--accent);
  box-shadow: none;
}
.form-control::placeholder { color: var(--text-dim); }
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 20px;
  border-radius: var(--radius-pill);
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 700;
  border: 1px solid transparent;
  cursor: pointer;
  text-decoration: none;
  transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease;
  white-space: nowrap;
}
.btn:hover { transform: translateY(-1px); }
.btn-primary {
  background: var(--accent);
  color: #fff;
  box-shadow: none;
}
.btn-primary:hover { box-shadow: none; background: #1a6acd; }
.btn-ghost {
  background: rgba(255,255,255,0.05);
  color: var(--text-muted);
  border-color: rgba(255,255,255,0.1);
  backdrop-filter: blur(16px);
}
.btn-ghost:hover { color: var(--text); background: rgba(255,255,255,0.09); }
.alert {
  padding: 12px 16px;
  border-radius: 18px;
  margin-bottom: 16px;
  font-size: 14px;
  font-weight: 500;
  backdrop-filter: blur(18px);
}
.alert-error {
  background: rgba(255,107,107,0.12);
  border: 1px solid rgba(255,107,107,0.26);
  color: #ffc2c2;
}
.auth-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 22px 0 18px;
}
.auth-divider::before, .auth-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.14), transparent);
}
.auth-divider span { color: var(--text-dim); font-size: 12px; }
.auth-link {
  color: #a9dfff;
  text-decoration: none;
  font-weight: 700;
}
.auth-link:hover { color: #fff; }
.site-footer{
  text-align:center;
  padding:20px 12px;
  border-top:1px solid rgba(255,255,255,0.09);
  font-size:13px;
  color:var(--text-muted);
  background:rgba(6,10,18,0.55);
  backdrop-filter:blur(18px);
  margin-top:0;
  position:relative;
  z-index:1;
}
.footer-links{ margin-bottom:6px; }
.footer-links a{
  color:var(--text-muted);
  text-decoration:none;
  margin:0 8px;
  font-weight:500;
}
.footer-links a:hover{ color:#cde9ff; }
@media (max-width: 900px) {
  .auth-page { grid-template-columns: 1fr; }
  .auth-left { min-height: 42vh; padding-bottom: 0; }
  .auth-right { padding-top: 0; }
  .auth-right::before { inset: 20px 18px 28px; }
}
@media (max-width: 640px) {
  .auth-left, .auth-right { padding: 18px; }
  .auth-left-content { padding: 26px; border-radius: 28px; }
  .auth-form-box { width: 100%; padding: 22px 10px; }
  .auth-logo-big { font-size: 46px; }
  .auth-form-title { font-size: 28px; }
}
