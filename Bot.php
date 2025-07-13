<?php
require_once 'TelegramLib.php';
require_once 'PrettyTable.php';
use SQLite3;

$db = new SQLite3('db.db');
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        fname TEXT,
        uname TEXT,
        uid TEXT PRIMARY KEY,
        rating INTEGER,
        level TEXT,
        part_c TEXT,
        part_t TEXT,
        acc INTEGER,
        phone TEXT
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS tests (
        tname TEXT,
        tid INTEGER PRIMARY KEY,
        time TEXT,
        ans TEXT,
        res TEXT,
        cr TEXT,
        state INTEGER,
        rated INTEGER
    )
");

$last_t = null;
$ut = 0;

$reg_m = inlineKeyboard([
    [inlineButton('📝 Ro‘yxatdan o‘tish 📝', 'reg')]
]);

$home_m = inlineKeyboard([
    [inlineButton('👤 Profil 👤', 'h_p')],
    [inlineButton('👥 Foydalanuvchilar 👥', 'h_u')],
    [inlineButton('💻 Contestlar 💻', 'h_c')],
    [inlineButton('🧾 Testlar 🧾', 'h_t')],
    [inlineButton('📘 Botdan foydalanish', 'how_use')]
]);

$howuse_m = inlineKeyboard([
    [inlineButton('🔖 Buyruqlar', 'help_cmds')],
    [inlineButton('🧾 Testlar haqida', 'help_tests')],
    [inlineButton('👥 Foydalanuvchi turlari', 'help_users')],
    [inlineButton('🎯 Reyting va Level', 'help_rating')],
    [inlineButton('⬅️ Orqaga', 'b_h')]
]);

$p_m = inlineKeyboard([
    [inlineButton('💻 Qatnashgan contestlarim 💻', 'p_c')],
    [inlineButton('🧾 Qatnashgan testlarim 🧾', 'p_t')],
    [inlineButton('⬅️ Orqaga', 'b_h')]
]);

$b_m = inlineKeyboard([
    [inlineButton('⬅️ Orqaga', 'b_h')]
]);

$b_m_adm = inlineKeyboard([
    [inlineButton('⬅️ Orqaga', 'adm')]
]);

$b_m_adm_tests = inlineKeyboard([
    [inlineButton('⬅️ Orqaga', 'adm_test')]
]);

$b_m_adm_all_t = inlineKeyboard([
    [inlineButton('⬅️ Orqaga', 'my_t')]
]);

$ad_1 = inlineKeyboard([
    [
        inlineButton(' ➕ Yangi test yaratish ➕ ', 'new_t'),
        inlineButton(' ✏️ Mening testlarim ✏️ ', 'my_t')
    ],
    [inlineButton(" 🔑 Admin/VIP qo'shish 🔑 ", 'new_admin')]
]);

$conf = inlineKeyboard([
    [
        inlineButton('🟢 Yangi test yaratish 🟢', 'new_t_c'),
        inlineButton('⬅ Orqaga', 'adm')
    ]
]);

function new_rating($p, $t) {
    $m = 64;
    $s = ($t - $p) / $t;
    $e = 0.5;
    $res = (int)($m * ($s - $e));
    return $res;
}

