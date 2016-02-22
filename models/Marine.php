<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "marine".
 *
 * @property integer $id_marine
 * @property integer $identifier
 * @property integer $mmsi
 * @property string $name
 * @property string $flag
 * @property integer $type
 * @property integer $length
 * @property string $port
 * @property integer $col11
 * @property integer $col12
 * @property integer $col13
 * @property integer $col14
 * @property integer $col15
 * @property integer $col17
 * @property integer $col18
 *
 * @property-read Track[] $tracks
 */
class Marine extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'marine';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['identifier', 'mmsi', 'type', 'length', 'col11', 'col12', 'col13', 'col14', 'col15'], 'integer'],
            [['name', 'port'], 'string', 'max' => 45],
            [['flag'], 'string', 'max' => 3]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_marine' => 'Id Marine',
            'identifier' => 'Identifier',
            'mmsi' => 'Mmsi',
            'name' => 'Name',
            'flag' => 'Flag',
            'type' => 'Type',
            'length' => 'Length',
            'port' => 'Port',
            'col11' => 'Col11',
            'col12' => 'Col12',
            'col13' => 'Col13',
            'col14' => 'Col14',
            'col15' => 'Col15',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTracks()
    {
        return $this->hasMany(Track::className(), ['id_marine' => 'id_marine']);
    }
}
