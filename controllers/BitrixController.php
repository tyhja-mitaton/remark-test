<?php

namespace app\controllers;

use Bitrix24\SDK\Core\ApiLevelErrorHandler;
use Bitrix24\SDK\Core\Batch;
use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Infrastructure\HttpClient\RequestId\DefaultRequestIdGenerator;
use Bitrix24\SDK\Services\CRM\Contact\Service\Contact;
use Bitrix24\SDK\Tests\Unit\Stubs\NullCore;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpClient\HttpClient;
use yii\helpers\ArrayHelper;


class BitrixController extends \yii\rest\Controller
{

    public function actionTest()
    {
        /*$obB24App = new \Bitrix24\Bitrix24(false, $log);
// $obB24App->setApplicationScope(array("crm", "users"));
        $obB24App->setApplicationId("appid");
        $obB24App->setApplicationSecret("appsecret");

// set user-specific settings
        $obB24App->setDomain($_REQUEST['domain']);
        $obB24App->setMemberId($_REQUEST['memberid']);
        $obB24App->setAccessToken($_REQUEST['auth']);
// $obB24App->setRefreshToken($arParams['REFRESH_ID']);

        $arContacts = [];
        $obB24App->addBatchCall('crm.contact.list', [
            'select' => ['ID', 'NAME', 'LAST_NAME'], 'order' => ['ID' => 'ASC'], 'filter' => ["ID" => $arContactsID]
        ], function ($result) use ($obB24App, &$arContacts) {
            // save first page
            foreach ($result['result'] as $contact) {
                $arContacts[$contact['ID']] = $contact;
            }
            // add calls for subsequent pages
            for ($i = $result['next']; $i < $result['total']; $i += $result['next']) {
                $obB24App->addBatchCall('crm.contact.list', [
                    'start' => $i, 'select' => ['ID', 'NAME', 'LAST_NAME'], 'order' => ['ID' => 'ASC'], 'filter' => ["ID" => $arContactsID]
                ], function ($result) use (&$arContacts) {
                    // save subsequent page
                    foreach ($result['result'] as $contact) {
                        $arContacts[$contact['ID']] = $contact;
                    }
                });
            }
        });
        $obB24App->processBatchCalls();

        print_r($arContacts);*/
    }

    public function actionCompanies()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $apiClient = $this->getApiClient('https://b24-9etx58.bitrix24.ru/rest/1/tv36vfdxhz0jyzgg');

        $response = $apiClient->getResponse('crm.company.list', [
            'order' => ['TITLE' => 'ASC'],
            'filter' => [],
            'select' => ["ID", "TITLE", "COMPANY_TYPE"],
            //'start' => 0,
            ]);
        $response = json_decode($response->getContent(), true);

        return $response;
    }

    public function actionContacts()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $apiClient = $this->getApiClient('https://b24-9etx58.bitrix24.ru/rest/1/917a284giwnj3w0i/');

        $response = $apiClient->getResponse('crm.contact.list', [
            'filter' => ['LAST_NAME' => 'Иванов'],
            'select' => ["ID"],
        ]);
        $response = json_decode($response->getContent(), true);

        return !empty($response['result']) ? $response['result'][0]['ID'] : 'Ничего не найдено';
    }

    public function actionDeals()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $apiClientContacts = $this->getApiClient('https://b24-9etx58.bitrix24.ru/rest/1/917a284giwnj3w0i/');
        $responseContacts = $apiClientContacts->getResponse('crm.contact.list', [
            'filter' => ['LAST_NAME' => 'Иванов'],
            'select' => ["ID"],
        ]);
        $responseContacts = json_decode($responseContacts->getContent(), true);//new Batch()

        if(empty($responseContacts['result'])) {
            return 'Ничего не найдено';
        }

        $apiClient = $this->getApiClient('https://b24-9etx58.bitrix24.ru/rest/1/imkariew9z1ptfph/');

        $response = $apiClient->getResponse('crm.deal.list', [
            'filter' => ['CONTACT_ID' => array_keys(ArrayHelper::index($responseContacts['result'], 'ID'))],
            'select' => ["ID"],
        ]);
        $response = json_decode($response->getContent(), true);

        return !empty($response['result']) ? $response['result'][0]['ID'] : 'Ничего не найдено';
    }

    /*public function actionDeal()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $apiClientContacts = $this->getApiClient('https://b24-9etx58.bitrix24.ru/rest/1/917a284giwnj3w0i/');

        $log = new Logger('name');
        $log->pushHandler(new StreamHandler('b24-api-client-debug.log', Level::Debug));

        $batch = new Batch(new NullCore(), $log);
        $generator = $batch->getTraversableList('crm.contact.list', [], [], []);
    }*/

    public function getApiClient($webhookUrl)
    {
        $log = new Logger('name');
        $log->pushHandler(new StreamHandler('b24-api-client-debug.log', Level::Debug));

        $client = HttpClient::create();

        $credentials = new \Bitrix24\SDK\Core\Credentials\Credentials(
            new \Bitrix24\SDK\Core\Credentials\WebhookUrl($webhookUrl),
            null,
            null,
            null//'https://b24-9etx58.bitrix24.ru/rest/1/tsewnaompkafywx4/profile.json'
        );

        return new \Bitrix24\SDK\Core\ApiClient($credentials, $client, new DefaultRequestIdGenerator(), $log);
    }

    public static function getCompanies() {
        $start = 0;
        $finish = FALSE;
        $result = [];

        while (!$finish) {
            $response = \Yii::$app->bitrix24->admin()->call('crm.company.list', [
                'order' => ['TITLE' => 'ASC'],
                'filter' => [],
                'select' => ["ID", "TITLE", "COMPANY_TYPE"],
                'start' => $start,
            ]);
            if ($response['next']) {
                $start = $response['next'];
            }
            else {
                $finish = TRUE;
            }
            $result = array_merge($result, $response['result']);
        }

        return $result;

    }

}