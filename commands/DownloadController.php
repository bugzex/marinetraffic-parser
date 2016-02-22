<?php
/**
 * Программа для сбора данных о движениях судов с сервиса marinetraffic.com.
 * Разработана в академических целях; использовать на свой страх и риск.
 *
 * @link https://github.com/maksim-trofimov/marinetraffic-parser
 *
 * @copyright Maksim Trofimov <maksim.trofimov@vvsu.ru>
 *
 * @author Viktor Grinyak <Viktor.Grinyak@vvsu.ru>
 * @author Maksim Trofimov <maksim.trofimov@vvsu.ru>
 * @author Boris Golovchenko <boris.golovchenko@vvsu.ru>
 *
 * @license The MIT License (MIT)
 */

namespace app\commands;

use app\models\Marine;
use app\models\MarinetrafficResult;
use app\models\Track;
use yii\console\Controller;

// todo: разместить библиотеки в vendor
set_include_path(
    get_include_path() . PATH_SEPARATOR .
    dirname(__FILE__) . '/lib' . PATH_SEPARATOR .
    dirname(__FILE__) . '/lib/HTTP'
);

// todo: библиотека устарела
require_once 'Request.php';

/**
 * контроллер загрузки данных
 * @todo сопоставить поля, если неверно назначены
 */
class DownloadController extends Controller
{
    /** @var int Номер итерации попытки получения и записи данных */
    private static $iteration = 1;

