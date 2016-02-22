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
class DownloadController extends Controller {

    /** @var int Номер итерации попытки получения и записи данных */
    private static $iteration = 1;

    /**
     * Запустить загрузку и сохранение данных с сервиса.
     *
     * @param string $url
     * @param float $sw_x
     * @param float $sw_y
     * @param float $ne_x
     * @param float $ne_y
     * @param int $zoom
     * @param string $time_zone
     * @param int $sleep_seconds
     */
    public function actionIndex(
        $url = 'http://www.marinetraffic.com/map/getDataJson',
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

        ini_set('iconv.input_encoding', 'cp866');
        ini_set('iconv.output_encoding', 'cp866');
        ini_set('iconv.internal_encoding', 'UTF-8');
        // -- -- --

        // параметры http-запроса
        $request_params = array('method' => 'GET');

        // этим запросом получаем индентификатор сессии, который будем использовать при последующих запросах к сервису
        // url ссылается на страницу входа, но, по сути, можно использовать любую другую
        // в сервисе в url содержатся двоеточия - это разделитель ключа и значения параметра
        $loginUrl = 'http://www.marinetraffic.com/ru/users/ajax_user_menu/home:1';

        $requrest = new \HTTP_Request($loginUrl, $request_params);

        // использование прокси: http://pear.php.net/manual/ru/package.http.http-request.proxy-auth.php
        //$requrest->setProxy('proxy.example.com', 8080, 'johndoe', 'foo');

        // проверка работы скрипта через прокси из списка: http://proxylist.hidemyass.com/2
        //$requrest->setProxy('217.175.34.170', 8080);

        $requrest->sendRequest();
        $requrest->disconnect();
        $cookies = $requrest->getResponseCookies();
        $session_id = $cookies[0]['value'];

        $requrestUrl = "{$url}/sw_x:{$sw_x}/sw_y:{$sw_y}/ne_x:{$ne_x}/ne_y:{$ne_y}/zoom:{$zoom}/fleet:/station:0";

        // бесконечный цикл для сбора данных
        while (true) {
            // -- делаем запрос на получение данных
            $requrest = new \HTTP_Request($requrestUrl, $request_params);
            $requrest->addHeader('Referer', $requrestUrl);
            $requrest->addCookie('CAKEPHP', $session_id);
            $requrest->sendRequest();
            $requrest->disconnect();
            // -- -- --

            // -- пропускаем итерацию при недолжном ответе
            if ($requrest->getResponseCode() !== 200 || empty($requrest->getResponseBody())) {
                static::log('Сервер сервиса вернул ошибку или пустые данные (возможно сервис недоступен).');
                static::log('Пробую повторно.');
                continue;
            }
            // -- -- --

            // -- декодируем json-объект в массив
            $decodeResult = json_decode($requrest->getResponseBody());
            if (($decodeResult instanceof \stdClass) === false) {
                static::log('Декодированные данные оказались другого типа (нужно менять код программы).');
                static::log('Выхожу.');
                return;
            } elseif (!isset($decodeResult->data->rows) || !is_array($decodeResult->data->rows)) {
                static::log('Декодированные данные о суднах и их движении имеют другой тип или изменился формат данных (нужно менять код программы).');
                static::log('Выхожу.');
                return;
            } else {
                static::log('Всего будет обработано ' . count($decodeResult->data->rows) . ' записей.');
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
                    static::log('Ошибка при загрузки в прокси-объект данных, полученных из запроса: возможно изменился формат данных.');
                    static::log('Выхожу.');
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
                        static::log('Добавлено новое судно: ' . $marine->name);
                    } else {
                        static::log('Неудалось добавить новое судно из-за ошибок:');
                        static::log(print_r($marine->getErrors(), true));
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
                        static::log('Добавлено положение судна: ' . $marine->name);
                    } else {
                        static::log('Неудалось добавить положение судна из-за ошибок: ');
                        static::log(print_r($track->getErrors(), true));
                    }
                }
                // -- -- --
            }

            // -- сообщаем о законченной итерации и делаем паузу
            static::log('Закончена итерация #: ' . (static::$iteration++));
            static::log('Жду ' . $sleep_seconds . ' секунд...');
            sleep($sleep_seconds);
            // -- -- --
        }
    }

    /**
     * Добавить в лог сообщение (вывести на консоль).
     *
     * @param string $message
     */
    private static function log($message) {
        echo $message . PHP_EOL;
        ob_flush();
    }
}