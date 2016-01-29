Cotonti: Versioning package
===========================


 > В будущем этот пакет предполагается к включению в ядро поставки Cotonti CMF.

Набор системных функцй для Cotonti, позволяющий легко производить проверку на соответствие версии определенным условиям и проверять зависимости заданных Расширений.


Введение
--------

На текущий момент (Cotonti Siena 0.9.18) пакет `Extensions`, включенный в ядро, позволяет проверять зависимости Расширений друг от друга — механизм учета жестких связей (обязательных к установке). А также указывать рекомендованные расширения. Однако нет механизма задания и проверки на соответствие конкретной версии «обязательного» Расширения. Так же нет возможности задать и проверить версию ядра, необходимую для нормальной работы Расширения.

Этот пакет разработан для восполнения данного пробела.


Особенности:
------------

* Простое и четкое сравнение версий (в рамках правил [Семантического версионирования](http://semver.org/lang/ru/))
* Позволяет задавать «нечеткие» условия с символами подстановки (`*`);
* Разбор и преобразование строк с данными о версии;
* Возможность проверить на выполнение зависимостей для конкретного расширения;
* Позволяет задавать и проверять зависимости компонент (ядра Cotonti, Модулей, Плагинов, Тем), задавая не только их версии, но обязательность установки;
* Можно задавать поддерживаемые версии PHP и проверять наличие и версию PHP расширений.

API:
----

Структура пакета стандартна для ядра Cotonti и представляет собой набор функций в едином файле `versioning.php`, подключаемом по необходимости.

### Пакет предоставляет следующий набор функций: ###

* **cot_check_requirements()** — проверяет весь набор зависимостей (условий), заданный для указанного Расширения;
* **cot_requirements_satisfied()** — проверяет удовлетворяется ли условие на наличие/установку определенного пакета в системе и на соответствие его версии заданному условию;
* **cot_find_version_tag()** — ищет в строке информацию наиболее близкую по формату к формату указания версий;
* **cot_version_parse()** — разбирает строку с номером версии и преобразует ее к определенному формату;
* **cot_version_constraint()** — проверяет версию на соответствие заданному условию. Альтернатива штатной функции version_compare() с более гибким форматом указания аргументов и поддержкой более широкого спектра вводимых данных и условий;
* **cot_version_compare()** — расширенная версия штатной функции `version_compare()`, для более корректного сравнения пред-релизных версий;
* **cot_phpversion()** — расширенный вариант штатной функции `phpversion()`. Позволяет определить установлено ли расширение и использует встроенные функции некоторых расширений для определения их версий.

Дополнительная информация:
--------------------------

Полное [описание интерфейса](package_api-doc.md) на английском языке.


Ссылки по теме:
---------------

* Семантическое версионирование: [version 2.0.0](http://semver.org/lang/ru/)
* PHP: документация к функции [version_compare()](http://php.net/manual/ru/function.version-compare.php) 
* PHP: документация к функции [phpversion()](http://php.net/manual/ru/function.phpversion.php) 