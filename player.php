<?php
// حماية أساسية للرأس
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('Referrer-Policy: strict-origin-when-cross-origin');

// استقبال رابط البث واسم القناة من GET
$stream_link = isset($_GET['stream_link']) ? $_GET['stream_link'] : '';
$channel_name = isset($_GET['channel_name']) ? $_GET['channel_name'] : 'قناة غير معروفة';
$error_message = '';
if (empty($stream_link)) {
    $error_message = 'لم يتم توفير رابط البث أو الرابط غير صالح.';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشغل RAYAPRO TV - <?php echo htmlspecialchars($channel_name); ?></title>
    <link rel="stylesheet" href="sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        body {
            background-color: #1a1a1a;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }
        .player-container {
            width: 95%;
            max-width: 1200px;
            background-color: #000;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
            padding: 10px;
            text-align: center;
        }
        video {
            width: 100%;
            height: auto;
            border-radius: 6px;
            display: block;
            margin: 0 auto;
        }
        h1 {
            color: #00aaff;
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        .error-message {
            color: #ff4d4d;
            font-size: 1.2em;
            padding: 20px;
            background-color: #330000;
            border-radius: 5px;
            margin-top: 20px;
        }
        .loading-text {
            color: #00ffcc;
            font-size: 1.1em;
            margin-top: 15px;
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 1.5em;
            }
            .player-container {
                padding: 5px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="player-container">
        <h1><?php echo htmlspecialchars($channel_name); ?></h1>
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php else: ?>
            <video id="player" controls autoplay playsinline></video>
            <p class="loading-text">جاري تحميل البث...</p>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var video = document.getElementById('player');
                    var channelLink = "<?php echo htmlspecialchars($stream_link); ?>";
                    if (Hls.isSupported()) {
                        var hls = new Hls();
                        hls.loadSource(channelLink);
                        hls.attachMedia(video);
                        hls.on(Hls.Events.MANIFEST_PARSED, function() {
                            video.play().catch(e => console.error("Autoplay failed:", e));
                            document.querySelector('.loading-text').style.display = 'none';
                        });
                        hls.on(Hls.Events.ERROR, function(event, data) {
                            if (data.fatal) {
                                switch(data.type) {
                                    case Hls.ErrorTypes.NETWORK_ERROR:
                                        hls.startLoad();
                                        break;
                                    case Hls.ErrorTypes.MEDIA_ERROR:
                                        hls.recoverMediaError();
                                        break;
                                    default:
                                        hls.destroy();
                                        document.querySelector('.loading-text').innerText = 'حدث خطأ أثناء تحميل البث: ' + data.details;
                                        document.querySelector('.loading-text').style.color = 'red';
                                        break;
                                }
                            }
                        });
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = channelLink;
                        video.addEventListener('loadedmetadata', function() {
                            video.play().catch(e => console.error("Autoplay failed:", e));
                            document.querySelector('.loading-text').style.display = 'none';
                        });
                        video.addEventListener('error', function() {
                            document.querySelector('.loading-text').innerText = 'حدث خطأ أثناء تحميل البث (native HLS).';
                            document.querySelector('.loading-text').style.color = 'red';
                        });
                    } else {
                        document.querySelector('.loading-text').innerText = 'متصفحك لا يدعم تشغيل البث المباشر.';
                        document.querySelector('.loading-text').style.color = 'red';
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>