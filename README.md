# vk
## Подключение
### Используя composer
```
composer require buhoycoder/vk
```
```php
require_once 'vendor/autoload.php'; //Подключаем библиотеку
```
### Вручную
1. Скачать последний релиз
2. Подключить autoload.php. Вот так будет происходить подключение, если ваш скрипт находится в той же папке, что и папка vk-master
```php
require_once 'vk-master/autoload.php'; //Подключаем библиотеку
```
## Примеры использования
```php
const VK_KEY = ''; //токен сообщества или пользователя
const CONFIRM_STR = ''; //ключ авторизации сообщества, который вы получили
```
### Callback API
Обработчик CallbackApi будет ждать уведомления о событиях из формы ВКонтакте.
Как только событие произошло, вы будете уведомлены о нем и сможете его обработать.

Посмотрите на этот пример:
```php
use BuhoyCoder\VK\VkApi;
use BuhoyCoder\VK\Context;
use BuhoyCoder\VK\Callback;

$vk = new VkApi(VK_KEY);
$callback = new Callback($vk, CONFIRM_STR);

$callback->on('message_new', function (Context $ctx) {
    $ctx->replyMessage('ok');
});
```
