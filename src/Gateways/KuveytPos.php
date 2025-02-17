<?php

namespace Mews\Pos\Gateways;

use GuzzleHttp\Client;
use Mews\Pos\Entity\Account\KuveytPosAccount;
use Mews\Pos\Entity\Card\CreditCardKuveytPos;
use Mews\Pos\Exceptions\NotImplementedException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class KuveytPos
 */
class KuveytPos extends AbstractGateway
{

    const LANG_TR = 'tr';
    const LANG_EN = 'en';

    /**
     * API version
     */
    const API_VERSION = '1.0.0';

    /**
     * @const string
     */
    public const NAME = 'KuveytPos';

    /**
     * Response Codes
     *
     * @var array
     */
    protected $codes = [
        "001" =>"OTORİZASYON VERİLDİ",
        "000" =>"KARTI VEREN BANKAYI ARA - LİM.",
        "002" =>"KARTI VEREN BANKAYI ARAYINIZ.",
        "003" =>"GEÇERSİZ ÜYE İŞYERİ",
        "004" =>"Karta El Koyunuz",
        "005" =>"İŞLEM ONAYLANMADI   ",
        "009" =>"TEKRAR DENEYİNİZ    ",
        "011" =>"VİP-İşlem için onay verildi ",
        "012" =>"Geçersiz İşlem       ",
        "013" =>"GEÇERSİZ İŞLEM TUTARI     ",
        "014" =>"Geçersiz Kart Numarası      ",
        "015" =>"Kart Veren Banka Tanımsız    ",
        "033" =>"Vade Sonu geçmiş-Karta El koy   ",
        "034" =>"Sahtekarlık- Karta el Koyunuz.  ",
        "036" =>"KISITLI KART. KARTA EL KOYUNUZ  ",
        "037" =>"GÜVENLİĞİ UYARINIZ. KARTA EL K  ",
        "038" =>"Hatalı Şifre-Karta El Koy.      ",
        "041" =>"Kayıp Kart- Karta el Koy        ",
        "043" =>"Çalıntı Kart-Karta el koyunuz   ",
        "051" =>"Bakiyesi-Kredi limiti Yetersiz  ",
        "053" =>"DÖVİZ HESABI BULUNAMADI         ",
        "054" =>"Vade Sonu Geçmiş Kart           ",
        "055" =>"Hatalı Kart Şifresi             ",
        "056" =>"Kart Tanımlı Değil.             ",
        "057" =>"İŞLEM TİPİNE İZİN YOK.          ",
        "058" =>"İşlem Tipi Terminale Kapalı     ",
        "059" =>"Sahtekarlık Şüphesi             ",
        "061" =>"Para Çekme  Tutar Limiti aşıld  ",
        "062" =>"KISITLANMIS KART.               ",
        "063" =>"Güvenlik İhlali                 ",
        "065" =>"Para  Çekme Adet Limiti Aşıldı  ",
        "066" =>"İŞLEMİ REDDEDİNİZ. GÜVENLİĞİ U  ",
        "067" =>"BU HESAPTA HİÇBİR İŞLEM YAPILA  ",
        "068" =>"TANIMSIZ ŞUBE                   ",
        "075" =>"Şifre Deneme Sayısı Aşıldı      ",
        "076" =>"Şifreler Uyuşmuyor-Key          ",
        "077" =>"ŞİFRE SCRIPT TALEBİ REDDEDİLDİ  ",
        "078" =>"ŞİFRE GÜVENİLİR BULUNMADI       ",
        "079" =>"ARQC KONTROLÜ BAŞARISIZ         ",
        "085" =>"ŞİFRE DEĞİŞİKLİĞİ/YÜKLEME ONAY  ",
        "088" =>"İŞLEM ŞÜPHELİ TAMAMLANDI.KONTR  ",
        "089" =>"EK KART İLE BU İŞLEM YAPILAMAZ  ",
        "090" =>"Gün Sonu Devam Ediyor           ",
        "091" =>"Kartı  Veren Banka Hizmetdışı   ",
        "092" =>"KART VEREN BANKA TANIMLI DEGIL  ",
        "096" =>"SİSTEM ARIZASI        "
    ];

    /**
     * Transaction Types
     *
     * @var array
     */
    protected $types = [
        self::TX_PAY => 'Sale',
        self::TX_PRE_PAY => 'preauth',
        self::TX_POST_PAY => 'postauth',
        self::TX_CANCEL => 'void',
        self::TX_REFUND => 'refund',
        self::TX_HISTORY => 'orderhistoryinq',
        self::TX_STATUS => 'orderinq',
    ];


    /**
     * currency mapping
     *
     * @var array
     */
    protected $currencies = [
        'TRY'       => '0949',
        'USD'       => '0840',
        'EUR'       => '0978',
        'GBP'       => '0826',
        'JPY'       => '0392',
        'RUB'       => '0643',
    ];

