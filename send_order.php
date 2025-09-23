<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Данные заказа
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $comment = $input['comment'] ?? '';
    $items = $input['items'] ?? [];
    
    // Формируем текст заказа
    $orderText = "НОВЫЙ ЗАКАЗ\n\n";
    $orderText .= "Имя: $name\n";
    $orderText .= "Email: $email\n";
    $orderText .= "Телефон: $phone\n";
    $orderText .= "Комментарий: " . ($comment ?: 'не указан') . "\n\n";
    $orderText .= "ТОВАРЫ:\n";
    
    $total = 0;
    foreach ($items as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $total += $itemTotal;
        $orderText .= "▫️ {$item['name']} - {$item['quantity']} шт. × {$item['price']} руб. = {$itemTotal} руб.\n";
    }
    
    $orderText .= "\nИТОГО: {$total} руб.\n";
    $orderText .= "\nДата: " . date('d.m.Y H:i:s');

    // Настройки
    $telegramToken = 'YOUR_BOT_TOKEN'; // Замените на токен вашего бота
    $telegramChatId = 'YOUR_CHAT_ID'; // Замените на ваш chat_id
    $emailTo = 'snabpromgroup@mail.ru'; // Ваша почта

    // Отправка в Telegram
    $telegramSent = sendToTelegram($telegramToken, $telegramChatId, $orderText);
    
    // Отправка на почту
    $emailSent = sendToEmail($emailTo, $orderText, $name, $email);
    
    if ($telegramSent || $emailSent) {
        echo json_encode(['success' => true, 'message' => 'Заказ успешно отправлен']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка отправки заказа']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}

function sendToTelegram($token, $chatId, $text) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $filePath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    
    // Отправка файла в Telegram
    $url = "https://api.telegram.org/bot{$token}/sendDocument";
    $data = [
        'chat_id' => $chatId,
        'document' => new CURLFile($filePath, mime_content_type($filePath), $fileName),
        'caption' => $text
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result !== false;
}

function sendToEmail($to, $text, $name, $fromEmail) {
    $subject = "Новый заказ с сайта ТехСнабПром от {$name}";
    $headers = "From: snabpromgroup@mail.ru\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    
    return mail($to, $subject, $text, $headers);
}
?>