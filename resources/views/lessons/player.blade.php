<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $lesson->title }}</title>
    <style>
        html, body {
            margin: 0;
            height: 100%;
            background: #050816;
        }

        body {
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .player-shell {
            height: 100%;
            width: 100%;
            background:
                radial-gradient(circle at top, rgba(139, 92, 246, 0.18), transparent 36%),
                radial-gradient(circle at bottom, rgba(56, 189, 248, 0.12), transparent 30%),
                #050816;
        }

        video,
        iframe {
            height: 100%;
            width: 100%;
            border: 0;
            display: block;
            background: #050816;
        }
    </style>
</head>
<body oncontextmenu="return false">
    <div class="player-shell">
        @if($mode === 'iframe')
            <iframe
                src="{{ $playbackUrl }}"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
        @else
            <video
                controls
                controlsList="nodownload noplaybackrate noremoteplayback"
                disablepictureinpicture
                playsinline
                preload="metadata"
            >
                <source src="{{ $playbackUrl }}">
            </video>
        @endif
    </div>

    <script>
        document.addEventListener('contextmenu', (event) => event.preventDefault(), true);
    </script>
</body>
</html>
