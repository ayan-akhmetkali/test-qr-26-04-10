<?php

namespace app\controllers;

use app\services\Exception\LinkNotFoundException;
use app\services\RedirectService;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class RedirectController extends Controller
{
    public function __construct(
        $id,
        $module,
        private RedirectService $redirectService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionGo(string $code): Response
    {
        $request = Yii::$app->request;

        try {
            $targetUrl = $this->redirectService->resolveAndTrack(
                shortCode: $code,
                ip: $request->userIP ?? '0.0.0.0',
                userAgent: $request->userAgent,
                referer: $request->referrer
            );
        } catch (LinkNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), 0, $exception);
        }

        return $this->redirect($targetUrl, 302);
    }
}
