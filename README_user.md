# Порядок установки проекта

* Запуск Docker `` docker compose up -d ``
* Запуск зависимостей `` docker-compose run --rm composer install --ignore-platform-reqs ``- это только с этим docker-compose.yml
* Переход в контейнер  `` docker-compose exec -it web bash ``
* Запуск установки расширений yii2 `` composer install ``
* Очистка кеш `` composer clear-cache ``
* Запуск обновлений расширений yii2 `` composer update ``
* Запуск миграций `` php yii migrate `` 

## Добавить пользователей
`` php yii user/init``
После выполнения инициализации вы сможете войти в систему с учетными данными:
* admin / admin
* manager / manager
* user / user


## Создание отдельного пользователя:
``  php yii user/create <username> <email> <password> [role]``

## Инициализация ролей RBAC:
``  php yii rbac/init-rbac``
Эта команда выполнит следующие действия:
* Удалит все существующие роли и разрешения RBAC
* Создаст правило AuthorRule для проверки владельца записи
* Создаст необходимые разрешения (permissions) для системы
* Создаст роли для admin, manager и user с правильной иерархией
* Создаст трех пользователей с помощью UserInitializer
* Назначит соответствующие роли этим пользователям  
  После этого вы сможете войти в систему с учетными данными:
* admin / admin
* manager / manager
* user / user  
  И у каждого пользователя будут соответствующие права доступа согласно его роли.

