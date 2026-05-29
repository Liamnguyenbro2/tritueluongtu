<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bảo trì hệ thống</title>
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
            --emerald: #34d399;
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
                radial-gradient(circle at 18% 10%, rgba(139,92,246,.34), transparent 28%),
                radial-gradient(circle at 82% 12%, rgba(248,200,78,.18), transparent 24%),
                radial-gradient(circle at 50% 100%, rgba(52,211,153,.12), transparent 36%),
                linear-gradient(180deg, #090b19 0%, var(--bg) 100%);
        }

        .frame {
            width: min(100%, 980px);
            border-radius: 36px;
            border: 1px solid var(--border);
            background:
                radial-gradient(circle at top right, rgba(248,200,78,.16), transparent 28%),
                linear-gradient(145deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
            box-shadow: 0 32px 90px rgba(0,0,0,.38);
            overflow: hidden;
        }

        .grid {
            display: grid;
            gap: 0;
        }

        @media (min-width: 900px) {
            .grid {
                grid-template-columns: 1.1fr .9fr;
            }
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
            border: 1px solid rgba(248,200,78,.22);
            background: rgba(248,200,78,.08);
            color: #fde68a;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .24em;
            text-transform: uppercase;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--gold), var(--violet));
            box-shadow: 0 0 18px rgba(248,200,78,.6);
        }

        h1 {
            margin: 0;
            font-size: clamp(40px, 7vw, 74px);
            line-height: .95;
            font-weight: 900;
            letter-spacing: -.04em;
        }

        p.lead {
            margin: 24px 0 0;
            max-width: 520px;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.75;
        }

        .status {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 28px;
        }

        .badge {
            padding: 12px 16px;
            border-radius: 18px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,.05);
            color: #e2e8f0;
            font-size: 13px;
            font-weight: 700;
        }

        .aside {
            position: relative;
            min-height: 280px;
            padding: 40px;
            border-top: 1px solid var(--border);
            background:
                radial-gradient(circle at 18% 18%, rgba(139,92,246,.35), transparent 32%),
                radial-gradient(circle at 78% 78%, rgba(52,211,153,.18), transparent 24%),
                rgba(4,6,14,.55);
        }

        @media (min-width: 900px) {
            .aside {
                border-top: 0;
                border-left: 1px solid var(--border);
            }
        }

        .orb {
            position: absolute;
            inset: 28px;
            border-radius: 32px;
            border: 1px solid rgba(255,255,255,.08);
            background:
                radial-gradient(circle at 50% 50%, rgba(255,255,255,.12), transparent 52%),
                linear-gradient(160deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
            overflow: hidden;
        }

        .orb::before,
        .orb::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(10px);
        }

        .orb::before {
            width: 180px;
            height: 180px;
            top: 32px;
            right: 18px;
            background: rgba(248,200,78,.28);
        }

        .orb::after {
            width: 220px;
            height: 220px;
            bottom: 18px;
            left: 10px;
            background: rgba(139,92,246,.26);
        }

        .aside-copy {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 100%;
        }

        .aside-copy h2 {
            margin: 0 0 10px;
            font-size: 16px;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: #c4b5fd;
        }

        .aside-copy p {
            margin: 0;
            color: #e2e8f0;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <main class="frame">
        <div class="grid">
            <section class="content">
                <p class="eyebrow">
                    <span class="dot"></span>
                    Bảo trì định kỳ
                </p>
                <h1>Website đang trong quá trình bảo trì</h1>
                <p class="lead">Thông báo: Website đang trong quá trình bảo trì, nâng cấp định kỳ vui lòng quay lại sau.</p>
                <div class="status">
                    <span class="badge">Hệ thống đang tạm dừng phục vụ</span>
                    <span class="badge">Dữ liệu vẫn được bảo toàn</span>
                    <span class="badge">Sẽ hoạt động lại sau khi nâng cấp xong</span>
                </div>
            </section>
            <aside class="aside">
                <div class="orb"></div>
                <div class="aside-copy">
                    <h2>Maintenance Mode</h2>
                    <p>Chúng tôi đang cập nhật hệ thống để tối ưu hiệu năng, tăng độ ổn định và cải thiện trải nghiệm sử dụng.</p>
                </div>
            </aside>
        </div>
    </main>
</body>
</html>
