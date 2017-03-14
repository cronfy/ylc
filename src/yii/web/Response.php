<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 09.06.16
 * Time: 10:54
 */

namespace cronfy\ylc\yii\web;

/**
 * Class Response
 * @package lik\ylc\yii\web
 *
 * yii\web\response extension, allows to send cookies manually
 */

class Response extends \yii\web\Response {

    public function sendCookies() {
        call_user_func_array(['parent',  __FUNCTION__], func_get_args());
    }

}
