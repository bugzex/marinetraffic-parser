<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "track".
 *
 * @property integer $id_track
 * @property integer $id_marine
 * @property double $lat
 * @property double $lon
 * @property integer $speed
 * @property integer $course
 * @property integer $age
 * @property string $date_add
 *
 * @property-read Marine $marine
 */
class Track extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'track';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_marine'], 'required'],
            [['id_marine', 'speed', 'course', 'age'], 'integer'],
            [['lat', 'lon'], 'number'],
            [['date_add'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_track' => 'Id Track',
            'id_marine' => 'Id Marine',
            'lat' => 'Lat',
            'lon' => 'Lon',
            'speed' => 'Speed',
            'course' => 'Course',
            'age' => 'Age',
            'date_add' => 'Date Add',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarine()
    {
        return $this->hasOne(Marine::className(), ['id_marine' => 'id_marine']);
    }
}
