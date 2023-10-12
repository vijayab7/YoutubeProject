<?php
require_once 'function.php';
require_once(dirname(__FILE__) . '/config/config.php');

$pdo = connect_db();

$sql = "SELECT * from shop where id=:id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', 1, PDO::PARAM_INT);
$stmt->execute();
$shop = $stmt->fetch();

$reserve_date_array = array();
for ($i = 0; $i <= $shop['reservable_date']; $i++) {
    $target_date = strtotime("+{$i}day");

    $reserve_date_array[date('Ymd', $target_date)] = date('n/j', $target_date);

}

$reserve_num_array = array();
for ($i = 1; $i <= $shop['max_reserve_num']; $i++) {
    $reserve_num_array[$i] = $i;
}
$reserve_time_array = array();
for ($i = date('G', strtotime($shop['start_time'])); $i <= date('G', strtotime($shop['end_time'])); $i++) {
    $reserve_time_array[sprintf('%02d', $i) . ':00'] = sprintf('%02d', $i) . ':00';
}


session_start();
$err = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $reserve_date = $_POST['reserve_date'];
    $reserve_time = $_POST['reserve_time'];
    $reserve_num = $_POST['reserve_num'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $tel = $_POST['tel'];
    $comment = $_POST['comment'];

    if (!$reserve_date) {
        $err['reserve_date'] = '予約日を選択してください。';
    }
    if (!$reserve_time) {
        $err['reserve_time'] = '人数を選択してください。';
    }
    if (!$reserve_num) {
        $err['reserve_num'] = '予約時間を選択してください。';
    } elseif (!preg_match('/^[0-9]+$/', $reserve_num)) {
        $err['reserve_num'] = '人数を正しく入力してください。';
    }
    if (!$name) {
        $err['name'] = '氏名を入力してください。';
    } elseif (mb_strlen($name, 'utf-8') > 20) {
        $err['name'] = '氏名は20文字以内で入力してください。';
    }
    if (!$email) {
        $err['email'] = 'メールアドレスを入力してください。';
    } elseif (mb_strlen($email, 'utf-8') > 100) {
        $err['email'] = 'メールアドレスは100文字以内で入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err['email'] = 'メールアドレスが不正です。';
    }
    if (!$tel) {
        $err['tel'] = '電話番号を入力してください。';
    } elseif (mb_strlen($tel, 'utf-8') > 20) {
        $err['tel'] = '電話番号は20文字以内で入力してください。';
    } elseif (!preg_match('/^\d{2,4}-\d{2,4}-\d{3,4}$/', $tel)) {
        $err['tel'] = '電話番号を正しく入力してください。';
    }
    if (mb_strlen($comment, 'utf-8') > 2000) {
        $err['comment'] = '備考欄は20文字以内で入力してください。';
    }
    if (empty($err)) {

        $sql = "SELECT SUM(reserve_num) FROM reserve WHERE DATE_FORMAT(reserve_date,'%Y%m%d') = :reserve_date 
        AND DATE_FORMAT(reserve_time,'%H:%i') = :reserve_time GROUP BY reserve_date, reserve_time LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':reserve_date', $reserve_date, PDO::PARAM_STR);
        $stmt->bindValue(':reserve_time', $reserve_time, PDO::PARAM_STR);
        $stmt->execute();
        $reserve_count = $stmt->fetchColumn();

        if ($reserve_count && ($reserve_count + $reserve_num) > $shop['max_reserve_num']) {
            $err['common'] = 'この日時はすでに予約が埋まっております。';
        }
        if (empty($err)) {
            $_SESSION['RESERVE']['reserve_date'] = $reserve_date;
            $_SESSION['RESERVE']['reserve_time'] = $reserve_time;
            $_SESSION['RESERVE']['reserve_num'] = $reserve_num;
            $_SESSION['RESERVE']['name'] = $name;
            $_SESSION['RESERVE']['email'] = $email;
            $_SESSION['RESERVE']['tel'] = $tel;
            $_SESSION['RESERVE']['comment'] = $comment;


            header('Location:/confirm.php');
            exit;
        }
    }

} else {
    if (isset($_SESSION['RESERVE'])) {
        $reserve_date = $_SESSION['RESERVE']['reserve_date'];
        $reserve_time = $_SESSION['RESERVE']['reserve_time'];
        $reserve_num = $_SESSION['RESERVE']['reserve_num'];
        $name = $_SESSION['RESERVE']['name'];
        $email = $_SESSION['RESERVE']['email'];
        $tel = $_SESSION['RESERVE']['tel'];
        $comment = $_SESSION['RESERVE']['comment'];

    } else {
        $reserve_date = "";
        $reserve_time = "";
        $reserve_num = "";
        $name = "";
        $email = "";
        $tel = "";
        $comment = "";
    }
}
?>