$update = json_decode(file_get_contents('php://input'), true);
if (isset($update['message']) || isset($update['callback_query'])) {
    $message = $update['message'] ?? $update['callback_query']['message'];
    $chat_id = $message['chat']['id'];
    $from_id = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'];
    $callback_data = $update['callback_query']['data'] ?? null;
    $text = $update['message']['text'] ?? '';

    if (isset($update['message']['text'])) {
        if ($text === '/start') {
            global $db, $reg_m, $home_m, $ut;
            $uid = strval($from_id);
            $stmt = $db->prepare('SELECT * FROM users WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);

            if (!$user) {
                sendMessage($chat_id, 'Assalomu alaykum! PContest botiga xush kelibsiz!', ['reply_markup' => $reg_m]);
            } else {
                $ut = (int)$user['acc'];
                sendMessage($chat_id, "Salom {$user['uname']} {$user['fname']} ! PContest botiga xush kelibsiz.\n 💠 Bosh menyu:\n", ['reply_markup' => $home_m]);
            }
        } elseif ($text === '/adminp') {
            global $ut, $ad_1, $home_m;
            if ($ut > 0) {
                sendMessage($chat_id, "\n🔷 Admin Panel\n", ['reply_markup' => $ad_1]);
            } else {
                sendMessage($chat_id, "❌ Siz admin emassiz!", ['reply_markup' => $home_m]);
            }
        }
    }

    if ($callback_data) {
        global $db, $last_t, $ut;

        if ($callback_data === 'reg') {
            sendMessage($chat_id, "Ism va familiyangizni to'liq kiriting:");
            file_put_contents("user_state_$from_id.json", json_encode(['state' => 'awaiting_full_name', 'user_id' => $from_id]));
        } elseif ($callback_data === 'b_h') {
            deleteMessage($chat_id, $message['message_id']);
            sendMessage($chat_id, "\n💠 Bosh menyu\n", ['reply_markup' => $home_m]);
        } elseif ($callback_data === 'adm') {
            deleteMessage($chat_id, $message['message_id']);
            if ($ut > 0) {
                sendMessage($chat_id, "🔷 Admin Panel", ['reply_markup' => $ad_1]);
            } else {
                sendMessage($chat_id, "❌ Siz admin emassiz!", ['reply_markup' => $home_m]);
            }
        } elseif ($callback_data === 'h_p') {
            deleteMessage($chat_id, $message['message_id']);
            $uid = strval($from_id);
            $stmt = $db->prepare("SELECT * FROM users WHERE uid = :uid");
            $stmt->bindValue(':uid', $uid);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);

            if ($user) {
                $table = new PrettyTable([' 👤 Sizning profilingiz', '---']);
                $table->addRow([' 🆔 Ism', $user['fname']]);
                $table->addRow([' 🏆 Rating', $user['rating']]);
                $table->addRow([' ⭐️ Level', $user['level']]);
                $table->addRow([' 💻 Qatnashgan contestlari', $user['part_c'] ? count(explode(' ', $user['part_c'])) : 0]);
                $table->addRow([' 🧾 Qatnashgan testlari', $user['part_t'] ? count(explode(' ', $user['part_t'])) : 0]);
                sendMessage($chat_id, "```\n{$table}\n```", ['reply_markup' => $p_m]);
            }
        } elseif ($callback_data === 'h_u') {
            deleteMessage($chat_id, $message['message_id']);
            $result = $db->query("SELECT fname, rating FROM users");
            $table = new PrettyTable(['Foydalanuvchi', 'Rating']);
            while ($user = $result->fetchArray(SQLITE3_ASSOC)) {
                $table->addRow([$user['fname'], $user['rating']]);
            }
            sendMessage($chat_id, "👥 Foydalanuvchilar:\n\n```{$table}```", ['reply_markup' => $b_m]);
        } elseif ($callback_data === 'p_t') {
            $uid = strval($from_id);
            $stmt = $db->prepare("SELECT part_t FROM users WHERE uid = :uid");
            $stmt->bindValue(':uid', $uid);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            if (!$row || !$row['part_t']) {
                sendMessage($chat_id, "❌ Siz hali hech qanday testda qatnashmagansiz.", ['reply_markup' => $b_m]);
                return;
            }

            $test_ids = explode(' ', $row['part_t']);
            $table = new PrettyTable(['Test nomi', 'Test ID', "O'rin", 'Rating']);
            foreach ($test_ids as $tid) {
                $stmt = $db->prepare("SELECT tname, ans, res, rated FROM tests WHERE tid = :tid");
                $stmt->bindValue(':tid', $tid);
                $result = $stmt->execute();
                $data = $result->fetchArray(SQLITE3_ASSOC);
                if (!$data) continue;

                $tname = $data['tname'];
                $cor = $data['ans'];
                $res = json_decode($data['res'] ?: '{}', true);
                $rated = $data['rated'];

                $users = [];
                foreach ($res as $pid => $ans) {
                    $sc = 0;
                    for ($i = 0; $i < min(strlen($ans), strlen($cor)); $i++) {
                        if ($ans[$i] === $cor[$i]) $sc++;
                    }
                    $users[] = [$pid, $sc];
                }
                usort($users, function($a, $b) { return $b[1] - $a[1]; });
                $total = count($users);
                $rank = '?';
                foreach ($users as $i => $user) {
                    if ($user[0] === $uid) {
                        $rank = $i + 1;
                        break;
                    }
                }

                $delta = '–';
                if ($rated == 1) {
                    $stmt = $db->prepare("SELECT rating FROM users WHERE uid = :uid");
                    $stmt->bindValue(':uid', $uid);
                    $result = $stmt->execute();
                    $urating = $result->fetchArray(SQLITE3_ASSOC)['rating'];
                    $d = new_rating($rank, $total);
                    $delta = ($d >= 0 ? '+' : '') . $d;
                }
                $table->addRow([$tname, $tid, $rank, $delta]);
            }

            $kb = inlineKeyboard([
                [inlineButton("⬅️ Orqaga", 'b_h')]
            ]);
            sendMessage($chat_id, "🧾 Qatnashgan testlaringiz:\n```{$table}```", ['reply_markup' => $kb]);
        } elseif ($callback_data === 'h_c') {
            deleteMessage($chat_id, $message['message_id']);
            sendMessage($chat_id, "Hozircha contest mavjud emas.", ['reply_markup' => $b_m]);
        } elseif ($callback_data === 'enter_test_code') {
            sendMessage($chat_id, "🔢 Test ID ni kiriting:");
            file_put_contents("user_state_$from_id.json", json_encode(['state' => 'awaiting_test_id', 'user_id' => $from_id]));
        } elseif (strpos($callback_data, 'join_test_') === 0) {
            $tid = (int)explode('_', $callback_data)[2];
            $uid = strval($from_id);
            $stmt = $db->prepare("SELECT res, ans, state FROM tests WHERE tid = :tid");
            $stmt->bindValue(':tid', $tid);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            $res = json_decode($row['res'] ?: '{}', true);
            $cor = $row['ans'];
            $state = $row['state'];

            if ($state == 1) {
                if (isset($res[$uid])) {
                    sendMessage($chat_id, "⚠️ Siz bu testda qatnashgansiz.");
                    return;
                }
                sendMessage($chat_id, "Test javoblarini quyidagi ko'rinishda kiriting: ABCDEABCDE yoki abcdeabcde");
                file_put_contents("user_state_$from_id.json", json_encode([
                    'state' => 'awaiting_test_answer',
                    'user_id' => $from_id,
                    'tid' => $tid,
                    'cor' => $cor,
                    'attempt' => 1
                ]));
            } elseif ($state == 2) {
                sendMessage($chat_id, " 🛑 Bu test allaqachon tugagan");
            } else {
                sendMessage($chat_id, " 🚫 Bu test hali boshlanmagan.");
            }
        } elseif ($callback_data === 'h_t') {
            deleteMessage($chat_id, $message['message_id']);
            $result = $db->query("SELECT tname, tid, state FROM tests WHERE rated = 1");
            $tests = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $tests[] = $row;
            }

            $kb = inlineKeyboard([
                [inlineButton(" 🆔 Test ID orqali kirish 🆔 ", 'enter_test_code')]
            ]);
            $table = new PrettyTable(['Test nomi', 'Test ID', 'Holat']);
            foreach ($tests as $test) {
                $state = $test['state'] == 0 ? 'Boshlanmagan' : ($test['state'] == 1 ? "O'tkazilmoqda" : 'Tugagan');
                $table->addRow([$test['tname'], $test['tid'], $state]);
                $kb['inline_keyboard'][] = [inlineButton("{$test['tname']} ({$test['tid']})", "join_test_{$test['tid']}")];
            }
            $kb['inline_keyboard'][] = [inlineButton("⬅️ Orqaga", 'b_h')];

            if (empty($tests)) {
                sendMessage($chat_id, "⛔ Hozircha rated testlar mavjud emas.", ['reply_markup' => $kb]);
                return;
            }

            sendMessage($chat_id, " 🧾 Rated testlar ro‘yxati:\n ```{$table}```", ['reply_markup' => $kb]);
        } elseif ($callback_data === 'new_t') {
            deleteMessage($chat_id, $message['message_id']);
            sendMessage($chat_id, "➕ Yangi test yaratish", ['reply_markup' => $conf]);
        } elseif ($callback_data === 'new_t_c') {
            deleteMessage($chat_id, $message['message_id']);
            sendMessage($chat_id, "Test nomini kiriting:");
            file_put_contents("user_state_$from_id.json", json_encode(['state' => 'awaiting_test_name', 'user_id' => $from_id]));
        } elseif (strpos($callback_data, 'rated_') === 0) {
            $parts = explode('_', $callback_data);
            $rated = $parts[1] === 'yes' ? 1 : 0;
            $tname = implode('_', array_slice($parts, 2));
            sendMessage($chat_id, "Test kalitlarini kiriting (masalan: ABCDE):");
            file_put_contents("user_state_$from_id.json", json_encode([
                'state' => 'awaiting_test_keys',
                'user_id' => $from_id,
                'tname' => $tname,
                'rated' => $rated
            ]));
        } elseif (strpos($callback_data, 'edit_test_') === 0) {
            $tid = (int)explode('_', $callback_data)[2];
            $last_t = ['data' => "edit_test_$tid", 'from' => ['id' => $from_id], 'message' => $message];
            $stmt = $db->prepare("SELECT tname, state, rated FROM tests WHERE tid = :tid");
            $stmt->bindValue(':tid', $tid);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            if (!$row) {
                sendMessage($chat_id, "❌ Test topilmadi.", ['reply_markup' => $b_m_adm]);
                return;
            }

            $tname = $row['tname'];
            $state = $row['state'];
            $rated = $row['rated'];
            $status = $state == 0 ? "⏳ Boshlanmagan" : ($state == 1 ? "🟢 O‘tkazilmoqda" : "🔴 Tugagan");
            $text = "
📝 *Test nomi:* {$tname}
🆔 *Test ID:* `{$tid}`
📌 *Holati:* {$status}
🔷 *Test turi:* " . ($rated ? 'Rated' : 'Unrated') . "
            ";

            $kb = inlineKeyboard([]);
            if ($state == 0) {
                $kb['inline_keyboard'][] = [inlineButton("▶️ Testni boshlash", "start_test_$tid")];
            } elseif ($state == 1) {
                $kb['inline_keyboard'][] = [inlineButton("⛔ Testni tugatish", "end_test_$tid")];
            }
            $kb['inline_keyboard'][] = [
                inlineButton("📊 Natijalarni ko‘rish", "see_result_$tid"),
                inlineButton("❌ Testni o‘chirish", "del_test_$tid")
            ];
            $kb['inline_keyboard'][] = [inlineButton("⬅️ Orqaga", 'my_t')];

            sendMessage($chat_id, $text, ['reply_markup' => $kb]);
        } elseif ($callback_data === 'adm_test') {
            if ($last_t) {
                $tid = explode('_', $last_t['data'])[2];
                deleteMessage($chat_id, $message['message_id']);
                answerCallbackQuery($update['callback_query']['id']);
                sendMessage($chat_id, "Redirecting to edit_test_$tid", [
                    'reply_markup' => inlineKeyboard([
                        [inlineButton("Edit Test $tid", "edit_test_$tid")]
                    ])
                ]);
            }
        } elseif ($callback_data === 'my_t') {
            deleteMessage($chat_id, $message['message_id']);
            $result = $db->query("SELECT tname, tid, state, rated FROM tests");
            $tests = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $tests[] = $row;
            }

            if (empty($tests)) {
                sendMessage($chat_id, "❌ Hozircha testlar mavjud emas.", ['reply_markup' => $b_m_adm]);
                return;
            }

            $table = new PrettyTable(['Test', 'Test ID', 'Holat', 'Test turi']);
            $kb = inlineKeyboard([]);
            foreach ($tests as $test) {
                $status = $test['state'] == 0 ? "⚪️ Boshlanmagan" : ($test['state'] == 1 ? "🟢 O‘tkazilmoqda" : "🔴 Tugagan");
                $rated = $test['rated'] ? 'Rated' : 'Unrated';
                $table->addRow([$test['tname'], $test['tid'], $status, $rated]);
                $kb['inline_keyboard'][] = [inlineButton($test['tid'], "edit_test_{$test['tid']}")];
            }
            $kb['inline_keyboard'][] = [inlineButton("⬅️ Orqaga", 'adm')];
            sendMessage($chat_id, " 🧾 Mening testlarim:\n\n ```{$table}```", ['reply_markup' => $kb]);
        } elseif (strpos($callback_data, 'start_test_') === 0) {
            $tid = (int)explode('_', $callback_data)[2];
            $db->exec("UPDATE tests SET state = 1 WHERE tid = $tid");
            sendMessage($chat_id, "✅ Test `{$tid}` boshlandi.", ['parse_mode' => 'Markdown']);
            answerCallbackQuery($update['callback_query']['id']);
            sendMessage($chat_id, "Redirecting to edit_test_$tid", [
                'reply_markup' => inlineKeyboard([
                    [inlineButton("Edit Test $tid", "edit_test_$tid")]
                ])
            ]);
        } elseif (strpos($callback_data, 'end_test_') === 0) {
            $tid = (int)explode('_', $callback_data)[2];
            $db->exec("UPDATE tests SET state = 2 WHERE tid = $tid");
            sendMessage($chat_id, "⛔ Test `{$tid}` tugatildi.", ['parse_mode' => 'Markdown']);
            answerCallbackQuery($update['callback_query']['id']);
            sendMessage($chat_id, "Redirecting to edit_test_$tid", [
                'reply_markup' => inlineKeyboard([
                    [inlineButton("Edit Test $tid", "edit_test_$tid")]
                ])
            ]);
        } elseif (strpos($callback_data, 'see_result_') === 0) {
            deleteMessage($chat_id, $message['message_id']);
            $tid = (int)explode('_', $callback_data)[2];
            $stmt = $db->prepare("SELECT res, ans, rated FROM tests WHERE tid = :tid");
            $stmt->bindValue(':tid', $tid);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            if (!$row) {
                sendMessage($chat_id, "❌ Test topilmadi.", ['reply_markup' => $b_m_adm_tests]);
                return;
            }

            $res = json_decode($row['res'] ?: '{}', true);
            $cor = $row['ans'];
            $rated = $row['rated'];

            if (empty($res)) {
                sendMessage($chat_id, "❌ Bu testda hech kim qatnashmadi.", ['reply_markup' => $b_m_adm_tests]);
                return;
            }

            $ress = [];
            foreach ($res as $uid => $ans) {
                $score = 0;
                for ($i = 0; $i < min(strlen($ans), strlen($cor)); $i++) {
                    if ($ans[$i] === $cor[$i]) $score++;
                }
                $stmt = $db->prepare("SELECT fname, rating FROM users WHERE uid = :uid");
                $stmt->bindValue(':uid', $uid);
                $result = $stmt->execute();
                $user = $result->fetchArray(SQLITE3_ASSOC);
                $name = $user['fname'] ?: '---';
                $rating = $user['rating'];
                $ress[] = ['uid' => $uid, 'name' => $name, 'score' => $score, 'rating' => $rating];
            }

            usort($ress, function($a, $b) { return $b['score'] - $a['score']; });
            $total = count($ress);
            $table = new PrettyTable(['Foydalanuvchi', 'Ball', 'Reyting', 'Change']);
            foreach ($ress as $i => $user) {
                $uid = $user['uid'];
                $orating = $user['rating'];
                $rank = $i + 1;

                if ($rated == 1) {
                    $ch = new_rating($rank, $total);
                    $nrating = $orating + $ch;
                    $level = $nrating < 500 ? 'Noob' : ($nrating < 600 ? 'Pupil' : ($nrating < 900 ? 'Intermediate' : ($nrating < 1200 ? 'Expert' : ($nrating < 1600 ? 'Pro' : ($nrating < 2000 ? 'Master' : ($nrating < 2500 ? 'Pro Master' : ($nrating < 3000 ? 'Grandmaster' : 'Legend'))))));
                    $stmt = $db->prepare("UPDATE users SET rating = :rating, level = :level WHERE uid = :uid");
                    $stmt->bindValue(':rating', $nrating);
                    $stmt->bindValue(':level', $level);
                    $stmt->bindValue(':uid', $uid);
                    $stmt->execute();
                } else {
                    $ch = " --- ";
                }

                $table->addRow([$user['name'], $user['score'], $user['rating'], $ch]);
            }

            sendMessage($chat_id, " 📊 {$tid} - Test natijalari:\n```{$table}```", ['reply_markup' => $b_m_adm_tests]);
        } elseif (strpos($callback_data, 'del_test_') === 0) {
            deleteMessage($chat_id, $message['message_id']);
            $tid = explode('_', $callback_data)[2];
            $db->exec("DELETE FROM tests WHERE tid = $tid");
            sendMessage($chat_id, " ✅ Test muvaffaqiyatli o'chirildi.", ['reply_markup' => $b_m_adm_all_t]);
        } elseif ($callback_data === 'how_use') {
            sendMessage($chat_id, "📘 Botdan foydalanish haqida bo‘limni tanlang:", ['reply_markup' => $howuse_m]);
        } elseif ($callback_data === 'help_cmds') {
            $text = "
🧾 *Bot buyruqlari:*

/start – Botni qayta ishga tushirish 
/help – Botdan foydalanish tartibi
/adminp – Admin panel (faqat adminlar uchun)  

⚠️ Ko‘pgina funksiyalar menyular orqali amalga oshiriladi.
            ";
            sendMessage($chat_id, $text, ['reply_markup' => $howuse_m]);
        } elseif ($callback_data === 'help_tests') {
            $text = "
🧾 *Testlar haqida:*

– `Rated` testlar: Testda ko'rsatgan natijaga ko'ra ko'tariladi yoki tushadi.  
– `Unrated` testlar: Faqatgina Test ID orqali kirish mumkin. Reyting va Levelga hech qanday ta'sir ko'rsatmaydi.

– Test istalgan vaqtda Adminlar tomonidan boshlanib, tugashi mumkin.
– Test holati testlar ko'rsatiladigan ro'yxatga ko'ra aniqlash mumkin:
    - ⚪️ Boshlanmagan
    - 🟢 O‘tkazilmoqda
    - 🔴 Tugagan

– Test javoblarini faqatgina test o'tkazilayotgan paytda kiritish mumkin.
– Test natijalari test tugagandan so'ng Adminlar tomonidan barchaga e'lon qilinadi.
            ";
            sendMessage($chat_id, $text, ['reply_markup' => $howuse_m]);
        } elseif ($callback_data === 'help_users') {
            $text = "
 👥 *Foydalanuvchi turlari:*

 👤 `User` – Faqat testlarda ishtirok etish huquqiga ega.
 💎 `VIP` – Faqat Unrated testlar yaratishi huquqiga ega.
 🔧 `Admin` – Har qanday turdagi testlarni yaratish va boshqarish.
 👑 `Creator` – Barcha huquqlarga ega.

 ⚠️ Admin/creator huquqlari faqat maxsus tasdiqlangan foydalanuvchilarda bo‘ladi. VIP/Adminga ruxsat olish uchun biz bilan bog'laning: @IlhomovJasurbek.
            ";
            sendMessage($chat_id, $text, ['reply_markup' => $howuse_m]);
        } elseif ($callback_data === 'help_rating') {
            $text = "
🎯 *Reyting va Level tizimi:*

`0–500` — 🟤 Noob
`500–600` — ⚪️ Pupil
`600–900` — 🟡 Intermediate
`900–1200` — 🟠 Expert
`1200–1600` — 🟢 Pro
`1600–2000` — 🔴 Master
`2000–2500` — 🔵 Pro Master
`2500–3000` — 🟣 Grandmaster 
`3000+` — ⚫️ Legend  

🏆 Reyting va Level faqatgina *Rated* testlarda ko'rsatgan natijalarga qarab adminlar tomonidan ko'tariladi yoki tushiriladi.
            ";
            sendMessage($chat_id, $text, ['reply_markup' => $howuse_m]);
        } elseif ($callback_data === 'new_admin') {
            $uid = strval($from_id);
            $stmt = $db->prepare("SELECT acc FROM users WHERE uid = :uid");
            $stmt->bindValue(':uid', $uid);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if (!$row || $row['acc'] != 3) {
                sendMessage($chat_id, "❌ Faqat Creator yangi admin yoki VIP yaratishi mumkin.", ['reply_markup' => $b_m_adm]);
                return;
            }
            sendMessage($chat_id, "🆔 Foydalanuvchi ID sini kiriting:");
            file_put_contents("user_state_$from_id.json", json_encode(['state' => 'awaiting_new_admin_id', 'user_id' => $from_id]));
        } elseif (strpos($callback_data, 'set_admin_') === 0 || strpos($callback_data, 'set_vip_') === 0) {
            $creator_uid = strval($from_id);
            $stmt = $db->prepare("SELECT acc FROM users WHERE uid = :uid");
            $stmt->bindValue(':uid', $creator_uid);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if (!$row || $row['acc'] != 3) {
                sendMessage($chat_id, "❌ Faqat Creatorgina Admin/VIP qila oladi.", ['reply_markup' => $b_m_adm]);
                return;
            }

            $parts = explode('_', $callback_data);
            $role = $parts[1];
            $target_uid = $parts[2];
            $new_acc = $role === 'admin' ? 2 : 1;
            $stmt = $db->prepare("UPDATE users SET acc = :acc WHERE uid = :uid");
            $stmt->bindValue(':acc', $new_acc);
            $stmt->bindValue(':uid', $target_uid);
            $stmt->execute();
            sendMessage($chat_id, " ✅ Foydalanuvchi - {$target_uid} muvaffaqiyatli " . ($new_acc == 2 ? 'Admin' : 'VIP') . " qilindi.", ['reply_markup' => $ad_1]);
        } elseif (strpos($callback_data, 'lang_') === 0) {
            $parts = explode('_', $callback_data);
            $language = $parts[1];
            $user_id = $parts[2];
            $full_name = $parts[3];
            $phone = $parts[4];
            $age = $parts[5];
            $username = $update['callback_query']['from']['username'] ?? '---';
            $registered_at = date('Y-m-d H:i:s');

            $stmt = $db->prepare("
                INSERT INTO users (fname, uname, uid, rating, level, part_c, part_t, acc, phone)
                VALUES (:fname, :uname, :uid, :rating, :level, :part_c, :part_t, :acc, :phone)
            ");
            $stmt->bindValue(':fname', $full_name);
            $stmt->bindValue(':uname', "@$username");
            $stmt->bindValue(':uid', $user_id);
            $stmt->bindValue(':rating', 500);
            $stmt->bindValue(':level', 'Pupil');
            $stmt->bindValue(':part_c', '');
            $stmt->bindValue(':part_t', '');
            $stmt->bindValue(':acc', 0);
            $stmt->bindValue(':phone', $phone);
            $stmt->execute();

            sendMessage($chat_id, "Tabriklaymiz! Siz muvaffaqiyatli ro‘yxatdan o‘tdingiz!", ['reply_markup' => $home_m]);
            unlink("user_state_$from_id.json");
        }
    }

    if (isset($update['message']['text']) && file_exists("user_state_$from_id.json")) {
        $state_data = json_decode(file_get_contents("user_state_$from_id.json"), true);
        $state = $state_data['state'];

        if ($state === 'awaiting_full_name') {
            $full_name = trim($text);
            if (empty($full_name) || count(explode(' ', $full_name)) != 2) {
                sendMessage($chat_id, "❌ Iltimos, faqat ism va familiyangizni to'liq kiriting:");
                file_put_contents("user_state_$from_id.json", json_encode(['state' => 'awaiting_full_name', 'user_id' => $from_id]));
            } else {
                sendMessage($chat_id, "📞 Telefon raqamingizni kiriting (masalan: +998901234567):");
                file_put_contents("user_state_$from_id.json", json_encode([
                    'state' => 'awaiting_phone',
                    'user_id' => $from_id,
                    'full_name' => $full_name
                ]));
            }
        } elseif ($state === 'awaiting_phone') {
            $phone = trim($text);
            sendMessage($chat_id, "Yoshingizni kiriting:");
            file_put_contents("user_state_$from_id.json", json_encode([
                'state' => 'awaiting_age',
                'user_id' => $from_id,
                'full_name' => $state_data['full_name'],
                'phone' => $phone
            ]));
        } elseif ($state === 'awaiting_age') {
            $age = trim($text);
            $kb = inlineKeyboard([
                [inlineButton('🇺🇿 O‘zbek', "lang_uz_{$from_id}_{$state_data['full_name']}_{$state_data['phone']}_{$age}")]
            ]);
            sendMessage($chat_id, "Tilni tanlang:", ['reply_markup' => $kb]);
            file_put_contents("user_state_$from_id.json", json_encode([
                'state' => 'awaiting_language',
                'user_id' => $from_id,
                'full_name' => $state_data['full_name'],
                'phone' => $state_data['phone'],
                'age' => $age
            ]));
        } elseif ($state === 'awaiting_test_id') {
            $tid = (int)trim($text);
            $stmt = $db->prepare("SELECT res, ans, state FROM tests WHERE tid = :tid");
            $stmt->bindValue(':tid', $tid);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            if (!$row) {
                sendMessage($chat_id, "❌ Bunday IDli test topilmadi.", ['reply_markup' => $p_m]);
                unlink("user_state_$from_id.json");
                return;
            }

            $res = json_decode($row['res'] ?: '{}', true);
            $cor = $row['ans'];
            $state = $row['state'];

            if ($state != 1) {
                $status = $state == 0 ? "hali boshlanmagan" : "allaqachon tugagan";
                sendMessage($chat_id, "⛔ Bu test {$status}.", ['reply_markup' => $p_m]);
                unlink("user_state_$from_id.json");
                return;
            }

            if (isset($res[$from_id])) {
                sendMessage($chat_id, "⚠️ Siz bu testda allaqachon qatnashgansiz.", ['reply_markup' => $p_m]);
                unlink("user_state_$from_id.json");
                return;
            }

            sendMessage($chat_id, "Test javoblarini quyidagi ko'rinishda kiriting: ABCDEABCDE yoki abcdeabcde");
            file_put_contents("user_state_$from_id.json", json_encode([
                'state' => 'awaiting_test_answer',
                'user_id' => $from_id,
                'tid' => $tid,
                'cor' => $cor,
                'attempt' => 1
            ]));
        } elseif ($state === 'awaiting_test_answer') {
            $ans = strtoupper(trim($text));
            $tid = $state_data['tid'];
            $cor = $state_data['cor'];
            $attempt = $state_data['attempt'];

            if (!ctype_alpha($ans) || strlen($ans) != strlen($cor)) {
                if ($attempt < 5) {
                    $warning = strlen($ans) != strlen($cor) ?
                        " ❌ Test kalitlari soni " . strlen($cor) . " ta. Test javoblarni qaytadan kiriting:" :
                        " ❌ Test kalitlarini quyidagi ko`rinishda kiriting: ABCDEABCDE yoki abcdeabcde";
                    sendMessage($chat_id, $warning);
                    file_put_contents("user_state_$from_id.json", json_encode([
                        'state' => 'awaiting_test_answer',
                        'user_id' => $from_id,
                        'tid' => $tid,
                        'cor' => $cor,
                        'attempt' => $attempt + 1
                    ]));
                    return;
                } else {
                    sendMessage($chat_id, "\n💠 Bosh menyu\n", ['reply_markup' => $home_m]);
                    unlink("user_state_$from_id.json");
                    return;
                }
            }

            $stmt = $db->prepare("SELECT res FROM tests WHERE tid = :tid");
            $stmt->bindValue(':tid', $tid);
            $result = $stmt->execute();
            $res = json_decode($result->fetchArray(SQLITE3_ASSOC)['res'] ?: '{}', true);

            if (isset($res[$from_id])) {
                sendMessage($chat_id, "⚠️ Siz bu testda allaqachon qatnashgansiz.", ['reply_markup' => $home_m]);
                unlink("user_state_$from_id.json");
                return;
            }

            $res[$from_id] = $ans;
            $stmt = $db->prepare("UPDATE tests SET res = :res WHERE tid = :tid");
            $stmt->bindValue(':res', json_encode($res));
            $stmt->bindValue(':tid', $tid);
            $stmt->execute();

            $stmt = $db->prepare("SELECT part_t FROM users WHERE uid = :uid");
            $stmt->bindValue(':uid', $from_id);
            $result = $stmt->execute();
            $pp = $result->fetchArray(SQLITE3_ASSOC)['part_t'] ?: '';
            $np = array_merge(explode(' ', $pp), [$tid]);
            $stmt = $db->prepare("UPDATE users SET part_t = :part_t WHERE uid = :uid");
            $stmt->bindValue(':part_t', implode(' ', $np));
            $stmt->bindValue(':uid', $from_id);
            $stmt->execute();

            $score = 0;
            for ($i = 0; $i < strlen($cor); $i++) {
                if ($ans[$i] === $cor[$i]) $score++;
            }
            sendMessage($chat_id, " ✅ Javoblaringiz qabul qilindi. Natija test tugaganidan so'ng e'lon qilinadi.", ['reply_markup' => $home_m]);
            unlink("user_state_$from_id.json");
        } elseif ($state === 'awaiting_test_name') {
            $tname = trim($text);
            $kb = inlineKeyboard([]);
            if ($ut > 1) {
                $kb['inline_keyboard'][] = [inlineButton("✅ Rated", "rated_yes_{$tname}")];
            }
            $kb['inline_keyboard'][] = [inlineButton("❌ Unrated", "rated_no_{$tname}")];
            sendMessage($chat_id, "\n Test turi:\n", ['reply_markup' => $kb]);
            file_put_contents("user_state_$from_id.json", json_encode([
                'state' => 'awaiting_rated',
                'user_id' => $from_id,
                'tname' => $tname
            ]));
        } elseif ($state === 'awaiting_test_keys') {
            $answers = trim($text);
            $tname = $state_data['tname'];
            $rated = $state_data['rated'];
            $tid = rand(1000, 99999);

            $result = $db->query("SELECT tid FROM tests");
            $existing_ids = [];
            while ($row = $result->fetchArray(SQLITE3_NUM)) {
                $existing_ids[] = $row[0];
            }
            while (in_array($tid, $existing_ids)) {
                $tid = rand(1000, 99999);
            }

            $stmt = $db->prepare("
                INSERT INTO tests (tname, tid, time, ans, res, cr, state, rated)
                VALUES (:tname, :tid, :time, :ans, :res, :cr, :state, :rated)
            ");
            $stmt->bindValue(':tname', $tname);
            $stmt->bindValue(':tid', $tid);
            $stmt->bindValue(':time', date('Y-m-d H:i:s'));
            $stmt->bindValue(':ans', $answers);
            $stmt->bindValue(':res', '{}');
            $stmt->bindValue(':cr', $from_id);
            $stmt->bindValue(':state', 0);
            $stmt->bindValue(':rated', $rated);
            $stmt->execute();

            sendMessage($chat_id, "✅ Test muvaffaqiyatli yaratildi! Test ID: `{$tid}`", ['reply_markup' => $ad_1]);
            unlink("user_state_$from_id.json");
        } elseif ($state === 'awaiting_new_admin_id') {
            $target_uid = trim($text);
            $stmt = $db->prepare("SELECT fname FROM users WHERE uid = :uid");
            $stmt->bindValue(':uid', $target_uid);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if (!$row) {
                sendMessage($chat_id, "❌ Bunday foydalanuvchi topilmadi.", ['reply_markup' => $b_m_adm]);
                unlink("user_state_$from_id.json");
                return;
            }

            $kb = inlineKeyboard([
                [
                    inlineButton("🔧 Admin qilish", "set_admin_{$target_uid}"),
                    inlineButton("💎 VIP qilish", "set_vip_{$target_uid}")
                ],
                [inlineButton("⬅️ Orqaga", 'adm')]
            ]);
            sendMessage($chat_id, "Foydalanuvchi: {$row['fname']}\n\nUshbu foydalanuvchi uchun rol tanlang:", ['reply_markup' => $kb]);
            unlink("user_state_$from_id.json");
        }
    }
}

?>
