<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'WhatsApp Encryption Demo';
$this->params['breadcrumbs'][] = $this->title;
?>
    <div class="whatsapp-index">
        <h1><?= Html::encode($this->title) ?></h1>

        <div class="alert alert-info">
            <strong>Демонстрация шифрования/дешифрования WhatsApp медиафайлов</strong>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Шифрование файла</h3>
                    </div>
                    <div class="panel-body">
                        <?php $form = ActiveForm::begin([
                            'id' => 'encrypt-form',
                            'action' => ['/whats-app/encrypt'],
                            'options' => ['enctype' => 'multipart/form-data']
                        ]) ?>

                        <?= $form->field($encryptModel, 'file')->fileInput() ?>

                        <?= $form->field($encryptModel, 'mediaType')->dropDownList([
                            'IMAGE' => 'Изображение',
                            'VIDEO' => 'Видео',
                            'AUDIO' => 'Аудио',
                            'DOCUMENT' => 'Документ'
                        ], ['prompt' => 'Выберите тип медиа']) ?>

                        <?= Html::submitButton('Зашифровать', ['class' => 'btn btn-primary']) ?>

                        <?php ActiveForm::end() ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title">Дешифрование файла</h3>
                    </div>
                    <div class="panel-body">
                        <?php $form = ActiveForm::begin([
                            'id' => 'decrypt-form',
                            'action' => ['/whats-app/decrypt'],
                            'options' => ['enctype' => 'multipart/form-data']
                        ]) ?>

                        <?= $form->field($decryptModel, 'file')->fileInput() ?>

                        <?= $form->field($decryptModel, 'mediaKey')->textInput([
                            'placeholder' => 'Введите base64 encoded media key'
                        ]) ?>

                        <?= $form->field($decryptModel, 'mediaType')->dropDownList([
                            'IMAGE' => 'Изображение',
                            'VIDEO' => 'Видео',
                            'AUDIO' => 'Аудио',
                            'DOCUMENT' => 'Документ'
                        ], ['prompt' => 'Выберите тип медиа']) ?>

                        <?= Html::submitButton('Дешифровать', ['class' => 'btn btn-success']) ?>

                        <?php ActiveForm::end() ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Тестирование</h3>
                    </div>
                    <div class="panel-body">
                        <?= Html::a('Обработать тестовые samples', ['/whats-app/process-samples'], [
                            'class' => 'btn btn-info',
                            'id' => 'test-samples'
                        ]) ?>

                        <?= Html::a('Очистить runtime', ['/whats-app/clear-runtime'], [
                            'class' => 'btn btn-warning',
                            'data' => [
                                'confirm' => 'Вы уверены, что хотите очистить папку runtime?',
                                'method' => 'post',
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Просмотр ключа из файла</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="key-file-input">Выберите файл ключа (.key):</label>
                            <input type="file" id="key-file-input" class="form-control" accept=".key" />
                        </div>
                        <button type="button" id="show-key-btn" class="btn btn-primary" disabled>Показать ключ</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно для отображения ключа -->
        <div class="modal fade" id="key-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">Ключ (base64)</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Размер ключа:</label>
                            <p id="key-size-info" class="form-control-static"></p>
                        </div>
                        <div class="form-group">
                            <label>Ключ в формате base64:</label>
                            <textarea id="key-base64-output" class="form-control" rows="3" readonly></textarea>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-default" id="copy-key-btn">Копировать</button>
                            <button type="button" class="btn btn-success" id="use-in-decrypt-btn">Использовать в форме дешифрования</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="results" class="row" style="display: none;">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Результаты</h3>
                    </div>
                    <div class="panel-body" id="results-content">
                        <!-- Результаты будут здесь -->
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$this->registerJs(<<<JS
// Обработка формы шифрования
$('#encrypt-form').on('beforeSubmit', function(e) {
    e.preventDefault();
    var form = $(this);
    var formData = new FormData(this);
    
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                var html = '<div class="alert alert-success">';
                html += '<strong>Успешно!</strong><br>';
                html += 'Файл зашифрован<br>';
                html += 'Исходный размер: ' + response.originalSize + ' байт<br>';
                html += 'Зашифрованный размер: ' + response.encryptedSize + ' байт<br>';
                html += 'Media Key: <code>' + response.mediaKey + '</code><br>';
                if (response.sidecarFile) {
                    html += 'Sidecar файл: ' + response.sidecarFile + '<br>';
                }
                html += '</div>';
                $('#results-content').html(html);
                $('#results').show();
            } else {
                $('#results-content').html('<div class="alert alert-danger">Ошибка: ' + response.error + '</div>');
                $('#results').show();
            }
        },
        error: function() {
            $('#results-content').html('<div class="alert alert-danger">Ошибка сервера</div>');
            $('#results').show();
        }
    });
    return false;
});

