<?php
class User
{
  private static $instance = null;
  private $connection;  // Подключение к БД
  private $errors = []; // Ошибки при работе с классом

  // Конструктор (подключение к БД)
  private function __construct(string $host, string $dbname, string $login, string $password)
  {
    try {
      $this->connection = new PDO("mysql:host=$host;dbname=$dbname", $login, $password);
      $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
      $this->errors[] = 'Ошибка подключения к БД: ' . $e->getMessage();
    }
  }

  // Получение одного экземпляра класса
  public static function getInstance(string $host, string $dbname, string $login, string $password): object
  {
    if (self::$instance === null) {
      self::$instance = new User($host, $dbname, $login, $password);
    }
    return self::$instance;
  }

  // Создание пользователя
  public function create(array $data): ?int
  {
    try {
      $sql = 'INSERT INTO users (email, first_name, last_name, age, date_created) VALUES (:email, :first_name, :last_name, :age, NOW())';
      $stmt = $this->connection->prepare($sql);
      $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
      $stmt->bindParam(':first_name', $data['first_name'], PDO::PARAM_STR);
      $stmt->bindParam(':last_name', $data['last_name'], PDO::PARAM_STR);
      $stmt->bindParam(':age', $data['age'], PDO::PARAM_INT);
      $stmt->execute();
      return $this->connection->lastInsertId();
    } catch (PDOException $e) {
      $this->errors[] = 'Ошибка создания пользователя: ' . $e->getMessage();
      return null;
    }
  }

  // Обновление пользователя
  public function update(int $id, array $data): void
  {
    try {
      $sql = 'UPDATE users SET email = :email, first_name = :first_name, last_name = :last_name, age = :age' . (isset($data['created']) ? ', date_created = :date_created' : '') . ' WHERE id = :id';
      $stmt = $this->connection->prepare($sql);
      $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
      $stmt->bindParam(':first_name', $data['first_name'], PDO::PARAM_STR);
      $stmt->bindParam(':last_name', $data['last_name'], PDO::PARAM_STR);
      $stmt->bindParam(':age', $data['age'], PDO::PARAM_INT);
      if (isset($data['created'])) {
        $stmt->bindParam(':date_created', date("Y-m-d H:i:s", $data['created'] ? strtotime($data['created']) : time()), PDO::PARAM_STR);
      }
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
    } catch (PDOException $e) {
      $this->errors[] = 'Ошибка обновления пользователя: ' . $e->getMessage();
    }
  }

  // Удаление пользователя
  public function delete(int $id): void
  {
    try {
      $sql = "DELETE FROM users WHERE id = :id";
      $stmt = $this->connection->prepare($sql);
      $stmt->execute(['id' => $id]);
    } catch (PDOException $e) {
      $this->errors[] = 'Ошибка удаления пользователя: ' . $e->getMessage();
    }
  }

  // Получение списка пользователей
  public function list(): ?array
  {
    try {
      $sql = "SELECT *, DATE_FORMAT(date_created, '%d.%m.%Y %h:%i') AS created FROM users";
      return $this->connection->query($sql)->fetchAll(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
      $this->errors[] = 'Ошибка получения списка пользователей: ' . $e->getMessage();
      return null;
    }
  }

  // Получение одного пользователя
  public function get(int $id): object|bool|null
  {
    try {
      $sql = "SELECT *, DATE_FORMAT(date_created, '%d.%m.%Y %h:%i') AS created FROM users WHERE id = :id";
      $stmt = $this->connection->prepare($sql);
      $stmt->execute(['id' => $id]);
      return $stmt->fetchObject();
    } catch (PDOException $e) {
      $this->errors[] = 'Ошибка получения пользователя: ' . $e->getMessage();
      return null;
    }
  }

  // Проверка существования пользователя
  public function exist(int $id): ?bool
  {
    try {
      $sql = "SELECT 1 FROM users WHERE id = :id";
      $stmt = $this->connection->prepare($sql);
      $stmt->execute(['id' => $id]);
      return (bool)$stmt->fetch();
    } catch (PDOException $e) {
      $this->errors[] = 'Ошибка проверки пользователя: ' . $e->getMessage();
      return null;
    }
  }

  // Проверка корректности данных пользователя
  public function validateData($data): bool
  {
    if (empty($data['email'])) {
      $this->errors[] = 'Не передано обязательное поле "Email"';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $data['email'])) {
      $this->errors[] = 'Некорректный формат электронной почты в поле "Email"';
    }
    if (empty($data['first_name'])) {
      $this->errors[] = 'Не передано обязательное поле "Имя"';
    }
    if (empty($data['last_name'])) {
      $this->errors[] = 'Не передано обязательное поле "Фамилия"';
    }
    if (empty($data['age'])) {
      $this->errors[] = 'Не передано обязательное поле "Возраст"';
    } elseif ($data['age'] < 5 || $data['age'] > 120) {
      $this->errors[] = 'Поле "Возраст" должно быть от 5 до 120 лет.';
    }
    if (!empty($data['created'])) {
      $dt = DateTime::createFromFormat('d.m.Y H:i', $data['created']);
      if ($dt === false || $dt->format('d.m.Y H:i') !== $data['created']) {
        $this->errors[] = 'Некорректный формат даты и времени поля "Создан"';
      }
    }
    return !$this->hasErrors();
  }

  // Проверка наличия ошибок
  public function hasErrors(): bool
  {
    return (bool)$this->errors;
  }

  // Получение всех ошибок одной строкой
  public function getErrors(): string
  {
    return implode(', ', $this->errors);
  }
}
