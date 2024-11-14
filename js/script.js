// Класс для управления строками таблицы пользователей
class UserRow {

  constructor(row) {
    this.row = row;
    this.user_id = $(row).data('id');
    this.mode = $(row).data('mode');
    if (this.user_id) {
      this.bindEvents();
    } else {
      this.addUser();
    }
  }

  // Привязка событий к кнопкам в строке пользователя
  bindEvents() {
    // Инициализация подсказок
    this.row.find('[data-toggle="tooltip"]').tooltip();

    // Кнопки в режиме просмотра (редактировать, удалить)
    if (this.mode === 'view') {
      this.row.find('.edit-user').click((e) => {
        e.preventDefault();
        this.toggleMode('edit');
      });
      this.row.find('.delete-user').click((e) => {
        e.preventDefault();
        if (confirm('Вы уверены, что хотите удалить пользователя?')) {
          this.deleteUser();
        }
      });
    }

    // Кнопки в режиме редактирования (сохранить, отменить)
    if (this.mode === 'edit') {
      this.row.find('.update-user').click((e) => {
        e.preventDefault();
        this.updateUser();
      });
      this.row.find('.cancel-user').click((e) => {
        e.preventDefault();
        this.hideTooltip();
        this.viewUser();
      });
    }
  }

  // Переключение режима строки (просмотр/редактирование)
  toggleMode(newMode) {
    this.mode = newMode;
    $(this.row).data('mode', newMode);
    this.hideTooltip();
    this.row.find('td').each((i, cell) => {
      let value = $(cell).text();
      if (newMode === 'edit') {
        if (i === 1) $(cell).html(this.getInputHTML('email', 'email', value));
        if (i === 2) $(cell).html(this.getInputHTML('text', 'first_name', value));
        if (i === 3) $(cell).html(this.getInputHTML('text', 'last_name', value));
        if (i === 4) $(cell).html(this.getSelectHTML('age', value));
        if (i === 5) $(cell).html(this.getInputHTML('text', 'created', value));
      } else if (newMode === 'view') {
        let input = $(cell).find('input');
        let select = $(cell).find('select option:selected');
        value = input.length ? input.val() : select.val();
        if (i >= 1 && i <= 5) {
          $(cell).html(value);
        }
      }
      if (i === 6) {
        $(cell).html(this.getButtonsHTML(newMode));
      }
    });
    this.bindEvents();
  }

  // Генерация html-кода для input
  getInputHTML(type, name, value, width) {
    return '<input type="' + type + '" name="' + name + '" value="' + value + '" class="form-control"' + (width ? ' style="width:'+width+'"' : '') + '>';
  }

  // Генерация html-кода для select
  getSelectHTML(name, value, width) {
    let html = '<select name="' + name + '" class="form-control"' + (width ? ' style="width:'+width+'"' : '') + '>';
    for (let age = 5; age <= 120; age++) {
      html += '<option value="' + age + '"' + (age == value ? ' selected' : '') + '>' + age + '</option>';
    }
    html += '</select>';
    return html;
  }

  // Генерация html-кода кнопок
  getButtonsHTML(mode) {
    let html = '';
    if (mode === 'view') {
      html = '<a href="#" data-toggle="tooltip" title="Редактировать" class="btn btn-primary edit-user"><i class="fa fa-pencil"></i></a>&nbsp;';
      html += '<a href="#" data-toggle="tooltip" title="Удалить" class="btn btn-danger delete-user"><i class="fa fa-trash-o"></i></a>';
    }
    if (mode === 'edit') {
      html = '<a href="#" data-toggle="tooltip" title="Сохранить" class="btn btn-success update-user"><i class="fa fa-save"></i></a>&nbsp;';
      html += '<a href="#" data-toggle="tooltip" title="Отменить" class="btn btn-secondary cancel-user"><i class="fa fa-reply"></i></a>';
    }
    return html;
  }

