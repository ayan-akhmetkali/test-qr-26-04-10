<?php

namespace app\controllers;

use app\models\ShortLinkForm;
use app\services\Exception\LinkCreationException;
use app\services\ShortLinkService;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;

class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private ShortLinkService $shortLinkService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create-link' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index', [
            'model' => new ShortLinkForm(),
        ]);
    }

    public function actionCreateLink(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new ShortLinkForm();
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            return $this->errorResponse('Некорректный URL.', $model->getErrors());
        }

        try {
            $result = $this->shortLinkService->create($model->url);
        } catch (LinkCreationException $exception) {
            return $this->errorResponse($exception->getMessage());
        } catch (\Throwable $exception) {
            Yii::error($exception, __METHOD__);

            return $this->errorResponse('Не удалось создать короткую ссылку.');
        }

        return [
            'success' => true,
            'short_url' => $result->shortUrl,
            'qr_url' => $result->qrUrl,
            'short_code' => $result->shortCode,
        ];
    }

    private function errorResponse(string $message, array $errors = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];
    }

}
