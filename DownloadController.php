<?php
/**
 * программа для сбора данных о движениях судов с сервиса marinetraffic.com
 * разработана в академических целях, использовать на свой страх и риск
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

use yii\console\Controller;

// todo: разместить библиотеки в vendor
set_include_path(
		get_include_path().PATH_SEPARATOR.
		dirname(__FILE__).'/lib'.PATH_SEPARATOR.
		dirname(__FILE__).'/lib/HTTP'
);

// todo: библиотека устарела
require_once 'Request.php';

/**
 * контроллер загрузки данных
 * @todo проанализировать результаты - что-то не то со скоростью и пр.
 */
class DownloadController extends Controller
{
	/**
	 * описание json-объекта, возвращаемого сервисом
	 * если формат изменится сервисом, то нужно будет отредактировать это описание и описание моделей классов
	 * 
	 * @var array
	 */
	private static $json_map = array(
		0 => 'identifier',		// 345901
		1 => 'lon',				// 43.08045
		2 => 'lat',				// 132.3173
		3 => 'name',			// GARMONIYA
		4 => 'type',			// 7
		5 => 'course',			// 511
		6 => 'speed',			// 0
		7 => 'flag',			// RU
		8 => 'mmsi',			// 273312860,
		9 => 'length',			// 103
		10 => 'age',			// 21
		11 => 'col11',			// 18
		12 => 'col12',			// 0
		13 => 'col13',			// 0
		14 => 'col14',			// 0
		15 => 'col15',			// 0
		16 => 'port',			// YUZH LIFLYANDIYA
	);
	
    public function actionIndex($url = null, $sw_x = 130.0, $sw_y = 43.0, $ne_x = 133.0, $ne_y = 43.5, $zoom = 12, $time_zone = 'Asia/Vladivostok', $sleep_seconds = 10)
    {	
		// устанавливаем временную зону по-умолчанию
		date_default_timezone_set($time_zone);

		// параметры http-запроса
		$request_params = array('method' => 'GET');
		
		// этим запросом получаем индентификатор сессии, который будем использовать при последующих запросах к сервису
		// url ссылается на страницу входа, но, по сути, можно использовать любую другую
		// в сервисе в url содержатся двоеточия - это разделитель ключа и значения параметра
		$url = 'http://www.marinetraffic.com/ru/users/ajax_user_menu/home:1';
		$requrest = new \HTTP_Request($url, $request_params);
		$requrest->sendRequest();
		$requrest->disconnect();
		$cookies = $requrest->getResponseCookies();
		$session_id = $cookies[0]['value'];

		// бесконечный цикл для сбора данных
		$url = "http://www.marinetraffic.com/map/getjson/sw_x:{$sw_x}/sw_y:{$sw_y}/ne_x:{$ne_x}/ne_y:{$ne_y}/zoom:{$zoom}/fleet:/station:0";
		while (true) {
			// делаем запрос на получение данных
			$requrest = new \HTTP_Request($url, $request_params);
			$requrest->addHeader('Referer', $url);
			$requrest->addCookie('CAKEPHP', $session_id);
			$requrest->sendRequest();
			$requrest->disconnect();
			
			// пропускаем итерацию при недолжном ответе
			if ($requrest->getResponseCode() !== 200 || empty($requrest->getResponseBody()))
				continue;
			
			// декодируем json-объект в массив
			$data = json_decode($requrest->getResponseBody());
			if (!is_array($data))
				continue;
			
			// перебираем массив и сохраняем данные в базу
			foreach ($data as $marine_data) {
				// в ответ могут быть включены не только данные о движениях судна - они нас не интересуют
				if (count($marine_data) != count(self::$json_map))
					continue;
				
				// получим ассоциативный массив данных
				$data = array_combine(array_values(self::$json_map), $marine_data);
				
				// идентифицируем судно
				$marine = \app\models\Marine::findOne(['mmsi' => $data['mmsi']]);
				
				// если судно отсутствует в базе, то добавляем его
				if (!$marine) {
					$marine = new \app\models\Marine();
					$marine->setAttributes($data);
					if ($marine->save(false)) {
						echo 'M';
					}
					else {
						echo 'X';
						// todo: не выводить на консоль, а сохранять в лог сообщение об ошибке
						print_r($marine->getErrors());
					}
				}
				
				// сохраняем положение судна
				if ($marine->id_marine)
				{
					$track = new \app\models\Track();
					$track->setAttributes($data);
					$track->id_marine = $marine->id_marine;
					$track->date_add = date('Y-m-d_H:i');
					if ($track->save(false))
						echo 'T';
					else {
						echo 'X';							
						// todo: не выводить на консоль, а сохранять в лог сообщение об ошибке
						print_r($track->getErrors());
					}
				}
			}
			
			// пауза между запросами
			sleep($sleep_seconds);
		}		
    }
}