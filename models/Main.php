<?php

use AmoCRM\Client;

/**
 * Class Main
 */
class Main extends Model
{
    const AMO_SUBDOMAIN = 'new5a1712a14dc5f';
    const AMO_LOGIN     = 'alertius777@gmail.com';
    const AMO_HASH      = 'c22d4d71892b8c75c27f59099e138bcd';
    const AMO_LEAD_NAME = 'Заявка с сайта';

    const AMO_TASK_TEXT              = 'Перезвонить клиенту';
    const AMO_TASK_ELEMENT_TYPE_LEAD = 2; // Сделка
    const AMO_TASK_TYPE              = 1; // Звонок

    const MAIL_SUBJECT = 'Заявка с сайта';

    /** @var Client $amo */
    private static $amo;

    /** @var int $resposibleUserId */
    private static $resposibleUserId;

    /**
     * @param array $post_data
     */
    public static function sendForm(array $post_data)
    {
        self::$amo = new \AmoCRM\Client(self::AMO_SUBDOMAIN, self::AMO_LOGIN, self::AMO_HASH);
        $contactId = self::getContact($post_data);
        $leadId    = self::createLead();
        self::linkLeadWithContact($contactId, $leadId);
        self::addTask($leadId);
        self::sendMailToAdmin($post_data);
    }

    /**
     * @param array $post_data
     *
     * @return int
     */
    protected static function getContact(array $post_data) : int
    {
        $account = self::$amo->account->apiCurrent();

        $user = self::$amo->contact->apiList([
            'query' => $post_data['email'],
        ]);

        if (!$user) {
            $user = self::$amo->contact->apiList([
                'query' => $post_data['phone'],
            ]);

            if (!$user) {
                $customFieldEmailId = $account['custom_fields']['contacts'][2]['id'];
                $customFieldPhoneId = $account['custom_fields']['contacts'][1]['id'];

                self::$resposibleUserId         = self::getLessBusyManager();
                $contact                        = self::$amo->contact;
                $contact['name']                = (array_key_exists('name', $post_data)) ? $post_data['name'] : '';
                $contact['responsible_user_id'] = self::$resposibleUserId;
                $contact->addCustomField($customFieldEmailId, $post_data['email'], 'WORK');
                $contact->addCustomField($customFieldPhoneId, $post_data['phone'], 'WORK');

                return $contact->apiAdd();
            }

            self::$resposibleUserId = $user[0]['responsible_user_id'];
            return $user[0]['id'];
        }

        self::$resposibleUserId = $user[0]['responsible_user_id'];
        return $user[0]['id'];
    }

    /**
     * @return int
     */
    protected static function getLessBusyManager() : int
    {
        $account = self::$amo->account->apiCurrent();
        $users   = $account['users'];
        $userLeads = [];

        foreach ($users as $user) {
            if ($user['is_admin'] != 'Y') {
                $leads = self::$amo->lead->apiList([
                    'responsible_user_id' => $user['id'],
                ]);
                $userLeadsTmp = [];

                foreach ($leads as $lead) {
                    $dateCreate = $lead['date_create'];
                    $todayBegin = mktime(0,0,0);
                    $todayEnd   = mktime(0,0,0) + 86400;

                    if ($dateCreate >= $todayBegin && $dateCreate <= $todayEnd) {
                        if (!array_key_exists($lead['main_contact_id'], $userLeads)) {
                            $userLeadsTmp[$lead['main_contact_id']] = 1;
                        }
                    }
                }
                $userLeads[$user['id']] = count($userLeadsTmp);
            }
        }

        foreach ($userLeads as $key => $value) {
            $firstIndex = [$key => $value];
            break;
        }

        $index = key($firstIndex);
        $min   = reset($firstIndex);
        foreach ($userLeads as $key => $value) {
            if ($value < $min) {
                $index = $key;
                $min = $value;
            }
        }
        return $index;
    }

    /**
     * @return int
     */
    protected static function createLead() : int
    {
        $account      = self::$amo->account->apiCurrent();
        $leadStatusId = $account['leads_statuses'][0]['id'];

        $lead                        = self::$amo->lead;
        $lead['name']                = self::AMO_LEAD_NAME;
        $lead['status_id']           = $leadStatusId;
        $lead['responsible_user_id'] = self::$resposibleUserId;

        return $lead->apiAdd();
    }

    /**
     * @param $contactId
     * @param $leadId
     *
     * @return bool
     */
    protected static function linkLeadWithContact(int $contactId, int $leadId) : bool
    {
        $link            = self::$amo->links;
        $link['from']    = 'leads';
        $link['from_id'] = $leadId;
        $link['to']      = 'contacts';
        $link['to_id']   = $contactId;

        return $link->apiLink();
    }

    /**
     * @param int $leadId
     *
     * @return int
     */
    protected static function addTask(int $leadId) : int
    {
        $task                        = self::$amo->task;
        $task['element_id']          = $leadId;
        $task['element_type']        = self::AMO_TASK_ELEMENT_TYPE_LEAD;
        $task['task_type']           = 1;
        $task['text']                = self::AMO_TASK_TEXT;
        $task['responsible_user_id'] = self::$resposibleUserId;
        $task['complete_till']       = '+1 DAY';

        return $task->apiAdd();
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected static function sendMailToAdmin(array $data) : bool
    {
        $account = self::$amo->account->apiCurrent();

        $admin = [];
        foreach ($account['users'] as $user) {
            if ($user['is_admin'] == 'Y') {
                $admin = $user;
            }
        }

        $message = '
            Оформлена заявка с сайта
            Имя: ' . $data['name'] . '
            Телефон: ' . $data['phone'] . '
            Email: ' . $data['email'] . '
        ';

        return mail($admin['login'], self::MAIL_SUBJECT, $message);
    }
}