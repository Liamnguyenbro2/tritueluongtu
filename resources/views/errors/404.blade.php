<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 | Liên kết không tồn tại</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #060711;
            --panel: rgba(255,255,255,.08);
            --border: rgba(255,255,255,.12);
            --text: #f8fafc;
            --muted: #cbd5e1;
            --gold: #f8c84e;
            --violet: #8b5cf6;
            --rose: #fb7185;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 12%, rgba(139,92,246,.34), transparent 30%),
                radial-gradient(circle at 85% 10%, rgba(248,200,78,.16), transparent 24%),
                radial-gradient(circle at 80% 86%, rgba(251,113,133,.14), transparent 24%),
                linear-gradient(180deg, #090b19 0%, var(--bg) 100%);
        }

        .card {
            width: min(100%, 920px);
            display: grid;
            gap: 0;
            border-radius: 36px;
            border: 1px solid var(--border);
            background:
                radial-gradient(circle at top right, rgba(248,200,78,.14), transparent 26%),
                linear-gradient(145deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
            box-shadow: 0 30px 90px rgba(0,0,0,.38);
            overflow: hidden;
        }

        @media (min-width: 900px) {
            .card {
                grid-template-columns: .95fr 1.05fr;
            }
        }

        .visual {
            position: relative;
            min-height: 320px;
            padding: 36px;
            border-bottom: 1px solid var(--border);
            background:
                radial-gradient(circle at 30% 26%, rgba(139,92,246,.35), transparent 24%),
                radial-gradient(circle at 72% 72%, rgba(251,113,133,.16), transparent 22%),
                rgba(4,6,14,.5);
        }

        @media (min-width: 900px) {
            .visual {
                border-bottom: 0;
                border-right: 1px solid var(--border);
            }
        }

        .code {
            position: absolute;
            inset: 36px;
            display: grid;
            place-items: center;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,.08);
            background:
                radial-gradient(circle at 50% 50%, rgba(255,255,255,.08), transparent 60%),
                linear-gradient(160deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
            font-size: clamp(84px, 18vw, 180px);
            font-weight: 900;
            letter-spacing: -.08em;
            color: rgba(255,255,255,.92);
            text-shadow: 0 0 40px rgba(139,92,246,.2);
        }

        .content {
            padding: 40px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 22px;
            padding: 12px 16px;
            border-radius: 999px;
            border: 1px solid rgba(139,92,246,.22);
            background: rgba(139,92,246,.09);
            color: #ddd6fe;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .24em;
            text-transform: uppercase;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--violet), var(--rose));
            box-shadow: 0 0 18px rgba(139,92,246,.55);
        }

        h1 {
            margin: 0;
            font-size: clamp(36px, 7vw, 68px);
            line-height: .98;
            font-weight: 900;
            letter-spacing: -.04em;
        }

        .lead {
            margin: 24px 0 0;
            color: var(--muted);
            font-size: 19px;
            line-height: 1.8;
            max-width: 540px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 22px;
            border-radius: 18px;
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            transition: transform .18s ease, background .18s ease, border-color .18s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            border-color: rgba(248,200,78,.2);
            background: linear-gradient(135deg, rgba(139,92,246,.95), rgba(248,200,78,.95));
            color: #060711;
        }

        .btn-secondary {
            background: rgba(255,255,255,.05);
        }
    </style>
</head>
<body>
    <main class="card">
        <section class="visual">
            <div class="code">404</div>
        </section>
        <section class="content">
            <p class="eyebrow">
                <span class="dot"></span>
                Không tìm thấy
            </p>
            <h1>Liên kết không tồn tại</h1>
            <p class="lead">Liên kết không tồn tại vui lòng kiểm tra lại.</p>
            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/') }}">Quay về trang chủ</a>
                <a class="btn btn-secondary" href="javascript:history.back()">Quay lại trang trước</a>
            </div>
        </section>
    </main>
</body>
</html>
