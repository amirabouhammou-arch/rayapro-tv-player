<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // في بيئة إنتاج، حدد نطاقات معينة بدلاً من *
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// هذه وظيفة وهمية لإنشاء Signed URL. في الإنتاج، ستقوم بالآتي:
// 1. الاتصال بواجهة برمجة تطبيقات CDN (مثل CloudFront, Akamai) لإنشاء رابط موقع.
// 2. أو إنشاء JWT الخاص بك وتوقيعه.
function generateSecureStreamLink($originalLink, $channelId, $userId = null) {
    // مثال بسيط جداً للمحاكاة: إضافة معلمة توقيع وانتهاء صلاحية
    // في الإنتاج: هذا سيكون توقيعًا حقيقيًا يعتمد على مفتاح سري
    $expires = time() + 300; // ينتهي بعد 5 دقائق
    $signature = md5($originalLink . $expires . 'your_secret_key_here'); // استخدم مفتاح سري قوي
    return "{$originalLink}?expires={$expires}&signature={$signature}&channelId={$channelId}";
}

// التحقق من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$category_id = isset($input['category_id']) ? intval($input['category_id']) : 0;
$channel_num = isset($input['channel_num']) ? intval($input['channel_num']) : 0;

if ($category_id === 0 || $channel_num === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid category or channel ID.']);
    exit();
}

// --- المصادقة والترخيص (Authentication & Authorization) ---
// هنا يجب عليك تنفيذ منطق التحقق من أن المستخدم الحالي مصرح له
// بمشاهدة هذه القناة. هذا يمكن أن يشمل:
// - التحقق من جلسة المستخدم (Session).
// - التحقق من رمز JWT تم إرساله في رأس Authorization.
// - التحقق من اشتراك المستخدم.
//
// مثال: إذا كان لديك نظام تسجيل دخول، قد تتحقق من $_SESSION['user_id']
$is_authenticated = true; // افترض أن المستخدم مصادق عليه لأغراض العرض التوضيحي

if (!$is_authenticated) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required to access this stream.']);
    exit();
}

// --- استرداد رابط القناة الأصلي من ملفات JSON المحمية ---
$data_file = __DIR__ . "/../data/{$category_id}.json"; // المسار الصحيح لملفات البيانات

if (!file_exists($data_file)) {
    http_response_code(404); // Not Found
    echo json_encode(['success' => false, 'message' => 'Category data file not found.']);
    exit();
}

$json_content = file_get_contents($data_file);
$channels = json_decode($json_content, true);

if ($channels === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error decoding channel data: ' . json_last_error_msg()]);
    exit();
}

$original_channel_link = '';
$channel_name = 'غير معروف';

foreach ($channels as $channel) {
    if (isset($channel['num']) && intval($channel['num']) === $channel_num) {
        $original_channel_link = $channel['link'];
        $channel_name = $channel['name'];
        break;
    }
}

if (empty($original_channel_link)) {
    http_response_code(404); // Not Found
    echo json_encode(['success' => false, 'message' => 'Channel link not found for the given ID.']);
    exit();
}

// --- إنشاء رابط البث الآمن ---
$secure_stream_link = generateSecureStreamLink($original_channel_link, $channel_num);

echo json_encode([
    'success' => true,
    'stream_link' => $secure_stream_link,
    'channel_name' => $channel_name
]);

?>