// Обработка формы дешифрования
$('#decrypt-form').on('beforeSubmit', function(e) {
    e.preventDefault();
    var form = $(this);
    var formData = new FormData(this);
    
    // Логирование данных формы перед отправкой
    console.log('Дешифрование: начало отправки формы');
    var fileInput = form.find('input[type="file"]')[0];
    var mediaKey = form.find('input[name*="mediaKey"]').val();
    var mediaType = form.find('select[name*="mediaType"]').val();
    console.log('Дешифрование: файл', fileInput.files[0] ? fileInput.files[0].name : 'не выбран');
    console.log('Дешифрование: mediaKey', mediaKey);
    console.log('Дешифрование: mediaType', mediaType);
    
    // Логирование всех данных FormData
    console.log('Дешифрование: все данные FormData:');
    for (var pair of formData.entries()) {
        if (pair[0] === 'DecryptForm[file]') {
            console.log(pair[0] + ': [File] ' + pair[1].name);
        } else {
            console.log(pair[0] + ': ' + pair[1]);
        }
    }
    
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Дешифрование: ответ сервера', response);
            if (response.success) {
                var html = '<div class="alert alert-success">';
                html += '<strong>Успешно!</strong><br>';
                html += 'Файл дешифрован<br>';
                html += 'Размер: ' + response.size + ' байт<br>';
                html += '</div>';
                $('#results-content').html(html);
                $('#results').show();
            } else {
                console.log('Дешифрование: ошибка в ответе', response.error);
                
                var errorMessage = response.error;
                var errorHtml = '<div class="alert alert-danger">';
                errorHtml += '<h4><strong>Ошибка дешифрования</strong></h4>';
                errorHtml += '<p>' + errorMessage + '</p>';
                
                // Добавляем подсказки для ошибки MAC
                if (errorMessage.indexOf('MAC') !== -1 || errorMessage.indexOf('не подходит') !== -1) {
                    errorHtml += '<hr><h5><strong>Подсказки для решения проблемы:</strong></h5>';
                    errorHtml += '<ul>';
                    errorHtml += '<li>Убедитесь, что используете <strong>тот же mediaKey</strong>, который был возвращен после шифрования файла</li>';
                    errorHtml += '<li>Проверьте, что выбран <strong>правильный тип медиа</strong> (IMAGE/VIDEO/AUDIO/DOCUMENT) - он должен совпадать с типом, использованным при шифровании</li>';
                    errorHtml += '<li>Если файл был зашифрован через форму на сайте, скопируйте mediaKey из результата шифрования</li>';
                    errorHtml += '<li>Если файл из папки samples, используйте соответствующий .key файл из той же папки</li>';
                    errorHtml += '<li>Можно использовать функцию "Просмотр ключа из файла" для загрузки ключа из .key файла</li>';
                    errorHtml += '</ul>';
                }
                
                // Добавляем подсказки для ошибки "слишком короткие"
                if (errorMessage.indexOf('слишком короткие') !== -1) {
                    errorHtml += '<hr><h5><strong>Подсказки:</strong></h5>';
                    errorHtml += '<ul>';
                    errorHtml += '<li>Файл поврежден или не является зашифрованным файлом WhatsApp</li>';
                    errorHtml += '<li>Проверьте, что файл был зашифрован через форму на сайте</li>';
                    errorHtml += '<li>Минимальный размер зашифрованного файла должен быть 26 байт</li>';
                    errorHtml += '</ul>';
                }
                
                // Добавляем подсказки для ошибки формата ключа
                if (errorMessage.indexOf('формат media key') !== -1 || errorMessage.indexOf('32 байта') !== -1) {
                    errorHtml += '<hr><h5><strong>Подсказки:</strong></h5>';
                    errorHtml += '<ul>';
                    errorHtml += '<li>Ключ должен быть в формате base64 и после декодирования иметь размер 32 байта</li>';
                    errorHtml += '<li>Проверьте, что ключ скопирован полностью, без пробелов и переносов строк</li>';
                    errorHtml += '<li>Можно использовать функцию "Просмотр ключа из файла" для загрузки ключа из .key файла</li>';
                    errorHtml += '</ul>';
                }
                
                // Добавляем подсказки для ошибки валидации
                if (errorMessage.indexOf('валидации') !== -1) {
                    errorHtml += '<hr><h5><strong>Подсказки:</strong></h5>';
                    errorHtml += '<ul>';
                    errorHtml += '<li>Заполните все обязательные поля: файл, mediaKey и тип медиа</li>';
                    errorHtml += '<li>Проверьте, что файл выбран правильно</li>';
                    errorHtml += '<li>Проверьте, что mediaKey введен корректно</li>';
                    errorHtml += '</ul>';
                }
                
                errorHtml += '</div>';
                $('#results-content').html(errorHtml);
                $('#results').show();
            }
        },
        error: function(xhr, status, error) {
            console.log('Дешифрование: ошибка запроса');
            console.log('Дешифрование: статус', status);
            console.log('Дешифрование: ошибка', error);
            console.log('Дешифрование: ответ сервера', xhr.responseText);
            $('#results-content').html('<div class="alert alert-danger">Ошибка сервера: ' + error + '</div>');
            $('#results').show();
        }
    });
    return false;
});

