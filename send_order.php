<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Данные заказа
    $name = $input['customer']['name'] ?? '';
    $email = $input['customer']['email'] ?? '';
    $phone = $input['customer']['phone'] ?? '';
    $comment = $input['customer']['comment'] ?? '';
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

    // Настройки (замените на реальные значения)
    $telegramToken = '8114103931:AAEDUG1UESqUPqLYIsFe78STajbippIM8Gg';
    $telegramChatId = '7065672537';
    $emailTo = 'snabpromgroup@mail.ru';

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
