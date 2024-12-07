### Начальные условия ###
На компьютере должен быть установлен docker (желательно, но не обязательно Docker Desktop)  https://docs.docker.com/get-docker/
и docker compose https://docs.docker.com/compose/install/



## Инструкция по работе с проектом ##
1. скачиваем проект и поднимаем докер в фоновом режиме

```
docker compose up -d
```
2. Заходим в контейнер с php
```
docker exec -it php-skeleton  /bin/bash
```
3. устанавливаем зависимости
```
composer install
```
4. Создаем базу данных
```
php bin/console doctrine:database:create
```
5. По скольку в проекте нету adminer или pgadmin для работы с 
базой данных, подключаем ее в phpstorm
```
- Правый верхний угол , нажимаем Database
- затем на кнопку "+"
- выбираем postgresql, вводим логин пароль 
(которые у нас в .env или docker-compose),
указываем внешний порт бд в данном случае 5435
- > testConnection -> OK
```
6. Накатываем миграции
```
php bin/console doctrine:migrations:migrate
```

7.Теперь можно делать запросы с помощью postman
```
/add-guest - параметры тела запроса name,lastname,phoneNumber,email,country
/edit-guest - параметры тела запроса 
currentPhoneNumber,newName,newLastName,newPhoneNumber,newEmail
/get-guest-by-email - параметры email
/get-guest-by-phone - параметры phone (формат 79297169752)
/get-guest-by-id/{id} - параметры id (можно напрямую)
/delete-guest-by-phone - параметры phone (формат 79297169752)
/delete-guest/{id} - параметры id (можно напрямую)
```

9. Для запуска юнит тестов команда
```
php bin/phpunit
```

Примерное время создания данного проекта рабочие сутки с тестами вместе 2 дня.


