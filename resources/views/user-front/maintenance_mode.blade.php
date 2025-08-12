<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>الموقع قيد الصيانة</title>

  <!-- Google Fonts (Cairo) -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;800&display=swap" rel="stylesheet" />

  <!-- Bootstrap 5.3.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />

  <style>
    :root{
      /* --bg1:#fff7e6; */
      /* --bg2:#ffeccd; */
      --card:#ffffff;
      --text:#1f2937;
      --muted:#6b7280;
      --primary:#ff8a3d;
      --ring:rgba(255,138,61,.25);
    }
    @media (prefers-color-scheme: dark){
      :root{
        /* --bg1:#0f1220; */
        --bg2:#161a2b;
        /* --card:#111528; */
        /* --text:#e5e7eb; */
        /* --muted:#9aa0aa; */
        --primary:#ffa666;
        --ring:rgba(255,166,102,.25);
      }
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family:"Cairo", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color:var(--text);
      background:
        radial-gradient(1200px 600px at 20% -10%, var(--bg2), transparent 60%),
        radial-gradient(1000px 500px at 120% 10%, var(--bg2), transparent 55%),
        linear-gradient(180deg, var(--bg1), var(--bg2));
      display:flex; align-items:center; justify-content:center;
      padding:24px;
    }

    .card-wrap{
      position:relative;
      max-width:720px;
      width:100%;
    }

    .glow{
      position:absolute; inset:-12px;
      background: radial-gradient(500px 180px at 50% -10%, var(--ring), transparent 60%);
      filter: blur(14px);
      z-index:0; pointer-events:none;
    }

    .maint-card{
      position:relative;
      z-index:1;
      background:var(--card);
      border:1px solid rgba(0,0,0,.05);
      border-radius:20px;
      padding:48px 28px;
      box-shadow:0 20px 50px rgba(0,0,0,.08);
    }

    .badge-soft{
      display:inline-flex; align-items:center; gap:.5rem;
      font-weight:600; font-size:.95rem;
      background:rgba(255,138,61,.12);
      color:var(--primary);
      padding:.5rem .9rem;
      border-radius:999px;
      border:1px dashed rgba(255,138,61,.35);
    }

    .icon-ring{
      display:inline-grid; place-items:center;
      width:82px; height:82px; border-radius:50%;
      background: linear-gradient(180deg, #fff, rgba(255,255,255,.7));
      border: 1px solid rgba(0,0,0,.06);
      margin: 12px auto 22px;
      box-shadow:
        0 8px 20px rgba(0,0,0,.06),
        inset 0 0 0 8px rgba(255,138,61,.08);
      position:relative;
      overflow:hidden;
    }
    .icon-ring::after{
      content:"";
      position:absolute; inset:0;
      background:conic-gradient(from 0deg, transparent 0 70%, rgba(255,138,61,.2) 70% 100%);
      animation:spin 4s linear infinite;
    }
    @keyframes spin{to{transform:rotate(360deg)}}

    h1{
      font-weight:800; letter-spacing:-.3px; margin-bottom:.5rem; line-height:1.2;
    }
    .lead{
      font-size:1.15rem; line-height:1.9; color:var(--muted);
    }

    .actions{gap:.75rem}
    .actions .btn{padding:.7rem 1.1rem; border-radius:12px; font-weight:700}

    .divider{
      height:1px; background:linear-gradient(90deg, transparent, rgba(0,0,0,.08), transparent);
      margin:24px 0;
    }

    .footer-text{font-size:.95rem; color:var(--muted)}
    .footer-text a{text-decoration:none; font-weight:600}

    /* Small wiggle for the wrench icon */
    .bi-wrench{position:relative; z-index:1; font-size:1.6rem; color:#ff8a3d; animation:wrench 1.8s ease-in-out infinite}
    @keyframes wrench{
      0%,100%{transform:rotate(0deg)}
      50%{transform:rotate(-12deg)}
    }
  </style>
</head>
<body>

  <main class="container">
    <div class="card-wrap mx-auto text-center">
      <span class="glow" aria-hidden="true"></span>

      <div class="maint-card">
        <span class="badge-soft mb-3">
          <i class="bi bi-calendar-check"></i>
          تحديثات مجدولة
        </span>

        <div class="icon-ring">
          <i class="bi bi-wrench"></i>
        </div>

        <h1>الموقع قيد الصيانة</h1>
        <p class="lead mb-4">
          نقوم حالياً بأعمال صيانة لتحسين الأداء وتجربة المستخدم. سنعود للعمل قريباً بإذن الله.
          نشكركم على صبركم وتفهّمكم.
        </p>

        <div class="d-flex justify-content-center flex-wrap actions mb-3">
        <a href="#" class="btn btn-dark" id="refresh-btn">
            <i class="bi bi-arrow-repeat me-1"></i> تحديث الصفحة
          </a>
          <!-- <a href="mailto:support@example.com" class="btn btn-outline-dark">
            <i class="bi bi-envelope-open me-1"></i> تواصل معنا
          </a> -->
        </div>

        <div class="divider"></div>

        <!-- <p class="footer-text mb-0">
          هل تحتاج للمساعدة العاجلة؟ راسلنا عبر البريد:
          <a href="mailto:support@example.com">support@example.com</a>
        </p> -->
      </div>
    </div>
  </main>

  <!-- Bootstrap 5.3.3 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.getElementById('refresh-btn').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent the default anchor behavior
    location.reload();  // Reload the page
  });
</script>
</body>
</html>
