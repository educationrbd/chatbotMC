<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// API Configuration
$GEMINI_API_KEY = "";
$GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash-lite:generateContent";

// Islamic Knowledge Base
$islamicKnowledge = [
    'quran' => [
        'description' => 'Al-Quran dengan 114 surat dan 6.236 ayat lengkap terjemahan Bahasa Indonesia',
        'sources' => ['Al-Quran Kementerian Agama RI', 'Tafsir Ibnu Katsir', 'Tafsir Al-Qurtubi']
    ],
    'hadith' => [
        'description' => 'Kumpulan hadist shahih dari kitab utama',
        'sources' => ['Shahih Bukhari', 'Shahih Muslim', 'Sunan Tirmidzi', 'Sunan Abu Dawud', 'Sunan Nasai', 'Sunan Ibnu Majah']
    ],
    'fiqh' => [
        'description' => 'Kitab fikih dari empat mazhab utama',
        'sources' => ['Al-Majmu\' Syarh al-Muhadzab (Imam Nawawi)', 'Al-Umm (Imam Syafi\'i)', 'Al-Mughni (Imam Ibnu Qudamah)']
    ],
    'tauhid' => [
        'description' => 'Kitab-kitab tauhid dan akidah Ahlusunnah',
        'sources' => ['Kitab Tauhid (Syekh Abdul Wahhab)', 'Al-Aqidah al-Wasitiyah (Ibnu Taimiyah)', 'Risalat al-Tawhid (Muhammad Abduh)']
    ],
    'akhlak' => [
        'description' => 'Kitab akhlak dan tasawuf',
        'sources' => ['Ihya\' Ulumiddin (Imam Al-Ghazali)', 'Al-Hikam (Ibnu Athaillah)', 'Riyadhus Shalihin (Imam Nawawi)']
    ]
];

function getIslamicSystemPrompt($knowledge) {
    $prompt = "Anda adalah Ustadz AI, asisten cerdas untuk konsultasi Islam berbasis Al-Quran dan Hadist. 
    Anda dilengkapi dengan pengetahuan Islam yang komprehensif dari sumber-sumber berikut:
    
    Quran: " . $knowledge['quran']['description'] . "
    Sumber: " . implode(', ', $knowledge['quran']['sources']) . "
    
    Hadist: " . $knowledge['hadith']['description'] . "
    Sumber: " . implode(', ', $knowledge['hadith']['sources']) . "
    
    Fikih: " . $knowledge['fiqh']['description'] . "
    Sumber: " . implode(', ', $knowledge['fiqh']['sources']) . "
    
    Tauhid: " . $knowledge['tauhid']['description'] . "
    Sumber: " . implode(', ', $knowledge['tauhid']['sources']) . "
    
    Akhlak: " . $knowledge['akhlak']['description'] . "
    Sumber: " . implode(', ', $knowledge['akhlak']['sources']) . "
    
    ATURAN PENTING:
    1. Jawablah semua pertanyaan dengan bahasa Indonesia yang baik dan mudah dimengerti
    2. Selalu berikan jawaban berdasarkan Al-Quran dan Hadist yang shahih
    3. Sertakan referensi ayat/surat Al-Quran atau hadist jika relevan
    4. Jika tidak yakin, jelaskan bahwa jawaban bersifat informatif dan sarankan untuk berkonsultasi dengan ulama
    5. Berikan jawaban yang sesuai dengan pemahaman Ahlusunnah Wal Jama'ah
    6. Gunakan format HTML untuk mempercantik jawaban (b, ul, li, br, p, strong, em)
    7. Sapa dengan Assalamu'alaikum jika memulai percakapan baru
    8. Berikan jawaban yang ringkas namun komprehensif
    9. Jika pertanyaan di luar topik Islam, arahkan kembali ke topik keagamaan
    10. Selalu berikan jawaban yang edukatif dan memotivasi";
    
    return $prompt;
}

function callGeminiAPI($prompt, $apiKey, $apiUrl) {
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2048,
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . '?key=' . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Referer: https://masjidunacenter.com'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("CURL Error: " . $error);
    }

    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: " . $httpCode . " - " . $response);
    }

    return json_decode($response, true);
}

// Main API Logic
try {
    // Debug logging
    error_log("API Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
    error_log("Raw Input: " . file_get_contents('php://input'));
    error_log("POST Data: " . json_encode($_POST));
    error_log("GET Data: " . json_encode($_GET));
    
    // Allow both POST and GET for better compatibility
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        // If JSON decode fails, try to parse as form data
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            $input = $_POST;
        }
    } else {
        $input = $_GET;
    }
    
    // For testing, also allow message from POST form data
    if (empty($input) && isset($_POST['message'])) {
        $input = $_POST;
    }
    
    error_log("Processed Input: " . json_encode($input));
    
    if (!isset($input['message']) || empty(trim($input['message']))) {
        error_log("Message validation failed - Message: " . ($input['message'] ?? 'Not set'));
        throw new Exception('Message is required and cannot be empty');
    }

    $userMessage = trim($input['message']);
    $systemPrompt = getIslamicSystemPrompt($islamicKnowledge);
    $fullPrompt = $systemPrompt . "\n\n---\n\nPertanyaan Pengguna: " . $userMessage;

    // Call Gemini API
    $result = callGeminiAPI($fullPrompt, $GEMINI_API_KEY, $GEMINI_API_URL);

    // Extract response
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    if (empty($text)) {
        throw new Exception('Empty response from AI');
    }

    // Format response
    $response = [
        'success' => true,
        'message' => $text,
        'sources' => [
            'Al-Quran dan Terjemahan',
            'Hadist Shahih (Bukhari, Muslim, dll)',
            'Kitab Tafsir (Ibnu Katsir, Al-Qurtubi)',
            'Kitab Fikih (4 Mazhab)',
            'Kitab Tauhid dan Akhlak'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Maaf, terjadi kesalahan dalam memproses pertanyaan Anda. Silakan coba lagi nanti.'
    ], JSON_PRETTY_PRINT);
}
?>
