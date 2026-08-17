# 🇺🇦 Проста бібліотека для еквайрінгу [MonobankPay](https://api.monobank.ua/) (PlataByMono)
Документація по REST API [тут](https://api.monobank.ua/docs/acquiring.html)

Для ведення запитів вам знадобиться токен з особистого кабінету [https://fop.monobank.ua/](https://fop.monobank.ua/) або тестовий токен з [https://api.monobank.ua/](https://api.monobank.ua/)

Встановити бібліотеку:
```bash
composer require plakidan/monobank-pay
```
Також зробив варіант для php5.6:
```bash
composer require plakidan/monobank-pay:dev-php5.6-adaptation
```

### Мінімальні вимоги:
* php >=7.4
* guzzlehttp/guzzle >= 7.0
* starkbank/ecdsa >= 0.0.5

### Приклади використання:
```php

require_once 'vendor/autoload.php';

//створили клієнта - через нього запити будуть слатись
$monoClient = new \MonoPay\Client('YOUR_TOKEN_HERE');

//із клієнта можна отримати id та назву мерчанта
echo $monoClient->getMerchantId();
echo $monoClient->getMerchantName();

//для створення платежів створюємо цей об'єкт
$monoPayment = new \MonoPay\Payment($monoClient);

//створення платежу
$invoice = $monoPayment->create(
    1000,
    [
        //деталі оплати
        'merchantPaymInfo' => [
            'reference' => 'my_shop_order_28142', //номер чека, замовлення, тощо; визначається мерчантом (вами)
            'destination' => 'Оплата за замовлення #28142', //призначення платежу
            'basketOrder' => [ //наповнення замовлення, використовується для відображення кошика замовлення
                [
                    'name' => 'Товар1', //назва товару
                    'qty' => 2, //кількість
                    'sum' => 500, //сума у мінімальних одиницях валюти за одиницю товару
                    'icon' => 'https://example.com/images/product1.jpg', //посилання на зображення товару
                    'unit' => 'уп.', //назва одиниці вимiру товару
                ],
            ],
        ],
        'redirectUrl' => 'https://example.com/order-result', //адреса для повернення (GET) - на цю адресу буде переадресовано користувача після завершення оплати (у разі успіху або помилки)
        'webHookUrl' => 'https://example.com/mono-webhook', //адреса для CallBack (POST) – на цю адресу буде надіслано дані про стан платежу при кожній зміні статусу. Зміст тіла запиту ідентичний відповіді запиту “перевірки статусу рахунку”
        'validity' => 3600 * 24 * 7, //строк дії в секундах, за замовчуванням рахунок перестає бути дійсним через 24 години
        'paymentType' => 'debit', //debit | hold. Тип операції. Для значення hold термін складає 9 днів. Якщо через 9 днів холд не буде фіналізовано — він скасовується
    ]
);
print_r($invoice);

//інформація про платіж
$invoice = $monoPayment->info('2305046jUBEj8WfyaBdB');
print_r($invoice);

//відшкодування
$result = $monoPayment->refund('2305046jUBEj8WfyaBdB');
print_r($result);

//скасування посилання на оплату
$result = $monoPayment->cancel('2305046jUBEj8WfyaBdB');
print_r($result);

//деталі успішної оплати
$invoiceDetails = $monoPayment->successDetails('2305046jUBEj8WfyaBdB');
print_r($invoiceDetails);

//списати заблоковану сумму
//зверніть увагу: списати можна тільки таку самму або меншу сумму яку ви заблокували
$result = $monoPayment->captureHold('2305046jUBEj8WfyaBdB', 500);
print_r($result);

//список успішних оплат за останні сутки
$list = $monoPayment->items(time() - 60 * 60 * 24);
print_r($list);

//оплата по токену (токен можна отримати якщо створити платіж і додати в цей платіж помітку saveCardData)
$result = $monoPayment->createWithCardToken('67XZtXdR4NpKU3',50.99,[
        'ccy' => 980, //валюта гривня
        'initiationKind' => \MonoPay\Payment::TOKEN_PAYMENT_INIT_KIND_MERCHANT //або client - ініціатор платежу
    ]);
print_r($result);

//список прив'язаник карток
$result = $monoPayment->listCardTokens('user_245'); //user_245 - walletId який ви вказували при створенні токену на прешому платежі
print_r($result);

//видалення картки 
deleteCardToken('67XZtXdR4NpKU3');

//Список фіскальних чеків
$result = $monoPayment->getFiscalReceipts('2305046jUBEj8WfyaBdB');
print_r($result);
```

### Отримання вебхуку:
> ЗАСТЕРЕЖЕННЯ!!!
> Обов'язково обгорніть обробку вебхука в try-catch, бо якщо ви відповісте НЕ 200 кодом, а веб-хуків буде багато - ваш IP може потрапити в тимчасовий бан (хз на скільки, в мене було на сутки)
```php
require_once 'vendor/autoload.php';

//створили клієнта - через нього запити будуть слатись
$monoClient = new \MonoPay\Client('YOUR_TOKEN_HERE');

//отримання публічного ключа (бажано закешувати)
$publicKey = $monoClient->getPublicKey();

//класс для роботи з вебхуком
$monoWebhook = new \MonoPay\Webhook($monoClient, $publicKey, $_SERVER['HTTP_X_SIGN']);
//отримуємо вхідні дані
$body = file_get_contents('php://input');
//валідуємо дані
if ($monoWebhook->verify($body)) {
    echo 'Ці дані прислав монобанк, можна обробляти';
} else {
    echo 'Дані прислав шахрай, ігноруємо';
}
```

### Використання через проксі:
Третім аргументом конструктора можна передати будь-які [опції Guzzle](https://docs.guzzlephp.org/en/stable/request-options.html), зокрема `proxy`:
```php
$monoClient = new \MonoPay\Client('YOUR_TOKEN_HERE', [], [
    'proxy' => 'http://10.0.0.1:3128',
]);
```

#### TODO List:
* Переробити вхідні параметри і вихідні дані на класи з описаними методами

### P.S. Завжди раді вашим pull-реквестам
