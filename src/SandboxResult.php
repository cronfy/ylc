<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 06.01.17
 * Time: 11:28
 */

namespace cronfy\ylc;

use yii\base\Object;

class SandboxResult extends Object {

    public
        $filled,

        $exception,

        $current,
        $breadcrumbs,
        $assets,

        $content,

        $h1,

        $keywords,
        $title,
        $description,
        $canonical,
        $nofollow
    ;

}