// Обработка тестирования samples
$('#test-samples').on('click', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('href'),
        type: 'GET',
        success: function(response) {
            if (Array.isArray(response)) {
                var html = '<div class="alert alert-info"><strong>Результаты тестирования:</strong></div>';
                html += '<table class="table table-striped"><thead><tr><th>Файл</th><th>Тип</th><th>Результат</th><th>Исходный размер</th><th>Расшифрованный размер</th></tr></thead><tbody>';
                
                response.forEach(function(item) {
                    html += '<tr>';
                    html += '<td>' + item.file + '</td>';
                    html += '<td>' + item.mediaType + '</td>';
                    html += '<td>' + (item.success ? '<span class="text-success">✓ Успех</span>' : '<span class="text-danger">✗ Ошибка: ' + item.error + '</span>') + '</td>';
                    html += '<td>' + item.originalSize + '</td>';
                    html += '<td>' + (item.decryptedSize || 'N/A') + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            } else {
                var html = '<div class="alert alert-warning">' + response.message + '</div>';
            }
            
            $('#results-content').html(html);
            $('#results').show();
        }
    });
});

// Обработка выбора файла ключа
$('#key-file-input').on('change', function() {
    var fileInput = this;
    var showBtn = $('#show-key-btn');
    
    if (fileInput.files && fileInput.files.length > 0) {
        showBtn.prop('disabled', false);
        console.log('Выбран файл ключа:', fileInput.files[0].name);
    } else {
        showBtn.prop('disabled', true);
    }
});

// Обработка кнопки "Показать ключ"
$('#show-key-btn').on('click', function() {
    var fileInput = document.getElementById('key-file-input');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Пожалуйста, выберите файл ключа');
        return;
    }
    
    var file = fileInput.files[0];
    var reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            var arrayBuffer = e.target.result;
            var bytes = new Uint8Array(arrayBuffer);
            var keySize = bytes.length;
            
            // Конвертируем ArrayBuffer в бинарную строку для base64
            var binaryString = '';
            for (var i = 0; i < bytes.length; i++) {
                binaryString += String.fromCharCode(bytes[i]);
            }
            
            // Проверка размера ключа
            var sizeInfo = 'Размер: ' + keySize + ' байт';
            if (keySize !== 32) {
                sizeInfo += ' <span class="text-warning">(ожидается 32 байта)</span>';
            }
            
            // Конвертируем в base64
            var base64Key = btoa(binaryString);
            
            // Показываем информацию в модальном окне
            $('#key-size-info').html(sizeInfo);
            $('#key-base64-output').val(base64Key);
            
            // Открываем модальное окно
            $('#key-modal').modal('show');
            
        } catch (error) {
            console.error('Ошибка при обработке файла:', error);
            alert('Ошибка при чтении файла: ' + error.message);
        }
    };
    
    reader.onerror = function() {
        alert('Ошибка при чтении файла');
    };
    
    // Читаем файл как ArrayBuffer
    reader.readAsArrayBuffer(file);
});

// Копирование ключа в буфер обмена
$('#copy-key-btn').on('click', function() {
    var keyTextarea = document.getElementById('key-base64-output');
    keyTextarea.select();
    keyTextarea.setSelectionRange(0, 99999); // Для мобильных устройств
    
    try {
        document.execCommand('copy');
        $(this).text('Скопировано!').removeClass('btn-default').addClass('btn-success');
        setTimeout(function() {
            $('#copy-key-btn').text('Копировать').removeClass('btn-success').addClass('btn-default');
        }, 2000);
    } catch (err) {
        alert('Не удалось скопировать. Пожалуйста, скопируйте вручную.');
    }
});

// Использование ключа в форме дешифрования
$('#use-in-decrypt-btn').on('click', function() {
    var base64Key = $('#key-base64-output').val();
    
    if (!base64Key) {
        alert('Ключ не загружен');
        return;
    }
    
    // Заполняем поле mediaKey в форме дешифрования
    var mediaKeyInput = $('#decrypt-form').find('input[name*="mediaKey"]');
    if (mediaKeyInput.length > 0) {
        mediaKeyInput.val(base64Key);
        $('#key-modal').modal('hide');
        
        // Показываем сообщение об успехе
        var alertHtml = '<div class="alert alert-success alert-dismissible fade in" role="alert" style="margin-top: 10px;">';
        alertHtml += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        alertHtml += '<span aria-hidden="true">&times;</span></button>';
        alertHtml += '<strong>Успешно!</strong> Ключ добавлен в форму дешифрования.';
        alertHtml += '</div>';
        
        // Удаляем предыдущие сообщения и добавляем новое
        $('#decrypt-form').find('.alert').remove();
        $('#decrypt-form').prepend(alertHtml);
        
        // Автоматически скрываем сообщение через 3 секунды
        setTimeout(function() {
            $('#decrypt-form').find('.alert').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    } else {
        alert('Не найдено поле для ввода ключа в форме дешифрования');
    }
});
JS
);