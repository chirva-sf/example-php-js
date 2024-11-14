<?php
// Подключение конфигурационного файла и класса User для работы с таблицей пользователей
require_once 'config.php';
require_once 'User.php';
$user = User::getInstance(DB_HOSTNAME, DB_DATABASE, DB_USERNAME, DB_PASSWORD);
if ($user->hasErrors()) {
  responseError(500, $user->getErrors());
}

// csrf-токен
session_start();
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Вывод списка пользователей и формы для добавления нового пользователя
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['user_id'])) {
  $users = $user->list();
  if ($user->hasErrors()) {
    responseError(500, $user->getErrors());
  }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Пример PHP и JS</title>
  <link crossorigin="anonymous" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo microtime(true); ?>">
</head>
<body>
  <div class="main">
    <div class="container">

      <section class="users-list">

        <h1>Пользователи</h1>

        <table class="table">
        <thead>
        <tr>
          <th width="1%">ID</th>
          <th width="23%">Email</th>
          <th width="23%">Имя</th>
          <th width="23%">Фамилия</th>
          <th width="10%">Возраст</th>
          <th width="18%">Создан</th>
          <th>Управление</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($users) { ?>
        <?php for($i = 0; $i < count($users); $i++) { ?>
        <tr data-id="<?php echo $users[$i]->id; ?>" data-mode="view">
          <td><?php echo $users[$i]->id; ?></td>
          <td><?php echo $users[$i]->email; ?></td>
          <td><?php echo $users[$i]->first_name; ?></td>
          <td><?php echo $users[$i]->last_name; ?></td>
          <td><?php echo $users[$i]->age; ?></td>
          <td><?php echo $users[$i]->created; ?></td>
          <td nowrap>
            <a href="#" data-toggle="tooltip" title="Редактировать" class="btn btn-primary edit-user"><i class="fa fa-pencil"></i></a>&nbsp;<a href="#" data-toggle="tooltip" title="Удалить" class="btn btn-danger delete-user"><i class="fa fa-trash-o"></i></a>
          </td>
        </tr>
        <?php } ?>
        <?php } else { ?>
        <td colspan="7" id="empty-list"><p>Пользователи отсутствуют.</p></td>
        <?php } ?>
        </tbody>
        </table>

      </section>
      <section class="add-user">

        <h2>Добавление нового пользователя</h2>

        <form action="index.php" method="POST" class="add-form">
          <div class="row required">
            <label class="col-sm-3 control-label">Email</label>
            <div class="col-sm-9">
              <input type="email" name="email" placeholder="Введите Email пользователя" value="" class="form-control">
            </div>
          </div>
          <div class="row required">
            <label class="col-sm-3 control-label">Имя</label>
            <div class="col-sm-9">
              <input type="text" name="first_name" placeholder="Введите Имя пользователя" value="" class="form-control">
            </div>
          </div>
          <div class="row required">
            <label class="col-sm-3 control-label">Фамилия</label>
            <div class="col-sm-9">
              <input type="text" name="last_name" placeholder="Введите Фамилию пользователя" value="" class="form-control">
            </div>
          </div>
          <div class="row required">
            <label class="col-sm-3 control-label">Возраст</label>
            <div class="col-sm-9">
              <select name="age" class="form-control">
                <?php for ($age = 5; $age <= 120; $age++) { ?>
                <option value="<?php echo $age; ?>"><?php echo $age; ?></option>
                <?php } ?>
              </select> лет
            </div>
          </div>
          <input type="submit" value="Отправить форму" class="btn btn-primary">
        </form>

      </section>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <script src="js/script.js?v=<?php echo microtime(true); ?>"></script>
</body>
</html>
<?php
} else {

  // Проверка токена для изменяющих данные запросов
  if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    if (!checkToken()) {
      responseError(403, 'Ошибка проверки token');
    }
  }

  // Добавление пользователя
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() === JSON_ERROR_NONE) {
      if ($user->validateData($data)) {
        $new_user_id = $user->create($data);
        if (!$user->hasErrors()) {
          responseSuccess(201, ['user_id' => $new_user_id]);
        } else {
          responseError(500, $user->getErrors());
        }
      } else {
        responseError(200, $user->getErrors());
      }
    } else {
      responseError(400, 'Неверный формат данных');
    }
  }

  // Изменение пользователя
  if ($_SERVER['REQUEST_METHOD'] == 'PUT' && isset($_GET['user_id'])) {
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() === JSON_ERROR_NONE) {
      if ($user->exist($_GET['user_id'])) {
        if ($user->validateData($data)) {
          $user->update($_GET['user_id'], $data);
          if (!$user->hasErrors()) {
            responseSuccess(200);
          } else {
            responseError(500, $user->getErrors());
          }
        } else {
          responseError(200, $user->getErrors());
        }
      } else {
        responseError(404, 'Пользователь не найден');
      }
    } else {
      responseError(400, 'Неверный формат данных');
    }
  }

  // Получение данных пользователя (для отмены изменений)
  if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['user_id'])) {
    if ($user->exist($_GET['user_id'])) {
      $userData = $user->get($_GET['user_id']);
      if (!$user->hasErrors()) {
        responseSuccess(200, $userData);
      } else {
        responseError(500, $user->getErrors());
      }
    } else {
      responseError(404, 'Пользователь не найден');
    }
  }

  // Удаление пользователя
  if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && isset($_GET['user_id'])) {
    if ($user->exist($_GET['user_id'])) {
      $user->delete($_GET['user_id']);
      if (!$user->hasErrors()) {
        responseSuccess(200);
      } else {
        responseError(500, $user->getErrors());
      }
    } else {
      responseError(404, 'Пользователь не найден');
    }
  }

  // Если программа попала сюда, значит вызван не поддерживаемый метод
  responseError(400, 'Не поддерживаемый метод');
}

// Формирование успешного ответа сервера
function responseSuccess(int $status = 200, array|object $data = null)
{
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  exit( json_encode( ['success' => true, 'data' => $data] ) );
}

// Формирование не успешного ответа сервера
function responseError(int $status, string $error_message)
{
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  exit( json_encode( ['success' => false, 'error' => $error_message], JSON_UNESCAPED_UNICODE ) );
}

// Проверка CSRF-токена
function checkToken(): bool
{
  $token = $_POST['token'] ?? $_SERVER['HTTP_TOKEN'] ?? '';
  return $token == $_SESSION['csrf_token'];
}
?>