    public function actionIndex(
        $url = null,
        $sw_x = 130.0,
        $sw_y = 43.0,
        $ne_x = 133.0,
        $ne_y = 43.5,
        $zoom = 12,
        $time_zone = 'Asia/Vladivostok',
        $sleep_seconds = 10
    ) {
        // устанавливаем временную зону по-умолчанию
        date_default_timezone_set($time_zone);

        // -- устанавливаем кодировку, чтобы в консоли отображалась кирилица корректно (для windows)
        ob_start('ob_iconv_handler');

        $options = [
            'iconv.input_encoding' => 'cp866',
            'iconv.output_encoding' => 'cp866',
            'iconv.internal_encoding' => 'UTF-8',
        ];

        foreach ($options as $k => $v) {
            ini_set($k, $v);
        }
        // -- -- --

        // параметры http-запроса
        $request_params = array('method' => 'GET');

        // этим запросом получаем индентификатор сессии, который будем использовать при последующих запросах к сервису
        // url ссылается на страницу входа, но, по сути, можно использовать любую другую
        // в сервисе в url содержатся двоеточия - это разделитель ключа и значения параметра
        $url = 'http://www.marinetraffic.com/ru/users/ajax_user_menu/home:1';

        $requrest = new \HTTP_Request($url, $request_params);

        // использование прокси: http://pear.php.net/manual/ru/package.http.http-request.proxy-auth.php
        //$requrest->setProxy('proxy.example.com', 8080, 'johndoe', 'foo');

        // проверка работы скрипта через прокси из списка: http://proxylist.hidemyass.com/2
        //$requrest->setProxy('217.175.34.170', 8080);

        $requrest->sendRequest();
        $requrest->disconnect();
        $cookies = $requrest->getResponseCookies();
        $session_id = $cookies[0]['value'];

        // бесконечный цикл для сбора данных
        $url = "http://www.marinetraffic.com/map/getDataJson/sw_x:{$sw_x}/sw_y:{$sw_y}/ne_x:{$ne_x}/ne_y:{$ne_y}/zoom:{$zoom}/fleet:/station:0";
        while (true) {
            // -- делаем запрос на получение данных
            $requrest = new \HTTP_Request($url, $request_params);
            $requrest->addHeader('Referer', $url);
            $requrest->addCookie('CAKEPHP', $session_id);
            $requrest->sendRequest();
            $requrest->disconnect();
            // -- -- --

            // -- пропускаем итерацию при недолжном ответе
            if ($requrest->getResponseCode() !== 200 || empty($requrest->getResponseBody())) {
                echo 'Сервер сервиса вернул ошибку или пустые данные (возможно сервис недоступен).' . PHP_EOL;
                echo 'Пробую повторно.' . PHP_EOL;
                continue;
            }
            // -- -- --

            // -- декодируем json-объект в массив
            $decodeResult = json_decode($requrest->getResponseBody());
            if (($decodeResult instanceof \stdClass) === false) {
                echo 'Декодированные данные оказались другого типа (нужно менять код программы).' . PHP_EOL;
                echo 'Выхожу.' . PHP_EOL;
                return;
            } elseif (!isset($decodeResult->data->rows) || !is_array($decodeResult->data->rows)) {
                echo 'Декодированные данные о суднах и их движении имеют другой тип или изменился формат данных (нужно менять код программы).' . PHP_EOL;
                echo 'Выхожу.' . PHP_EOL;
                return;
            } else {
                echo 'Всего будет обработано ' . count($decodeResult->data->rows) . ' записей.' . PHP_EOL;
            }
            // -- -- --

            // перебираем массив и сохраняем данные в базу
            foreach ($decodeResult->data->rows as $marineData) {
                /** @var \StdClass $marineData */
                // -- преобразуем атрибуты к нижнему регистру
                $data = [];
                foreach ($marineData as $attributeName => $attributeValue) {
                    $data[strtolower($attributeName)] = $attributeValue;
                }
                // -- -- --

                // -- заполним прокси-объект
                $marinetrafficResult = new MarinetrafficResult();
                $marinetrafficResult->setAttributes($data);
                if ($marinetrafficResult->validate() === false) {
                    echo 'Ошибка при загрузки в прокси-объект данных, полученных из запроса: возможно изменился формат данных.' . PHP_EOL;
                    echo 'Выхожу.' . PHP_EOL;
                    return;
                }
                // -- -- --

                // идентифицируем судно по имени, т.к. идентификатор mmsi больше не передается, а идентификатор судна по
                // сервису не всегда совпадает (вероятно их внутренний идентификатор, который иногда меняется)
                $marine = Marine::findOne(['name' => strtoupper($marinetrafficResult->shipname)]);

                // -- если судно отсутствует в базе, то добавляем его
                if ($marine === null) {
                    $marine = new Marine();
                    $marine->identifier = $marinetrafficResult->ship_id;
                    $marine->name = $marinetrafficResult->shipname;
                    $marine->type = $marinetrafficResult->shiptype;
                    $marine->flag = $marinetrafficResult->flag;
                    $marine->length = $marinetrafficResult->length;
                    $marine->col11 = $marinetrafficResult->width;
                    $marine->port = $marinetrafficResult->destination;
                    $marine->col12 = $marinetrafficResult->l_fore;
                    $marine->col13 = $marinetrafficResult->w_left;
                    $marine->col15 = $marinetrafficResult->rot;

                    if ($marine->save()) {
                        echo 'Добавлено новое судно: ' . $marine->name . PHP_EOL;
                    } else {
                        echo 'Неудалось добавить новое судно из-за ошибок:' . PHP_EOL;
                        echo print_r($marine->getErrors(), true) . PHP_EOL;
                    }
                }
                // -- -- --

                // -- сохраняем положение судна, если не было ошибок
                if ($marine->id_marine !== null) {
                    $track = new Track();
                    $track->id_marine = $marine->id_marine;
                    $track->lat = $marinetrafficResult->lat;
                    $track->lon = $marinetrafficResult->lon;
                    $track->course = $marinetrafficResult->course;
                    //$track->course    = $marinetrafficResult->heading; // (heading похож на course)
                    $track->speed = $marinetrafficResult->speed;
                    $track->age = $marinetrafficResult->elapsed;
                    $track->date_add = date('Y-m-d_H:i');

                    if ($track->save()) {
                        echo 'Добавлено положение судна: ' . $marine->name . PHP_EOL;
                    } else {
                        echo 'Неудалось добавить положение судна из-за ошибок: ' . PHP_EOL;
                        echo print_r($track->getErrors(), true) . PHP_EOL;
                    }
                }
                // -- -- --
            }

            // -- выталкиваем сообщения в стандартный вывод и делаем паузу
            echo 'Закончена итерация #: ' . (static::$iteration++)  . PHP_EOL;
            echo 'Жду ' . $sleep_seconds . ' секунд...' . PHP_EOL;
            ob_flush();
            sleep($sleep_seconds);
            // -- -- --
        }
    }
}