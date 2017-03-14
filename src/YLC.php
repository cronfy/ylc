<?php

namespace cronfy\ylc;

use cronfy\ylc\yii\web\Response;
use cronfy\ylc\yii\web\View;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownMethodException;
use yii\helpers\Url;
use yii\web\UrlManager;

class YLC {

    public static function runYiiInSandbox(SandboxResult $sandboxResult, $catch_exceptions = null) {
        if (!method_exists(Yii::$app->view, 'getPageAssets')) {
            throw new InvalidConfigException("View component must be an instance of " . View::className() . " or support getPageAssets() method.");
        }

        if (!$catch_exceptions) {
            $catch_exceptions = [
                \yii\web\NotFoundHttpException::class
            ];
        }

        ob_start();

        try {
            \Yii::$app->on(\yii\base\Controller::EVENT_BEFORE_ACTION, function ($event) use ($sandboxResult) {
                /**
                 * In legacy code we may need to know, which controller/action were run in Yii.
                 * Let's remember them.
                 *
                 * @var $event \yii\base\ActionEvent
                 */
                $sandboxResult->current['action'] = $event->action;
                $sandboxResult->current['controller'] = $event->action->controller;
            });
            \Yii::$app->run();
        } catch (\Exception $e) {
            $throw_exception = array_reduce($catch_exceptions, function ($carry, $exception_class) use ($e) {
                return $carry ? !is_a($e, $exception_class) : false;
            }, true);
            if ($throw_exception) {
                throw $e;
            }

            $sandboxResult->exception = $e;
        }

        $sandboxResult->assets = Yii::$app->view->getPageAssets();
        $sandboxResult->content = ob_get_contents();
        $sandboxResult->filled = true;

        ob_end_clean();
    }

    public static function tryYii() {
        try {
            \Yii::$app->run();
            // request successfully processed with Yii, exiting
            die();
        } catch (\yii\web\NotFoundHttpException $e) {
            // if NotFoundHttpException was caused by routing error,
            // then current request URL just is not known to Yii, but it still
            // may be recognized by legacy code.
            if ($orig_e = $e->getPrevious() and  get_class($orig_e) == 'yii\base\InvalidRouteException') {
                // do nothing: legacy code should take the request
            } else {
                // Other type of exception - throw it up
                throw $e;
            }
        } catch (GoToLegacyException $e) {
            // do nothing: legacy code should take the request
        }
    }

    public static function getBypassApplicationConfig() {
        return require(__DIR__ . '/apps/bypass/config/global.php');
    }

    /**
     * @param UrlManager $urlManager the UrlManager to create URL
     * @param string|array $route use a string to represent a route (e.g. `index`, `site/index`),
     * or an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     * @param boolean|string $scheme the URI scheme to use in the generated URL:
     *
     * - `false` (default): generating a relative URL.
     * - `true`: returning an absolute base URL whose scheme is the same as that in [[\yii\web\UrlManager::hostInfo]].
     * - string: generating an absolute URL with the specified scheme (either `http` or `https`).
     *
     * @return string the generated URL
     * @return string
     */
    public static function urlToRoute($urlManager, $route, $scheme = null) {
        $route = (array) $route;

        if (strpos('/', $route[0]) !== 0) {
            // route must be absolute to avoid 'No active controller'
            // exception from Url::normalizeRoute()
            $route[0] = '/' . $route[0];
        }

        $prevManager = Url::$urlManager;
        Url::$urlManager = $urlManager;
        $url = Url::toRoute($route, $scheme);
        Url::$urlManager = $prevManager;

        return $url;
    }

    /**
     * Can be used to manually send cookies.
     *
     * When you use Html::csrfMetaTags() in legacy template, Yii will generate CSRF token,
     * but will not send it, as Yii::$app->response->send() is not called.

     * You can call this method manually to send CSRF token and other cookies before
     * sending content from legacy.
     *
     * @throws InvalidConfigException
     */
    public static function sendCookies() {
        $response = Yii::$app->response;
        try {
            $response->sendCookies();
        } catch (UnknownMethodException $e) {
            throw new InvalidConfigException("Response::sendCookies() must be public, use " . Response::class . " instead of " . get_class($response));
        }
    }

}