<!doctype html>
<html lang="ja">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <!-- Original Css -->
    <link href="/css/style.css" rel="stylesheet">
    <title>予約受付</title>
</head>

<body>
    <header>SAMPLE SHOP </header>
    <h1>予約受付</h1>
    <form class="m-3" method="post">
        <?php if (isset($err['common'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= $err['common'] ?>
            </div>
        <?php endif; ?>
        <div class="mb-3">
            <label for="exampleFormControlInput1" class="form-label"> 【1】予約日を選択</label>
            <?= $test = arrayToSelect('reserve_date', $reserve_date_array, $reserve_date) ?>
            <div class="invalid-feedback">
                <?= $err['reserve_date'] ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="exampleFormControlInput1"
                class="form-label <?php if (isset($err['reserve_num']))
                    echo 'is-invalid' ?>">【2】人数を選択</label>
            <?= $test = arrayToSelect('reserve_num', $reserve_num_array, $reserve_num) ?>
            <div class="invalid-feedback">
                <?= $err['reserve_num'] ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="exampleFormControlInput1"
                class="form-label <?php if (isset($err['reserve_time']))
                    echo 'is-invalid' ?>">【3】予約時間を選択</label>
            <?= $test = arrayToSelect('reserve_time', $reserve_time_array, $reserve_time) ?>

            <div class="invalid-feedback">
                <?= $err['reserve_time'] ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="exampleFormControlInput1"
                class="form-label <?php if (isset($err['name']))
                    echo 'is-invalid' ?>">【4】予約者情報を入力</label>
                <input type="text" class="form-control" name="name" placeholder="氏名" value="<?= $name ?>">
            <div class="invalid-feedback">
                <?= $err['name'] ?>
            </div>
        </div>
        <div class="mb-3">
            <input type="text" class="form-control <?php if (isset($err['email']))
                echo 'is-invalid' ?>" name="email"
                    placeholder="メールアドレス" value="<?= $email ?>">
            <div class="invalid-feedback">
                <?= $err['email'] ?>
            </div>
        </div>
        <div class="mb-3">
            <input type="text" class="form-control <?php if (isset($err['tel']))
                echo 'is-invalid' ?>" name="tel"
                    placeholder="電話番号" value="<?= $tel ?>">
            <div class="invalid-feedback">
                <?= $err['tel'] ?>
            </div>
        </div>

        <div class="mb-3">
            <label for="exampleFormControlTextarea1"
                class="form-label <?php if (isset($err['comment']))
                    echo 'is-invalid' ?>">【5】備考欄</label>
                <textarea class="form-control" name="comment" rows="3" placeholder="備考欄"><?= $comment ?></textarea>
            <div class="invalid-feedback">
                <?= $err['comment'] ?>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-primary rounded-pill" type="submit">確認画面へ</button>
            <button class="btn btn-secondary rounded-pill" type="button">戻る</button>
        </div>
    </form>
    <!-- Optional JavaScript; choose one of the two! -->

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
        crossorigin="anonymous"></script>
</body>

</html>
