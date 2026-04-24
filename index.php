<?php
header('Content-Type: text/html; charset=UTF-8');

// Функция подключения к БД
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $db_host = 'localhost';
        $db_user = 'u82358';
        $db_pass = '8445612';       
        $db_name = 'u82358';
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Ошибка подключения к БД: " . $e->getMessage());
        }
    }
    return $pdo;
}

$allowed_languages = [
    'Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
    'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'
];
$allowed_genders = ['male', 'female'];

// === GET-ЗАПРОС (отображение формы) ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = [];
    $errors = [];
    $values = [];
    $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];

    // Читаем cookies ошибок (не удаляем, т.к. ошибки будут висеть до исправления)
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
    }

    // Сообщения об ошибках
    if ($errors['full_name']) $messages[] = 'ФИО должно содержать только буквы и пробелы (макс. 150 символов).';
    if ($errors['phone']) $messages[] = 'Телефон должен содержать от 6 до 12 цифр, допускаются символы +, -, (, ), пробел.';
    if ($errors['email']) $messages[] = 'Введите корректный email.';
    if ($errors['birth_date']) $messages[] = 'Дата рождения должна быть в формате ГГГГ-ММ-ДД и не позже сегодняшнего дня.';
    if ($errors['gender']) $messages[] = 'Выберите пол.';
    if ($errors['biography']) $messages[] = 'Биография не должна превышать 10000 символов.';
    if ($errors['contract_accepted']) $messages[] = 'Необходимо подтвердить согласие.';
    if ($errors['languages']) $messages[] = 'Выберите хотя бы один язык программирования из списка.';

    // Значения из cookies (если есть)
    foreach ($fields as $field) {
        $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : $_COOKIE[$field . '_value'];
    }
    if (!empty($_COOKIE['languages_value'])) {
        $values['languages'] = explode(',', $_COOKIE['languages_value']);
    } else {
        $values['languages'] = [];
    }
    $values['contract_accepted'] = !empty($_COOKIE['contract_accepted_value']) ? true : false;

    // Сообщение об успешном сохранении (кука save удаляется)
      $success_message = '';
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 1);
        $success_message = 'Данные успешно сохранены!';
    }

    // Список языков для выпадающего списка
    $pdo = getDB();
    $languages_from_db = [];
    $stmt = $pdo->query("SELECT name FROM language ORDER BY name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $languages_from_db[] = $row['name'];
    }
    if (empty($languages_from_db)) {
        $languages_from_db = $allowed_languages;
    }

    include 'anketa.php';
    exit();
}

// === POST-ЗАПРОС (обработка отправки формы) ===
else {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];

    $errors = false;

    // Валидация (устанавливаем cookies ошибок)
    // ФИО
    if (empty($full_name) || !preg_match('/^[а-яА-Яa-zA-Z\s]+$/u', $full_name) || strlen($full_name) > 150) {
        setcookie('full_name_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('full_name_value', $full_name, time() + 30*24*3600);

    // Телефон
    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{6,12}$/', $phone)) {
        setcookie('phone_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('phone_value', $phone, time() + 30*24*3600);

    // Email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('email_value', $email, time() + 30*24*3600);

    // Дата рождения
    if (empty($birth_date)) {
        setcookie('birth_date_error', '1', time() + 24*3600);
        $errors = true;
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date || $date > new DateTime('today')) {
            setcookie('birth_date_error', '1', time() + 24*3600);
            $errors = true;
        }
    }
    setcookie('birth_date_value', $birth_date, time() + 30*24*3600);

    // Пол
    if (empty($gender) || !in_array($gender, $allowed_genders)) {
        setcookie('gender_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('gender_value', $gender, time() + 30*24*3600);

    // Биография
    if (strlen($biography) > 10000) {
        setcookie('biography_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('biography_value', $biography, time() + 30*24*3600);

    // Чекбокс
    if (!$contract_accepted) {
        setcookie('contract_accepted_error', '1', time() + 24*3600);
        $errors = true;
    }
    setcookie('contract_accepted_value', $contract_accepted ? '1' : '0', time() + 30*24*3600);

    // Языки
    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 24*3600);
        $errors = true;
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                setcookie('languages_error', '1', time() + 24*3600);
                $errors = true;
                break;
            }
        }
    }
    setcookie('languages_value', implode(',', $languages), time() + 30*24*3600);

    // Если есть ошибки, редирект
    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // === Без ошибок: сохраняем в БД ===
    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        // Вставка в application
        $stmt = $pdo->prepare("
            INSERT INTO application 
            (full_name, phone, email, birth_date, gender, biography, contract_accepted)
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)
        ");
        $stmt->execute([
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':email' => $email,
            ':birth_date' => $birth_date,
            ':gender' => $gender,
            ':biography' => $biography,
            ':contract_accepted' => $contract_accepted
        ]);
        $application_id = $pdo->lastInsertId();

        // Вставка языков
        $lang_map = [];
        $stmt = $pdo->query("SELECT id, name FROM language");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lang_map[$row['name']] = $row['id'];
        }
        $stmt = $pdo->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang_name) {
            if (isset($lang_map[$lang_name])) {
                $stmt->execute([$application_id, $lang_map[$lang_name]]);
            }
        }

        $pdo->commit();

        // Удаляем все куки ошибок
        $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
        foreach ($fields as $field) {
            setcookie($field . '_error', '', 1);
        }

        // Кука успешного сохранения
        setcookie('save', '1', time() + 24*3600);

        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        setcookie('db_error', '1', time() + 24*3600);
        header('Location: index.php');
        exit();
    }
}