    /**
     * @var KuveytPosAccount
     */
    protected $account;

    /**
     * @var CreditCardKuveytPos
     */
    protected $card;

    /**
     * KuveytPost constructor.
     *
     * @param array $config
     * @param KuveytPosAccount $account
     * @param array $currencies
     */
    public function __construct($config, $account, array $currencies = [])
    {
        parent::__construct($config, $account, $currencies);
    }

    /**
     * @return KuveytPosAccount
     */
    public function getAccount()
    {
        return $this->account;
    }


    /**
     * @return CreditCardKuveytPos|null
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @inheritDoc
     */
    public function createXML(array $data, $encoding = 'UTF-8'): string
    {
        $xml =  parent::createXML(['KuveytTurkVPosMessage' => $data], $encoding);
        $xml = str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n", '', $xml);
        $xml = str_replace("<KuveytTurkVPosMessage>", "<KuveytTurkVPosMessage xmlns:xsi=\"http://www.w3.org/2001/XMLSchemainstance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">", $xml);
        $xml = str_replace("\n", "", $xml);
        return $xml;
    }

    /**
     * @inheritDoc
     */
    public function createPostXML(array $data, $encoding = 'UTF-8'): string
    {
        $xml =  parent::createXML(['KuveytTurkVPosMessage' => $data], $encoding);
        $xml = str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n", '', $xml);
        $xml = str_replace("<KuveytTurkVPosMessage>", "<KuveytTurkVPosMessage xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">", $xml);
        $xml = str_replace("\n", "", $xml);
        return $xml;
    }

    /**
     * @inheritDoc
     */
    public function make3DPayment()
    {
        $request = Request::createFromGlobals();
        //TODO hash check
        if (in_array($request->get('mdstatus'), [1, 2, 3, 4])) {
            $contents = $this->create3DPaymentXML($request->request->all());
            $this->send($contents);
        }

        $this->response = $this->map3DPaymentData($request->request->all(), $this->data);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function make3DPayPayment()
    {
        $request = Request::createFromGlobals();

        $this->response = $this->map3DPayResponseData($request->request->all());

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function history(array $meta)
    {
        $xml = $this->createHistoryXML($meta);

        $this->send($xml);

        $this->response = $this->mapHistoryResponse($this->data);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function send($contents)
    {
        $client = new Client();
        $url = $this->getApiURL();
        if ($this->types[self::TX_POST_PAY] === $this->type) {
            $url = $this->get3DGatewayURL();
        }
        $response = $client->request('POST', $url, [
            'body'  => $contents,
        ], ['curl' => [
            CURLOPT_SSLVERSION => 6
        ]]);

        //$this->data = $this->XMLStringToObject($response->getBody()->getContents());
        $this->data = $response->getBody()->getContents();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get3DFormData()
    {
        $hashData = $this->create3DHash();

        $inputs = [
            'secure3dsecuritylevel' => $this->account->getModel() === '3d_pay' ? '3D_PAY' : '3D',
            'mode'                  => $this->getMode(),
            'apiversion'            => self::API_VERSION,
            'terminalprovuserid'    => $this->account->getUsername(),
            'terminaluserid'        => $this->account->getUsername(),
            'terminalmerchantid'    => $this->account->getClientId(),
            'txntype'               => $this->type,
            'txnamount'             => $this->order->amount,
            'txncurrencycode'       => $this->order->currency,
            'txninstallmentcount'   => $this->order->installment,
            'orderid'               => $this->order->id,
            'terminalid'            => $this->account->getTerminalId(),
            'successurl'            => $this->order->success_url,
            'errorurl'              => $this->order->fail_url,
            'customeremailaddress'  => isset($this->order->email) ? $this->order->email : null,
            'customeripaddress'     => $this->order->ip,
            'cardnumber'            => $this->card->getNumber(),
            'cardexpiredatemonth'   => $this->card->getExpireMonth(),
            'cardexpiredateyear'    => $this->card->getExpireYear(),
            'cardcvv2'              => $this->card->getCvv(),
            'secure3dhash'          => $hashData,
        ];

        return [
            'gateway'       => $this->get3DGatewayURL(),
            'inputs'        => $inputs,
        ];
    }

    /**
     * TODO
     * @inheritDoc
     */
    public function make3DHostPayment()
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function createRegularPaymentXML()
    {
        $requestData = [
            'APIVersion'        => self::API_VERSION,
            'OkUrl'             => $this->order->success_url,
            'FailUrl'           => $this->order->fail_url,
            'HashData'          => $this->createHashData(),
            'MerchantId'        => $this->account->getTerminalId(),
            'CustomerId'        => $this->account->getClientId(),
            'UserName'          => $this->account->getUsername(),
            'CardNumber'        => $this->card->getNumber(),
            'CardExpireDateYear'=> $this->card->getExpireYear(),
            'CardExpireDateMonth'=> $this->card->getExpireMonth(),
            'CardCVV2'          => $this->card->getCvv(),
            'CardHolderName'    => $this->card->getHolderName(),
            'CardType'          => $this->card->getType(),
            'BatchID'           => 0,
            'TransactionType'   => $this->types[self::TX_PAY],
            'InstallmentCount'  => 0,
            'Amount'            => $this->order->amount,
            'DisplayAmount'     => $this->order->amount,
            'CurrencyCode'      => $this->order->currency,
            'MerchantOrderId'   => $this->order->id,
            'TransactionSecurity'=>'3',
            ];

        return $this->createXML($requestData);
    }

    /**
     * @inheritDoc
     */
    public function createRegularPostXML()
    {
        $requestData = [
            'APIVersion'        => self::API_VERSION,
            'HashData'          => $this->create3DHash(),
            'MerchantId'        => $this->account->getTerminalId(),
            'CustomerId'        => $this->account->getClientId(),
            'UserName'          => $this->account->getUsername(),
            'TransactionType'   => $this->types[self::TX_PAY],
            'InstallmentCount'  => 0,
            'CurrencyCode'      => $this->order->currency,
            'Amount'            => $this->order->amount,
            'MerchantOrderId'   => $this->order->id,
            'TransactionSecurity'=>'3',
            'KuveytTurkVPosAdditionalData' => [
                'AdditionalData'  =>  [
                    'Key'   => 'MD',
                    'Data'  => $this->order->md
                ]
        ]
        ];

        return $this->createPostXML($requestData);
    }

    /**
     * @inheritDoc
     */
    public function create3DPaymentXML($responseData)
    {
        $requestData = [
            'APIVersion'        => self::API_VERSION,
            'OkUrl'             => $this->order->success_url,
            'FailUrl'           => $this->order->fail_url,
            'HashData'          => $this->createHashData(),
            'MerchantId'        => $this->account->getTerminalId(),
            'CustomerId'        => $this->account->getClientId(),
            'UserName'          => $this->account->getUsername(),
            'CardNumber'        => $this->card->getNumber(),
            'CardExpireDateYear'=> $this->card->getExpireYear(),
            'CardExpireDateMonth'=> $this->card->getExpireMonth(),
            'CardCVV2'          => $this->card->getCvv(),
            'CardHolderName'    => $this->card->getHolderName(),
            'CardType'          => $this->card->getType(),
            'BatchID'           => 0,
            'TransactionType'   => $this->types[self::TX_PAY],
            'InstallmentCount'  => 0,
            'Amount'            => $this->order->amount,
            'DisplayAmount'     => $this->order->amount,
            'CurrencyCode'      => $this->order->currency,
            'MerchantOrderId'   => $this->order->id,
            'TransactionSecurity'=>'3',
        ];

        return $this->createXML($requestData);
    }

    /**
     * @inheritDoc
     */
    public function createCancelXML()
    {
        $requestData = [
            'Mode'          => $this->getMode(),
            'Version'       => self::API_VERSION,
            'ChannelCode'   => '',
            'Terminal'      => [
                'ProvUserID'    => $this->account->getRefundUsername(),
                'UserID'        => $this->account->getRefundUsername(),
                'HashData'      => $this->createHashData(),
                'ID'            => $this->account->getTerminalId(),
                'MerchantID'    => $this->account->getClientId(),
            ],
            'Customer'      => [
                'IPAddress'     => $this->order->ip,
                'EmailAddress'  => $this->order->email,
            ],
            'Order'         => [
                'OrderID'   => $this->order->id,
                'GroupID'   => '',
            ],
            'Transaction'   => [
                'Type'                  => $this->types[self::TX_CANCEL],
                'InstallmentCnt'        => $this->order->installment,
                'Amount'                => $this->order->amount, //TODO we need this field here?
                'CurrencyCode'          => $this->order->currency,
                'CardholderPresentCode' => '0',
                'MotoInd'               => 'N',
                'OriginalRetrefNum'     => $this->order->ref_ret_num,
            ],
        ];

        return $this->createXML($requestData);
    }

    /**
     * @inheritDoc
     */
    public function createRefundXML()
    {
        $requestData = [
            'Mode'          => $this->getMode(),
            'Version'       => self::API_VERSION,
            'ChannelCode'   => '',
            'Terminal'      => [
                'ProvUserID'    => $this->account->getRefundUsername(),
                'UserID'        => $this->account->getRefundUsername(),
                'HashData'      => $this->createHashData(),
                'ID'            => $this->account->getTerminalId(),
                'MerchantID'    => $this->account->getClientId(),
            ],
            'Customer'      => [
                'IPAddress'     => $this->order->ip,
                'EmailAddress'  => $this->order->email,
            ],
            'Order'         => [
                'OrderID'   => $this->order->id,
                'GroupID'   => '',
            ],
            'Transaction'   => [
                'Type'                  => $this->types[self::TX_REFUND],
                'InstallmentCnt'        => $this->order->installment,
                'Amount'                => $this->order->amount,
                'CurrencyCode'          => $this->order->currency,
                'CardholderPresentCode' => '0',
                'MotoInd'               => 'N',
                'OriginalRetrefNum'     => $this->order->ref_ret_num,
            ],
        ];

        return $this->createXML($requestData);
    }

    /**
     * @inheritDoc
     */
    public function createHistoryXML($customQueryData)
    {
        $requestData = [
            'Mode'          => $this->getMode(),
            'Version'       => self::API_VERSION,
            'ChannelCode'   => '',
            'Terminal'      => [
                'ProvUserID'    => $this->account->getUsername(),
                'UserID'        => $this->account->getUsername(),
                'HashData'      => $this->createHashData(),
                'ID'            => $this->account->getTerminalId(),
                'MerchantID'    => $this->account->getClientId(),
            ],
            'Customer'      => [ //TODO we need this data?
                'IPAddress'     => $this->order->ip,
                'EmailAddress'  => $this->order->email,
            ],
            'Order'         => [
                'OrderID'   => $this->order->id,
                'GroupID'   => '',
            ],
            'Card'  => [
                'Number'        => '',
                'ExpireDate'    => '',
                'CVV2'          => '',
            ],
            'Transaction'   => [
                'Type'                  => $this->types[self::TX_HISTORY],
                'InstallmentCnt'        => $this->order->installment,
                'Amount'                => $this->order->amount,
                'CurrencyCode'          => $this->order->currency, //TODO we need it?
                'CardholderPresentCode' => '0',
                'MotoInd'               => 'N',
            ],
        ];

        return $this->createXML($requestData);
    }

    /**
     * @inheritDoc
     */
    public function createStatusXML()
    {
        $hashData = $this->createHashData();

        $requestData = [
            'Mode'          => $this->getMode(),
            'Version'       => self::API_VERSION,
            'ChannelCode'   => '',
            'Terminal'      => [
                'ProvUserID'    => $this->account->getUsername(),
                'UserID'        => $this->account->getUsername(),
                'HashData'      => $hashData,
                'ID'            => $this->account->getTerminalId(),
                'MerchantID'    => $this->account->getClientId(),
            ],
            'Customer'      => [ //TODO we need this data?
                'IPAddress'     => $this->order->ip,
                'EmailAddress'  => $this->order->email,
            ],
            'Order'         => [
                'OrderID'   => $this->order->id,
                'GroupID'   => '',
            ],
            'Card'  => [
                'Number'        => '',
                'ExpireDate'    => '',
                'CVV2'          => '',
            ],
            'Transaction'   => [
                'Type'                  => $this->types[self::TX_STATUS],
                'InstallmentCnt'        => $this->order->installment,
                'Amount'                => $this->order->amount,   //TODO we need it?
                'CurrencyCode'          => $this->order->currency, //TODO we need it?
                'CardholderPresentCode' => '0',
                'MotoInd'               => 'N',
            ],
        ];

        return $this->createXML($requestData);
    }

    /**
     * Make Hash Data
     *
     * @return string
     */
    public function createHashData()
    {
        //$HashedPassword = base64_encode(sha1($this->account->getPassword(),"ISO-8859-9")); //md5($Password);
        //return base64_encode(sha1($this->account->getTerminalId() . $this->order->id.$this->order-amount.$this->order->success_url.$this->order->fail_url.$this->account->getUsername().$HashedPassword , "ISO-8859-9"));
        $map = [
            $this->account->getTerminalId(),
            $this->order->id,
            $this->order->amount,
            $this->order->success_url,
            $this->order->fail_url,
            $this->account->getUsername(),
            $this->createSecurityData()
        ];

        return base64_encode(sha1(implode('', $map), "ISO-8859-9"));
    }


    /**
     * Make 3d Hash Data
     *
     * @return string
     */
    public function create3DHash()
    {
        $map = [
            $this->account->getTerminalId(),
            $this->order->id,
            $this->order->amount,
            $this->account->getUsername(),
            $this->createSecurityData()
        ];

        return base64_encode(sha1(implode('', $map), "ISO-8859-9"));
    }

    /**
     * Amount Formatter
     *
     * @param double $amount
     *
     * @return int
     */
    public static function amountFormat($amount)
    {
        return round($amount, 2) * 100;
    }

    /**
     * @return string
     */
    protected function getMode()
    {
        return !$this->isTestMode() ? 'PROD' : 'TEST';
    }

    /**
     * @inheritDoc
     */
    protected function map3DPaymentData($raw3DAuthResponseData, $rawPaymentResponseData)
    {
        $status = 'declined';
        $response = 'Declined';
        $procReturnCode = '99';
        $transactionSecurity = 'MPI fallback';

        if (in_array($raw3DAuthResponseData['mdstatus'], [1, 2, 3, 4])) {
            if ($raw3DAuthResponseData['mdstatus'] == '1') {
                $transactionSecurity = 'Full 3D Secure';
            } elseif (in_array($raw3DAuthResponseData['mdstatus'], [2, 3, 4])) {
                $transactionSecurity = 'Half 3D Secure';
            }

            if ($rawPaymentResponseData->Transaction->Response->ReasonCode === '00') {
                $response = 'Approved';
                $procReturnCode = $rawPaymentResponseData->Transaction->Response->ReasonCode;
                $status = 'approved';
            }
        }

        return (object) [
            'id'                    => isset($rawPaymentResponseData->Transaction->AuthCode) ? $this->printData($rawPaymentResponseData->Transaction->AuthCode) : null,
            'order_id'              => $raw3DAuthResponseData['oid'],
            'group_id'              => isset($rawPaymentResponseData->Transaction->SequenceNum) ? $this->printData($rawPaymentResponseData->Transaction->SequenceNum) : null,
            'auth_code'             => isset($rawPaymentResponseData->Transaction->AuthCode) ? $this->printData($rawPaymentResponseData->Transaction->AuthCode) : null,
            'host_ref_num'          => isset($rawPaymentResponseData->Transaction->RetrefNum) ? $this->printData($rawPaymentResponseData->Transaction->RetrefNum) : null,
            'ret_ref_num'           => isset($rawPaymentResponseData->Transaction->RetrefNum) ? $this->printData($rawPaymentResponseData->Transaction->RetrefNum) : null,
            'batch_num'             => isset($rawPaymentResponseData->Transaction->BatchNum) ? $this->printData($rawPaymentResponseData->Transaction->BatchNum) : null,
            'error_code'            => isset($rawPaymentResponseData->Transaction->Response->ErrorCode) ? $this->printData($rawPaymentResponseData->Transaction->Response->ErrorCode) : null,
            'error_message'         => isset($rawPaymentResponseData->Transaction->Response->ErrorMsg) ? $this->printData($rawPaymentResponseData->Transaction->Response->ErrorMsg) : null,
            'reason_code'           => isset($rawPaymentResponseData->Transaction->Response->ReasonCode) ? $this->printData($rawPaymentResponseData->Transaction->Response->ReasonCode) : null,
            'campaign_url'          => isset($rawPaymentResponseData->Transaction->CampaignChooseLink) ? $this->printData($rawPaymentResponseData->Transaction->CampaignChooseLink) : null,
            'all'                   => $rawPaymentResponseData,
            'trans_id'              => $raw3DAuthResponseData['transid'],
            'response'              => $response,
            'transaction_type'      => $this->type,
            'transaction'           => $this->type,
            'transaction_security'  => $transactionSecurity,
            'proc_return_code'      => $procReturnCode,
            'code'                  => $procReturnCode,
            'status'                => $status,
            'status_detail'         => $this->getStatusDetail(),
            'md_status'             => $raw3DAuthResponseData['mdstatus'],
            'rand'                  => (string) $raw3DAuthResponseData['rnd'],
            'hash'                  => (string) $raw3DAuthResponseData['secure3dhash'],
            'hash_params'           => (string) $raw3DAuthResponseData['hashparams'],
            'hash_params_val'       => (string) $raw3DAuthResponseData['hashparamsval'],
            'secure_3d_hash'        => (string) $raw3DAuthResponseData['secure3dhash'],
            'secure_3d_level'       => (string) $raw3DAuthResponseData['secure3dsecuritylevel'],
            'masked_number'         => (string) $raw3DAuthResponseData['MaskedPan'],
            'amount'                => (string) $raw3DAuthResponseData['txnamount'],
            'currency'              => (string) $raw3DAuthResponseData['txncurrencycode'],
            'tx_status'             => (string) $raw3DAuthResponseData['txnstatus'],
            'eci'                   => (string) $raw3DAuthResponseData['eci'],
            'cavv'                  => (string) $raw3DAuthResponseData['cavv'],
            'xid'                   => (string) $raw3DAuthResponseData['xid'],
            'md_error_message'      => (string) $raw3DAuthResponseData['mderrormessage'],
            //'name'                  => (string) $raw3DAuthResponseData['firmaadi'],
            'email'                 => (string) $raw3DAuthResponseData['customeremailaddress'],
            'extra'                 => null,
            '3d_all'                => $raw3DAuthResponseData,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function map3DPayResponseData($raw3DAuthResponseData)
    {
        $status = 'declined';
        $response = 'Declined';
        $procReturnCode = $raw3DAuthResponseData['procreturncode'];

        $transactionSecurity = 'MPI fallback';
        if (in_array($raw3DAuthResponseData['mdstatus'], [1, 2, 3, 4])) {
            if ($raw3DAuthResponseData['mdstatus'] == '1') {
                $transactionSecurity = 'Full 3D Secure';
            } elseif (in_array($raw3DAuthResponseData['mdstatus'], [2, 3, 4])) {
                $transactionSecurity = 'Half 3D Secure';
            }

            $status = 'approved';
            $response = 'Approved';
        }

        $this->response = (object) [
            'id'                    => (string) $raw3DAuthResponseData['authcode'],
            'order_id'              => (string) $raw3DAuthResponseData['oid'],
            'trans_id'              => (string) $raw3DAuthResponseData['transid'],
            'auth_code'             => (string) $raw3DAuthResponseData['authcode'],
            'host_ref_num'          => (string) $raw3DAuthResponseData['hostrefnum'],
            'response'              => $response,
            'transaction_type'      => $this->type,
            'transaction'           => $this->type,
            'transaction_security'  => $transactionSecurity,
            'proc_return_code'      => $procReturnCode,
            'code'                  => $procReturnCode,
            'md_status'             => $raw3DAuthResponseData['mdStatus'],
            'status'                => $status,
            'status_detail'         => isset($this->codes[$raw3DAuthResponseData['ProcReturnCode']]) ? (string) $raw3DAuthResponseData['ProcReturnCode'] : null,
            'hash'                  => (string) $raw3DAuthResponseData['secure3dhash'],
            'rand'                  => (string) $raw3DAuthResponseData['rnd'],
            'hash_params'           => (string) $raw3DAuthResponseData['hashparams'],
            'hash_params_val'       => (string) $raw3DAuthResponseData['hashparamsval'],
            'masked_number'         => (string) $raw3DAuthResponseData['MaskedPan'],
            'amount'                => (string) $raw3DAuthResponseData['txnamount'],
            'currency'              => (string) $raw3DAuthResponseData['txncurrencycode'],
            'tx_status'             => (string) $raw3DAuthResponseData['txnstatus'],
            'eci'                   => (string) $raw3DAuthResponseData['eci'],
            'cavv'                  => (string) $raw3DAuthResponseData['cavv'],
            'xid'                   => (string) $raw3DAuthResponseData['xid'],
            'error_code'            => (string) $raw3DAuthResponseData['errcode'],
            'error_message'         => (string) $raw3DAuthResponseData['errmsg'],
            'md_error_message'      => (string) $raw3DAuthResponseData['mderrormessage'],
            'campaign_url'          => null,
            //'name'                  => (string) $raw3DAuthResponseData['firmaadi'],
            'email'                 => (string) $raw3DAuthResponseData['customeremailaddress'],
            'extra'                 => $raw3DAuthResponseData['Extra'],
            'all'                   => $raw3DAuthResponseData,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function mapPaymentResponse($responseData)
    {
        $status = 'declined';
        if ($this->getProcReturnCode() === '00') {
            $status = 'approved';
        }

        return (object) [
            'id'                => isset($responseData->Stan) ? $this->printData($responseData->Stan) : null,
            'order_id'          => isset($responseData->OrderId) ? $this->printData($responseData->OrderId) : null,
            'group_id'          => isset($responseData->VPosMessage->BatchID) ? $this->printData($responseData->VPosMessage->BatchID) : null,
            'trans_id'          => isset($responseData->Stan) ? $this->printData($responseData->Stan) : null,
            'response'          => isset($responseData->ResponseMessage) ? $this->printData($responseData->ResponseMessage) : null,
            'transaction_type'  => $this->type,
            'transaction'       => $this->type,
            'auth_code'         => isset($responseData->Stan) ? $this->printData($responseData->Stan) : null,
            'host_ref_num'      => isset($responseData->RRN) ? $this->printData($responseData->RRN) : null,
            'ret_ref_num'       => isset($responseData->RRN) ? $this->printData($responseData->RRN) : null,
            'hash_data'         => isset($responseData->HashData) ? $this->printData($responseData->HashData) : null,
            'proc_return_code'  => $this->getProcReturnCode(),
            'code'              => $this->getProcReturnCode(),
            'status'            => $status,
            'status_detail'     => $this->getStatusDetail(),
            'error_code'        => isset($responseData->ResponseCode) ? $this->printData($responseData->ResponseCode) : null,
            'error_message'     => isset($responseData->ResponseMessage) ? $this->printData($responseData->ResponseMessage) : null,
            'all'               => $responseData,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function mapRefundResponse($rawResponseData)
    {
        $status = 'declined';
        if ($this->getProcReturnCode() === '00') {
            $status = 'approved';
        }

        return (object) [
            'id'                => isset($rawResponseData->Transaction->AuthCode) ? $this->printData($rawResponseData->Transaction->AuthCode) : null,
            'order_id'          => isset($rawResponseData->Order->OrderID) ? $this->printData($rawResponseData->Order->OrderID) : null,
            'group_id'          => isset($rawResponseData->Order->GroupID) ? $this->printData($rawResponseData->Order->GroupID) : null,
            'trans_id'          => isset($rawResponseData->Transaction->AuthCode) ? $this->printData($rawResponseData->Transaction->AuthCode) : null,
            'response'          => isset($rawResponseData->Transaction->Response->Message) ? $this->printData($rawResponseData->Transaction->Response->Message) : null,
            'auth_code'         => isset($rawResponseData->Transaction->AuthCode) ? $rawResponseData->Transaction->AuthCode : null,
            'host_ref_num'      => isset($rawResponseData->Transaction->RetrefNum) ? $this->printData($rawResponseData->Transaction->RetrefNum) : null,
            'ret_ref_num'       => isset($rawResponseData->Transaction->RetrefNum) ? $this->printData($rawResponseData->Transaction->RetrefNum) : null,
            'hash_data'         => isset($rawResponseData->Transaction->HashData) ? $this->printData($rawResponseData->Transaction->HashData) : null,
            'proc_return_code'  => $this->getProcReturnCode(),
            'code'              => $this->getProcReturnCode(),
            'error_code'        => isset($rawResponseData->Transaction->Response->Code) ? $this->printData($rawResponseData->Transaction->Response->Code) : null,
            'error_message'     => isset($rawResponseData->Transaction->Response->ErrorMsg) ? $this->printData($rawResponseData->Transaction->Response->ErrorMsg) : null,
            'status'            => $status,
            'status_detail'     => $this->getStatusDetail(),
            'all'               => $rawResponseData,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function mapCancelResponse($rawResponseData)
    {
        return $this->mapRefundResponse($rawResponseData);
    }

    /**
     * @inheritDoc
     */
    protected function mapStatusResponse($rawResponseData)
    {
        $status = 'declined';
        if ($this->getProcReturnCode() === '00') {
            $status = 'approved';
        }

        return (object) [
            'id'                => isset($rawResponseData->Order->OrderInqResult->AuthCode) ? $this->printData($rawResponseData->Order->OrderInqResult->AuthCode) : null,
            'order_id'          => isset($rawResponseData->Order->OrderID) ? $this->printData($rawResponseData->Order->OrderID) : null,
            'group_id'          => isset($rawResponseData->Order->GroupID) ? $this->printData($rawResponseData->Order->GroupID) : null,
            'trans_id'          => isset($rawResponseData->Transaction->AuthCode) ? $this->printData($rawResponseData->Transaction->AuthCode) : null,
            'response'          => isset($rawResponseData->Transaction->Response->Message) ? $this->printData($rawResponseData->Transaction->Response->Message) : null,
            'auth_code'         => isset($rawResponseData->Order->OrderInqResult->AuthCode) ? $this->printData($rawResponseData->Order->OrderInqResult->AuthCode) : null,
            'host_ref_num'      => isset($rawResponseData->Order->OrderInqResult->RetrefNum) ? $this->printData($rawResponseData->Order->OrderInqResult->RetrefNum) : null,
            'ret_ref_num'       => isset($rawResponseData->Order->OrderInqResult->RetrefNum) ? $this->printData($rawResponseData->Order->OrderInqResult->RetrefNum) : null,
            'hash_data'         => isset($rawResponseData->Transaction->HashData) ? $this->printData($rawResponseData->Transaction->HashData) : null,
            'proc_return_code'  => $this->getProcReturnCode(),
            'code'              => $this->getProcReturnCode(),
            'status'            => $status,
            'status_detail'     => $this->getStatusDetail(),
            'error_code'        => isset($rawResponseData->Transaction->Response->Code) ? $this->printData($rawResponseData->Transaction->Response->Code) : null,
            'error_message'     => isset($rawResponseData->Transaction->Response->ErrorMsg) ? $this->printData($rawResponseData->Transaction->Response->ErrorMsg) : null,
            'extra'             => isset($rawResponseData->Extra) ? $rawResponseData->Extra : null,
            'all'               => $rawResponseData,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function mapHistoryResponse($rawResponseData)
    {
        $status = 'declined';
        if ($this->getProcReturnCode() === '00') {
            $status = 'approved';
        }

        return (object) [
            'id'                => isset($rawResponseData->Order->OrderHistInqResult->AuthCode) ? $this->printData($rawResponseData->Order->OrderHistInqResult->AuthCode) : null,
            'order_id'          => isset($rawResponseData->Order->OrderID) ? $this->printData($rawResponseData->Order->OrderID) : null,
            'group_id'          => isset($rawResponseData->Order->GroupID) ? $this->printData($rawResponseData->Order->GroupID) : null,
            'trans_id'          => isset($rawResponseData->Transaction->AuthCode) ? $this->printData($rawResponseData->Transaction->AuthCode) : null,
            'response'          => isset($rawResponseData->Transaction->Response->Message) ? $this->printData($rawResponseData->Transaction->Response->Message) : null,
            'auth_code'         => isset($rawResponseData->Order->OrderHistInqResult->AuthCode) ? $this->printData($rawResponseData->Order->OrderHistInqResult->AuthCode) : null,
            'host_ref_num'      => isset($rawResponseData->Order->OrderHistInqResult->RetrefNum) ? $this->printData($rawResponseData->Order->OrderHistInqResult->RetrefNum) : null,
            'ret_ref_num'       => isset($rawResponseData->Order->OrderHistInqResult->RetrefNum) ? $this->printData($rawResponseData->Order->OrderHistInqResult->RetrefNum) : null,
            'hash_data'         => isset($rawResponseData->Transaction->HashData) ? $this->printData($rawResponseData->Transaction->HashData) : null,
            'proc_return_code'  => $this->getProcReturnCode(),
            'code'              => $this->getProcReturnCode(),
            'status'            => $status,
            'status_detail'     => $this->getStatusDetail(),
            'error_code'        => isset($rawResponseData->Transaction->Response->Code) ? $this->printData($rawResponseData->Transaction->Response->Code) : null,
            'error_message'     => isset($rawResponseData->Transaction->Response->ErrorMsg) ? $this->printData($rawResponseData->Transaction->Response->ErrorMsg) : null,
            'extra'             => isset($rawResponseData->Extra) ? $rawResponseData->Extra : null,
            'order_txn'         => isset($rawResponseData->Order->OrderHistInqResult->OrderTxnList->OrderTxn) ? $rawResponseData->Order->OrderHistInqResult->OrderTxnList->OrderTxn : [],
            'all'               => $rawResponseData,
        ];
    }

    /**
     * Get ProcReturnCode
     *
     * @return string|null
     */
    protected function getProcReturnCode()
    {
        return isset($this->data->ResponseCode) ? (string) $this->data->ResponseCode : null;
    }

    /**
     * Get Status Detail Text
     *
     * @return string|null
     */
    protected function getStatusDetail()
    {
        $procReturnCode = $this->getProcReturnCode();

        return $procReturnCode ? (isset($this->codes[$procReturnCode]) ? (string) $this->codes[$procReturnCode] : null) : null;
    }

    /**
     * @inheritDoc
     */
    protected function preparePaymentOrder(array $order)
    {
        // Installment
        $installment = '';
        if (isset($order['installment']) && $order['installment'] > 1) {
            $installment = $order['installment'];
        }

        // Order
        return (object) array_merge($order, [
            'installment'   => $installment,
            'currency'      => $this->mapCurrency($order['currency']),
            'amount'        => self::amountFormat($order['amount']),
            'ip' => isset($order['ip']) ? $order['ip'] : '',
            'email' => isset($order['email']) ? $order['email'] : '',
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function preparePostPaymentOrder(array $order)
    {
        return (object) [
            'id' => $order['id'],
            'md' => $order['md'],
            'currency'      => $this->mapCurrency($order['currency']),
            'amount'      => self::amountFormat($order['amount']),
            'success_url' => $order['success_url'],
            'fail_url' => $order['fail_url'],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function prepareStatusOrder(array $order)
    {
        return (object) [
            'id' => $order['id'],
            'amount' => self::amountFormat(1),
            'currency' => $this->mapCurrency($order['currency']),
            'ip' => isset($order['ip']) ? $order['ip'] : '',
            'email' => isset($order['email']) ? $order['email'] : '',
            'installment' => '',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function prepareHistoryOrder(array $order)
    {
        return $this->prepareStatusOrder($order);
    }

    /**
     * @inheritDoc
     */
    protected function prepareCancelOrder(array $order)
    {
        return (object) [
            'id' => $order['id'],
            'amount' => self::amountFormat(1),
            'currency' => $this->mapCurrency($order['currency']),
            'ref_ret_num' => $order['ref_ret_num'],
            'ip' => isset($order['ip']) ? $order['ip'] : '',
            'email' => isset($order['email']) ? $order['email'] : '',
            'installment' => '',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function prepareRefundOrder(array $order)
    {
        return $this->prepareCancelOrder($order);
    }

    /**
     * Make Security Data
     * @return string
     */
    private function createSecurityData()
    {
        if ($this->type === $this->types[self::TX_REFUND] || $this->type === $this->types[self::TX_CANCEL]) {
            $password = $this->account->getRefundPassword();
        } else {
            $password = $this->account->getPassword();
        }

//        $map = [
//            $password
//            //str_pad((int) $this->account->getTerminalId(), 9, 0, STR_PAD_LEFT),
//        ];
//        //$HashedPassword = base64_encode(sha1($this->account->getPassword(),"ISO-8859-9")); //md5($Password);

        return base64_encode(sha1($password, "ISO-8859-9"));
    }
}
