<?php
// エラー表示設定（本番環境では無効化）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// セッション開始
session_start();

// 文字コード設定
mb_language("Japanese");
mb_internal_encoding("UTF-8");

// POSTリクエストのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: download.html');
    exit;
}

// 入力データの取得とサニタイズ
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$company = sanitize($_POST['company'] ?? '');
$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$question = sanitize($_POST['question'] ?? '');
$privacy = isset($_POST['privacy']) ? true : false;

// バリデーション
$errors = [];

if (empty($company)) {
    $errors[] = '貴社名を入力してください。';
}

if (empty($name)) {
    $errors[] = 'お名前を入力してください。';
}

if (empty($email)) {
    $errors[] = 'メールアドレスを入力してください。';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'メールアドレスの形式が正しくありません。';
}

if (empty($phone)) {
    $errors[] = '電話番号を入力してください。';
}

if (!$privacy) {
    $errors[] = '個人情報の取り扱いについて同意してください。';
}

// エラーがある場合はエラーページへ
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: download.html?error=1');
    exit;
}

// メール送信設定
$to = 'masahiro.yamashita@minshoku.jp'; // ここに受信用メールアドレスを設定
$subject = '【みんなの社食】資料ダウンロード申込';

// メール本文作成
$message = "資料ダウンロードのお申込みがありました。\n\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "【申込内容】\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "貴社名：{$company}\n";
$message .= "お名前：{$name}\n";
$message .= "メールアドレス：{$email}\n";
$message .= "電話番号：{$phone}\n";
if (!empty($question)) {
    $message .= "\n【ご質問】\n";
    $message .= $question . "\n";
}
$message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "\n送信日時：" . date('Y年m月d日 H:i:s') . "\n";

// メールヘッダー
$headers = "From: masahiro.yamashita@minshoku.jp\r\n"; // 送信元メールアドレスを設定
$headers .= "Reply-To: {$email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// メール送信
$mail_sent = mb_send_mail($to, $subject, $message, $headers);

// ユーザーへの自動返信メール
$user_subject = '【みんなの社食】資料ダウンロードのお申込みありがとうございます';
$user_message = "{$name} 様\n\n";
$user_message .= "この度は「みんなの社食」の資料をご請求いただき、誠にありがとうございます。\n\n";
$user_message .= "ご登録いただいたメールアドレス宛に、資料をお送りいたします。\n";
$user_message .= "しばらくお待ちくださいませ。\n\n";
$user_message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$user_message .= "【ご入力内容の確認】\n";
$user_message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$user_message .= "貴社名：{$company}\n";
$user_message .= "お名前：{$name}\n";
$user_message .= "メールアドレス：{$email}\n";
$user_message .= "電話番号：{$phone}\n";
if (!empty($question)) {
    $user_message .= "\n【ご質問】\n";
    $user_message .= $question . "\n";
}
$user_message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$user_message .= "ご不明な点がございましたら、お気軽にお問い合わせください。\n\n";
$user_message .= "株式会社みんなの社食\n";
$user_message .= "TEL：03-4500-1355\n";
$user_message .= "受付時間：平日10:00～18:00\n";

$user_headers = "From: masahiro.yamashita@minshoku.jp\r\n";
$user_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mb_send_mail($email, $user_subject, $user_message, $user_headers);

// 成功時はサンクスページへ
if ($mail_sent) {
    $_SESSION['form_success'] = true;
    header('Location: download.html?success=1');
} else {
    $_SESSION['form_errors'] = ['メール送信に失敗しました。しばらく時間をおいて再度お試しください。'];
    header('Location: download.html?error=1');
}
exit;
