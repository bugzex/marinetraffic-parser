<?php

namespace app\models;

use yii\base\Model;

/**
 * Прокси-модель для хранения данных в виде объекта, возвращаемых сервисом, чтобы было удобно их использовать.
 * Видимо все данные (кроме идентификатора, названия судна, флага), которые дает сервис - приблизительные, т.е. рассчитанные.
 */
class MarinetrafficResult extends Model
{
    /** @var int идентификатор судна по версии Marinetraffic */
    public $ship_id;

    /** @var string наименование судна */
    public $shipname;

    /** @var int тип судна */
    public $shiptype;

    /** @var string флаг судна (ISO) */
    public $flag;

    /** @var int длина судна */
    public $length;

    /** @var int ширина судна */
    public $width;

    /** @var string порт (парковка) */
    public $destination;

    /** @var float широта */
    public $lat;

    /** @var float долгота */
    public $lon;

    /** @var int курс */
    public $course;

    /** @var int неизвестное поле (похоже на курс, но уже есть такое поле course) */
    public $heading;

    /** @var int скорость */
    public $speed;

    /** @var int вероятно col15 по старой схеме */
    public $rot;

    /** @var int вероятно col12 по старой схеме */
    public $l_fore;

    /** @var int вероятно col13 по старой схеме */
    public $w_left;

    /** @var int возраст данных (скорее всего, предыдущее поле называлось age) */
    public $elapsed;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['ship_id', 'integer'],
            ['shiptype', 'integer'],
            ['length', 'integer'],
            ['width', 'integer'],
            ['course', 'integer'],
            ['heading', 'integer'],
            ['speed', 'integer'],
            ['rot', 'integer'],
            ['l_fore', 'integer'],
            ['w_left', 'integer'],
            ['elapsed', 'integer'],
            ['lat', 'number'],
            ['lon', 'number'],
            ['shipname', 'string'],
            ['flag', 'string'],
            ['destination', 'string'],
        ];
    }
}