  // Добавление нового пользователя
  addUser() {
    let data = {};
    $('.add-user form').find('input, select').each(function() {
      data[ $(this).attr('name') ] = $(this).val();
    });
    $.ajax({
      type: $('.add-user form').attr('method'),
      url: 'index.php',
      data: JSON.stringify(data),
      contentType: 'application/json',
      dataType: 'json',
      success: (response) => {
        if (response.success) {
          this.user_id = response.data.user_id;
          $(this.row).data('id', this.user_id);
          this.viewUser();
          if ($('#empty-list').length) {
            $('#empty-list').remove();
          }
        } else {
          this.showFormError(response.error);
        }
      },
      error: (xhr, textStatus, errorThrown) => {
        this.showFormError(textStatus + ' ' + errorThrown + ' ' + xhr.responseText);
      }
    });
  }

  // Обновление данных пользователя
  updateUser() {
    let data = {};
    this.row.find('input, select').each(function() {
      data[ $(this).attr('name') ] = $(this).val();
    });
    $.ajax({
      url: 'index.php?user_id=' + this.user_id,
      type: 'PUT',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify(data),
      success: (response) => {
        if (response.success) {
          this.toggleMode('view');
        } else {
          this.showPopupError(response.error);
        }
      },
      error: (xhr, textStatus, errorThrown) => {
        this.showPopupError(textStatus + ' ' + errorThrown + ' ' + xhr.responseText);
      }
    });
  }

  // Удаление пользователя
  deleteUser() {
    $.ajax({
      url: 'index.php?user_id=' + this.user_id,
      type: 'DELETE',
      dataType: 'json',
      success: (response) => {
        if (response.success) {
          this.row.remove();
        } else {
          this.showPopupError(response.error);
        }
      },
      error: (xhr, textStatus, errorThrown) => {
        this.showPopupError(textStatus + ' ' + errorThrown + ' ' + xhr.responseText);
      }
    });
  }

  // Вывод данных пользователя (при добавлении или отмене изменений)
  viewUser() {
    $.ajax({
      url: 'index.php?user_id=' + this.user_id,
      type: 'GET',
      dataType: 'json',
      success: (response) => {
        if (response.success) {
          this.mode = 'view';
          $(this.row).data('mode', this.mode);
          let data = response.data;
          let html = '', fields = ['id', 'email', 'first_name', 'last_name', 'age', 'created'];
          for (let field of fields) {
            html += '<td>' + data[field] + '</td>';
          }
          html += '<td>' + this.getButtonsHTML('view') + '</td>';
          this.row.html(html);
          this.bindEvents();
        } else {
          this.showPopupError(response.error);
        }
      },
      error: (xhr, textStatus, errorThrown) => {
        this.showPopupError(textStatus + ' ' + errorThrown + ' ' + xhr.responseText);
      }
    });
  }

  // Отображение ошибки в форме добавления пользователя
  showFormError(message) {
    if (!$('.add-user .alert').length) {
      $('.add-user input[type="submit"]').before('<div class="alert alert-danger" role="alert"></div>');
    }
    $('.add-user .alert').html(message);
  }

  // Отображение ошбики во всплывающем окне
  showPopupError(message) {
    // Выведу ошибку обычным alert (при необходимости можно здесь что-то покрасивее придумать)
    alert(message);
  }

  // Скрыть подсказки
  hideTooltip() {
    this.row.find('[data-toggle="tooltip"]').tooltip('hide');
  }
}

$(function(){
  // Токен для защиты от подделки запросов
  $.ajaxSetup({
    headers: { 'token': $('meta[name="csrf-token"]').attr('content') }
  });
  // Создание объектов для каждой строки таблицы пользователей
  $('.users-list tr[data-id]').each(function() {
    new UserRow($(this));
  });
  // Добавление нового пользователя
  $('.add-user form').submit(function(e){
    e.preventDefault();
    $('.table tbody').append('<tr data-id="" data-mode="view"></tr>');
    new UserRow($('.table tbody tr:last-child'));
  });
});
