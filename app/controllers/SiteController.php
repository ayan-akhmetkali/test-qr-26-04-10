<?php

namespace app\controllers;

use app\models\ShortLinkForm;
use app\services\Exception\LinkCreationException;
use app\services\ShortLinkService;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

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
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
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
            return [
                'success' => false,
                'message' => 'Некорректный URL.',
                'errors' => $model->getErrors(),
            ];
        }

        try {
            $result = $this->shortLinkService->create($model->url);
        } catch (LinkCreationException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        } catch (\Throwable $exception) {
            Yii::error($exception, __METHOD__);

            return [
                'success' => false,
                'message' => 'Не удалось создать короткую ссылку.',
            ];
        }

        return [
            'success' => true,
            'short_url' => $result->shortUrl,
            'qr_url' => $result->qrUrl,
            'short_code' => $result->shortCode,
        ];
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
